<?php
declare(strict_types=1);

final class Usuario
{
    public static function porEmail(string $email): ?array
    {
        return q('SELECT * FROM usuario WHERE email = ?', [$email])->fetch() ?: null;
    }

    public static function dadosPaciente(int $id): ?array
    {
        return q('SELECT u.nome, u.email, u.criado_em, p.telefone, p.consentimento_em, p.versao_aviso
                  FROM usuario u JOIN paciente p ON p.id_paciente = u.id_usuario WHERE u.id_usuario = ?', [$id])->fetch() ?: null;
    }

    public static function criarPaciente(string $nome, string $email, string $senha, string $telefone): int
    {
        return transacao(function () use ($nome, $email, $senha, $telefone) {
            q("INSERT INTO usuario (nome, email, senha_hash, perfil) VALUES (?, ?, ?, 'paciente')",
              [$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $id = (int)db()->lastInsertId();
            q('INSERT INTO paciente (id_paciente, telefone, consentimento_em, versao_aviso) VALUES (?, ?, ?, ?)',
              [$id, $telefone !== '' ? $telefone : null, sql_dt(agora()), config('versao_aviso')]);
            return $id;
        });
    }

    public static function criarAdmin(string $nome, string $email, string $senha): int
    {
        q("INSERT INTO usuario (nome, email, senha_hash, perfil) VALUES (?, ?, ?, 'admin')",
          [$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
        return (int)db()->lastInsertId();
    }

    public static function atualizarHash(int $id, string $senha): void
    {
        q('UPDATE usuario SET senha_hash = ? WHERE id_usuario = ?', [password_hash($senha, PASSWORD_DEFAULT), $id]);
    }

    public static function falhasRecentes(string $recurso, int $minutos): int
    {
        return (int)q("SELECT COUNT(*) FROM registro_auditoria WHERE acao = 'login_falha' AND recurso = ? AND data_hora > ?",
                      [$recurso, sql_dt(agora()->modify("-$minutos minutes"))])->fetchColumn();
    }
}
