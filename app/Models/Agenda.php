<?php
declare(strict_types=1);

final class Agenda
{
    public static function regrasDoDia(int $prof, string $data): array
    {
        $dia = (int)(new DateTimeImmutable($data))->format('w');
        return q('SELECT hora_inicio, hora_fim, duracao_min, intervalo_min FROM agenda_regra
                  WHERE id_profissional = ? AND dia_semana = ? AND vigencia_inicio <= ? AND (vigencia_fim IS NULL OR vigencia_fim >= ?)',
                 [$prof, $dia, $data, $data])->fetchAll();
    }

    public static function ocupados(int $prof, string $ini, string $fim, int $ignorarAgendamento = 0): array
    {
        $linhas = q("SELECT inicio, fim FROM bloqueio_agenda WHERE id_profissional = ? AND inicio < ? AND fim > ?
                     UNION ALL
                     SELECT inicio, fim FROM agendamento WHERE id_profissional = ? AND situacao = 'confirmado'
                       AND inicio < ? AND fim > ? AND id_agendamento <> ?",
                    [$prof, $fim, $ini, $prof, $fim, $ini, $ignorarAgendamento])->fetchAll();
        return array_map(fn($l) => [new DateTimeImmutable($l['inicio']), new DateTimeImmutable($l['fim'])], $linhas);
    }

    public static function regras(int $prof): array
    {
        return q('SELECT * FROM agenda_regra WHERE id_profissional = ? ORDER BY dia_semana, hora_inicio', [$prof])->fetchAll();
    }

    public static function regra(int $id, int $prof): ?array
    {
        return q('SELECT * FROM agenda_regra WHERE id_regra = ? AND id_profissional = ?', [$id, $prof])->fetch() ?: null;
    }

    public static function regraSobreposta(int $prof, int $dia, string $hi, string $hf, string $vi, ?string $vf): bool
    {
        return (bool)q('SELECT 1 FROM agenda_regra WHERE id_profissional = ? AND dia_semana = ?
                          AND hora_inicio < ? AND hora_fim > ?
                          AND vigencia_inicio <= COALESCE(?, \'9999-12-31\') AND COALESCE(vigencia_fim, \'9999-12-31\') >= ?',
                       [$prof, $dia, $hf, $hi, $vf, $vi])->fetchColumn();
    }

    public static function criarRegra(int $prof, int $dia, string $hi, string $hf, int $dur, int $int, string $vi, ?string $vf): void
    {
        q('INSERT INTO agenda_regra (id_profissional, dia_semana, hora_inicio, hora_fim, duracao_min, intervalo_min, vigencia_inicio, vigencia_fim)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [$prof, $dia, $hi, $hf, $dur, $int, $vi, $vf]);
    }

    public static function removerRegra(int $id): void
    {
        q('DELETE FROM agenda_regra WHERE id_regra = ?', [$id]);
    }

    public static function bloqueiosFuturos(int $prof): array
    {
        return q('SELECT * FROM bloqueio_agenda WHERE id_profissional = ? AND fim > ? ORDER BY inicio', [$prof, sql_dt(agora())])->fetchAll();
    }

    public static function criarBloqueio(int $prof, string $ini, string $fim, string $motivo, int $admin): void
    {
        q('INSERT INTO bloqueio_agenda (id_profissional, inicio, fim, motivo, criado_por) VALUES (?, ?, ?, ?, ?)',
          [$prof, $ini, $fim, $motivo !== '' ? $motivo : null, $admin]);
    }

    public static function removerBloqueio(int $id, int $prof): void
    {
        q('DELETE FROM bloqueio_agenda WHERE id_bloqueio = ? AND id_profissional = ?', [$id, $prof]);
    }
}
