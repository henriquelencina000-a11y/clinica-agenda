<?php
declare(strict_types=1);

final class Disponibilidade
{
    /** $ocupados: lista de [inicio, fim]. */
    public static function gerar(string $data, array $regras, array $ocupados, DateTimeImmutable $agora): array
    {
        $horarios = [];
        foreach ($regras as $regra) {
            $duracao = (int)$regra['duracao_min'];
            $passo = $duracao + (int)$regra['intervalo_min'];
            $inicio = new DateTimeImmutable("$data {$regra['hora_inicio']}");
            $fimExpediente = new DateTimeImmutable("$data {$regra['hora_fim']}");
            while (($fim = $inicio->modify("+$duracao minutes")) <= $fimExpediente) {
                if ($inicio > $agora && !self::sobrepoe($inicio, $fim, $ocupados)) {
                    $horarios[$inicio->format('H:i')] = ['inicio' => $inicio, 'fim' => $fim];
                }
                $inicio = $inicio->modify("+$passo minutes");
            }
        }
        ksort($horarios);
        return array_values($horarios);
    }

    public static function sobrepoe(DateTimeImmutable $inicio, DateTimeImmutable $fim, array $ocupados): bool
    {
        foreach ($ocupados as [$ocupadoInicio, $ocupadoFim]) {
            if ($inicio < $ocupadoFim && $fim > $ocupadoInicio) return true;
        }
        return false;
    }

    /** ['Y-m-d' => horários livres] */
    public static function porMes(int $prof, DateTimeImmutable $mes, int $ignorarAgendamento = 0): array
    {
        $primeiroDia = $mes->modify('first day of this month')->setTime(0, 0);
        $proximoMes = $primeiroDia->modify('+1 month');
        $regras = Agenda::regras($prof);
        if (!$regras) return [];

        $ocupados = Agenda::ocupados($prof, sql_dt($primeiroDia), sql_dt($proximoMes), $ignorarAgendamento);
        $agora = agora();
        $dias = [];
        for ($dia = $primeiroDia; $dia < $proximoMes; $dia = $dia->modify('+1 day')) {
            $data = $dia->format('Y-m-d');
            $diaSemana = (int)$dia->format('w');
            $regrasDoDia = array_filter($regras, fn($regra) => (int)$regra['dia_semana'] === $diaSemana
                && $regra['vigencia_inicio'] <= $data && ($regra['vigencia_fim'] === null || $regra['vigencia_fim'] >= $data));
            if (!$regrasDoDia) continue;

            $livres = count(self::gerar($data, $regrasDoDia, $ocupados, $agora));
            if ($livres) $dias[$data] = $livres;
        }
        return $dias;
    }

    /** Primeiros horários do próximo dia com vaga, dentro do prazo em $dias. */
    public static function proximos(int $prof, int $quantos = 3, int $dias = 14): array
    {
        $limite = agora()->modify("+$dias days")->format('Y-m-d');
        $mes = agora();
        for ($i = 0; $i < 2; $i++, $mes = $mes->modify('first day of next month')) {
            $data = array_key_first(self::porMes($prof, $mes));
            if ($data === null) continue;
            return $data > $limite ? [] : array_slice(self::livres($prof, $data), 0, $quantos);
        }
        return [];
    }

    public static function livres(int $prof, string $data, int $ignorarAgendamento = 0): array
    {
        $regras = Agenda::regrasDoDia($prof, $data);
        if (!$regras) return [];
        $diaSeguinte = (new DateTimeImmutable($data))->modify('+1 day')->format('Y-m-d');
        $ocupados = Agenda::ocupados($prof, "$data 00:00:00", "$diaSeguinte 00:00:00", $ignorarAgendamento);
        return self::gerar($data, $regras, $ocupados, agora());
    }
}
