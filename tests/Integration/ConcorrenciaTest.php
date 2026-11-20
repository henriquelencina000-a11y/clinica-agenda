<?php
declare(strict_types=1);

final class ConcorrenciaTest extends BancoTestCase
{
    private const PROCESSOS = 8;

    public function testT06ApenasUmaReservaSimultaneaEConfirmada(): void
    {
        $inicio = self::segunda() . ' 08:00';
        $largada = (string)(microtime(true) + 2);
        $procs = $saidas = [];
        for ($i = 0; $i < self::PROCESSOS; $i++) {
            $paciente = self::novoPaciente("Concorrente $i");
            $procs[] = proc_open(
                [PHP_BINARY, __DIR__ . '/../concorrencia_worker.php', (string)$paciente, $inicio, $largada],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                null,
                getenv() + ['CLINICA_CONFIG' => getenv('CLINICA_CONFIG')]
            );
            $saidas[] = $pipes;
        }
        $resultados = [];
        foreach ($procs as $i => $proc) {
            $resultados[] = trim(stream_get_contents($saidas[$i][1])) . trim(stream_get_contents($saidas[$i][2]));
            proc_close($proc);
        }

        $this->assertSame(1, count(array_keys($resultados, 'ok', true)), 'Resultados: ' . implode(' | ', $resultados));
        $this->assertSame(self::PROCESSOS - 1, count(array_keys($resultados, 'recusado', true)), 'Resultados: ' . implode(' | ', $resultados));
        $this->assertSame(1, self::contar("SELECT COUNT(*) FROM agendamento WHERE id_profissional = 1 AND situacao = 'confirmado' AND inicio = ?", ["$inicio:00"]));
    }
}
