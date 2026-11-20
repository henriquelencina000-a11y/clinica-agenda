<?php
declare(strict_types=1);

final class Agendamento
{
    private const DETALHES = 'SELECT a.*, p.nome AS profissional, e.nome AS especialidade, u.nome AS paciente
                              FROM agendamento a
                              JOIN profissional p ON p.id_profissional = a.id_profissional
                              JOIN especialidade e ON e.id_especialidade = a.id_especialidade
                              JOIN usuario u ON u.id_usuario = a.id_paciente';

    public static function travarPaciente(int $paciente): void
    {
        if (!q('SELECT id_paciente FROM paciente WHERE id_paciente = ? FOR UPDATE', [$paciente])->fetchColumn()) {
            throw new RegraException('Cadastro de paciente não encontrado.');
        }
    }

    public static function travar(int $id, ?int $paciente): ?array
    {
        $sql = 'SELECT * FROM agendamento WHERE id_agendamento = ?' . ($paciente ? ' AND id_paciente = ?' : '') . ' FOR UPDATE';
        return q($sql, $paciente ? [$id, $paciente] : [$id])->fetch() ?: null;
    }

    public static function doPaciente(int $paciente, ?int $id = null): array
    {
        $sql = self::DETALHES . ' WHERE a.id_paciente = ?' . ($id ? ' AND a.id_agendamento = ?' : '') . ' ORDER BY a.inicio DESC';
        return q($sql, $id ? [$paciente, $id] : [$paciente])->fetchAll();
    }

    public static function futurosAtivos(int $paciente): int
    {
        return (int)q("SELECT COUNT(*) FROM agendamento WHERE id_paciente = ? AND situacao = 'confirmado' AND inicio > ?",
                      [$paciente, sql_dt(agora())])->fetchColumn();
    }

    public static function futuros(array $filtro): array
    {
        $sql = "SELECT inicio FROM agendamento WHERE situacao = 'confirmado' AND inicio > ?";
        $p = [sql_dt(agora())];
        foreach (['id_profissional', 'id_especialidade'] as $col) {
            if (isset($filtro[$col])) { $sql .= " AND $col = ?"; $p[] = $filtro[$col]; }
        }
        if (isset($filtro['dia_semana'])) { $sql .= ' AND DAYOFWEEK(inicio) - 1 = ?'; $p[] = $filtro['dia_semana']; }
        return q($sql . ' ORDER BY inicio', $p)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function confirmadosSobrepostos(int $prof, string $ini, string $fim): array
    {
        return q("SELECT inicio FROM agendamento WHERE id_profissional = ? AND situacao = 'confirmado' AND inicio < ? AND fim > ? ORDER BY inicio",
                 [$prof, $fim, $ini])->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function criar(int $paciente, int $prof, int $esp, DateTimeImmutable $ini, DateTimeImmutable $fim): int
    {
        q("INSERT INTO agendamento (id_paciente, id_profissional, id_especialidade, inicio, fim, situacao) VALUES (?, ?, ?, ?, ?, 'confirmado')",
          [$paciente, $prof, $esp, sql_dt($ini), sql_dt($fim)]);
        return (int)db()->lastInsertId();
    }

    public static function mudarSituacao(array $a, string $nova, int $autor, string $motivo): void
    {
        $canceladoEm = $nova === 'cancelado' ? sql_dt(agora()) : $a['cancelado_em'];
        q('UPDATE agendamento SET situacao = ?, cancelado_em = ? WHERE id_agendamento = ?', [$nova, $canceladoEm, $a['id_agendamento']]);
        self::registrarHistorico((int)$a['id_agendamento'], $a['situacao'], $nova, $autor, $motivo);
    }

    public static function registrarHistorico(int $id, ?string $anterior, string $nova, int $autor, string $motivo): void
    {
        q('INSERT INTO historico_agendamento (id_agendamento, situacao_anterior, situacao_nova, autor, data_hora, motivo) VALUES (?, ?, ?, ?, ?, ?)',
          [$id, $anterior, $nova, $autor, sql_dt(agora()), $motivo]);
    }

    public static function listar(string $de, string $ate, int $prof, string $situacao): array
    {
        [$where, $p] = self::filtros($de, $ate, $prof, $situacao);
        return q(self::DETALHES . " WHERE $where ORDER BY a.inicio LIMIT 500", $p)->fetchAll();
    }

    public static function relatorio(string $de, string $ate, int $prof, string $situacao): array
    {
        [$where, $p] = self::filtros($de, $ate, $prof, $situacao);
        return q("SELECT p.nome AS profissional,
                         SUM(a.situacao = 'confirmado') AS confirmados,
                         SUM(a.situacao = 'realizado') AS realizados,
                         SUM(a.situacao = 'cancelado') AS cancelados,
                         COUNT(*) AS total
                  FROM agendamento a JOIN profissional p ON p.id_profissional = a.id_profissional
                  WHERE $where GROUP BY p.id_profissional, p.nome ORDER BY p.nome", $p)->fetchAll();
    }

    private static function filtros(string $de, string $ate, int $prof, string $situacao): array
    {
        $where = 'a.inicio >= ? AND a.inicio < ?';
        $p = ["$de 00:00:00", (new DateTimeImmutable($ate))->modify('+1 day')->format('Y-m-d 00:00:00')];
        if ($prof) { $where .= ' AND a.id_profissional = ?'; $p[] = $prof; }
        if (isset(SITUACOES[$situacao])) { $where .= ' AND a.situacao = ?'; $p[] = $situacao; }
        return [$where, $p];
    }
}
