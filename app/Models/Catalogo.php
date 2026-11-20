<?php
declare(strict_types=1);

final class Catalogo
{
    public static function especialidades(bool $soAtivas = true): array
    {
        return q('SELECT * FROM especialidade' . ($soAtivas ? ' WHERE ativa = 1' : '') . ' ORDER BY nome')->fetchAll();
    }

    public static function profissionais(bool $soAtivos = true): array
    {
        return q("SELECT p.*, GROUP_CONCAT(e.nome ORDER BY e.nome SEPARATOR ', ') AS especialidades,
                         GROUP_CONCAT(e.id_especialidade ORDER BY e.nome) AS ids_especialidades
                  FROM profissional p
                  LEFT JOIN profissional_especialidade pe ON pe.id_profissional = p.id_profissional AND pe.ativo = 1
                  LEFT JOIN especialidade e ON e.id_especialidade = pe.id_especialidade AND e.ativa = 1
                  " . ($soAtivos ? 'WHERE p.ativo = 1' : '') . "
                  GROUP BY p.id_profissional ORDER BY p.nome")->fetchAll();
    }

    public static function profissionaisParaAgendar(int $esp): array
    {
        return q("SELECT DISTINCT p.id_profissional, p.nome
                  FROM profissional p
                  JOIN profissional_especialidade pe ON pe.id_profissional = p.id_profissional AND pe.ativo = 1
                  JOIN especialidade e ON e.id_especialidade = pe.id_especialidade AND e.ativa = 1
                  JOIN agenda_regra r ON r.id_profissional = p.id_profissional AND (r.vigencia_fim IS NULL OR r.vigencia_fim >= CURDATE())
                  WHERE p.ativo = 1 AND e.id_especialidade = ? ORDER BY p.nome", [$esp])->fetchAll();
    }

    public static function oferece(int $prof, int $esp): bool
    {
        return (bool)q('SELECT 1 FROM profissional p
                        JOIN profissional_especialidade pe ON pe.id_profissional = p.id_profissional AND pe.ativo = 1
                        JOIN especialidade e ON e.id_especialidade = pe.id_especialidade AND e.ativa = 1
                        WHERE p.id_profissional = ? AND e.id_especialidade = ? AND p.ativo = 1', [$prof, $esp])->fetchColumn();
    }

    public static function nomes(int $prof, int $esp): ?array
    {
        return q('SELECT p.nome AS profissional, e.nome AS especialidade FROM profissional p, especialidade e
                  WHERE p.id_profissional = ? AND e.id_especialidade = ?', [$prof, $esp])->fetch() ?: null;
    }

    public static function travarProfissional(int $prof): void
    {
        if (!q('SELECT id_profissional FROM profissional WHERE id_profissional = ? FOR UPDATE', [$prof])->fetchColumn()) {
            throw new RegraException('Profissional não encontrado.');
        }
    }

    public static function idsEspecialidadesAtivas(int $prof): array
    {
        return array_map('intval', q('SELECT id_especialidade FROM profissional_especialidade WHERE id_profissional = ? AND ativo = 1', [$prof])->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function salvarProfissional(?int $id, string $nome, string $registro, bool $ativo): int
    {
        $reg = $registro !== '' ? $registro : null;
        if ($id) {
            q('UPDATE profissional SET nome = ?, registro_profissional = ?, ativo = ? WHERE id_profissional = ?', [$nome, $reg, (int)$ativo, $id]);
            return $id;
        }
        q('INSERT INTO profissional (nome, registro_profissional, ativo) VALUES (?, ?, ?)', [$nome, $reg, (int)$ativo]);
        return (int)db()->lastInsertId();
    }

    public static function definirVinculo(int $prof, int $esp, bool $ativo): void
    {
        q('INSERT INTO profissional_especialidade (id_profissional, id_especialidade, ativo) VALUES (?, ?, ?)
           ON DUPLICATE KEY UPDATE ativo = VALUES(ativo)', [$prof, $esp, (int)$ativo]);
    }

    public static function salvarEspecialidade(?int $id, string $nome, string $descricao, bool $ativa): void
    {
        if ($id) {
            q('UPDATE especialidade SET nome = ?, descricao = ?, ativa = ? WHERE id_especialidade = ?', [$nome, $descricao, (int)$ativa, $id]);
        } else {
            q('INSERT INTO especialidade (nome, descricao, ativa) VALUES (?, ?, ?)', [$nome, $descricao, (int)$ativa]);
        }
    }
}
