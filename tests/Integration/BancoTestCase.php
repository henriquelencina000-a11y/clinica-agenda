<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class BancoTestCase extends TestCase
{
    private const TABELAS = ['registro_auditoria', 'historico_agendamento', 'agendamento', 'bloqueio_agenda', 'agenda_regra',
                             'profissional_especialidade', 'especialidade', 'profissional', 'paciente', 'usuario'];

    protected function setUp(): void
    {
        self::recriarBanco();
    }

    public static function recriarBanco(): void
    {
        db()->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::TABELAS as $t) db()->exec("DROP TABLE IF EXISTS $t");
        db()->exec('SET FOREIGN_KEY_CHECKS = 1');
        foreach (['schema.sql', 'seed.sql'] as $arquivo) {
            $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b.*$/mi', '', file_get_contents(dirname(__DIR__, 2) . "/database/$arquivo"));
            foreach (preg_split('/;\s*$/m', $sql) as $comando) {
                if (trim(preg_replace('/^\s*--.*$/m', '', $comando)) !== '') db()->exec($comando);
            }
        }
    }

    protected static function novoPaciente(string $nome = 'Paciente Teste'): int
    {
        return Usuario::criarPaciente($nome, uniqid('p', true) . '@teste.local', 'senha-teste-123', '');
    }

    protected static function novoAdmin(): int
    {
        return Usuario::criarAdmin('Admin Teste', uniqid('a', true) . '@teste.local', 'senha-teste-123');
    }

    /** Segunda-feira a 3+ dias de hoje. */
    protected static function segunda(): string
    {
        return agora()->modify('+2 days')->modify('next monday')->format('Y-m-d');
    }

    protected static function situacao(int $id): string
    {
        return (string)q('SELECT situacao FROM agendamento WHERE id_agendamento = ?', [$id])->fetchColumn();
    }

    protected static function contar(string $sql, array $p = []): int
    {
        return (int)q($sql, $p)->fetchColumn();
    }
}
