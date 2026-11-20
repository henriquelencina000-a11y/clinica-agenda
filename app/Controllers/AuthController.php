<?php
declare(strict_types=1);

final class AuthController
{
    private const MAX_FALHAS = 5;
    private const JANELA_MIN = 15;

    public static function formCadastro(): void
    {
        view('cadastro', ['titulo' => 'Cadastro', 'd' => [], 'erros' => []]);
    }

    public static function cadastrar(): void
    {
        $d = [
            'nome'     => entrada('nome', 120),
            'email'    => mb_strtolower(entrada('email', 190)),
            'telefone' => entrada('telefone', 20),
        ];
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        $confirmacao = is_string($_POST['senha_confirmacao'] ?? null) ? $_POST['senha_confirmacao'] : '';

        $erros = [];
        if (mb_strlen($d['nome']) < 3) $erros['nome'] = 'Informe seu nome completo.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erros['email'] = 'Informe um e-mail válido.';
        if ($d['telefone'] !== '' && !preg_match('/^[0-9 ()+\-]{8,20}$/', $d['telefone'])) $erros['telefone'] = 'Telefone inválido. Use apenas números, espaços, parênteses e hífen.';
        if (strlen($senha) < 8 || strlen($senha) > 72) $erros['senha'] = 'A senha deve ter entre 8 e 72 caracteres.';
        elseif ($senha !== $confirmacao) $erros['senha_confirmacao'] = 'As senhas não conferem.';
        if (empty($_POST['consentimento'])) $erros['consentimento'] = 'É necessário declarar ciência do aviso de privacidade.';

        if (!$erros) {
            try {
                $id = Usuario::criarPaciente($d['nome'], $d['email'], $senha, $d['telefone']);
                auditar('cadastro', "usuario:$id", 'ok', $id);
                flash('success', 'Cadastro concluído. Entre com seu e-mail e senha.');
                redirect('/login');
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') throw $e;
                $erros['email'] = 'Não foi possível usar este e-mail. Se você já tem conta, faça login.';
            }
        }
        view('cadastro', ['titulo' => 'Cadastro', 'd' => $d, 'erros' => $erros], 422);
    }

    public static function formLogin(): void
    {
        view('login', ['titulo' => 'Entrar', 'email' => '', 'erro' => null]);
    }

    public static function entrar(): void
    {
        $email = mb_strtolower(entrada('email', 190));
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        $chave = 'login:' . hash('sha256', $email); // e-mail fora do log

        if (Usuario::falhasRecentes($chave, self::JANELA_MIN) >= self::MAX_FALHAS) {
            auditar('login_bloqueado', $chave, 'negado');
            view('login', ['titulo' => 'Entrar', 'email' => $email, 'erro' => 'Muitas tentativas. Aguarde ' . self::JANELA_MIN . ' minutos e tente novamente.'], 429);
            return;
        }

        $u = Usuario::porEmail($email);
        if (!$u || !$u['ativo'] || !password_verify($senha, $u['senha_hash'])) {
            auditar('login_falha', $chave, 'negado');
            view('login', ['titulo' => 'Entrar', 'email' => $email, 'erro' => 'Não foi possível autenticar. Confira os dados e tente novamente.'], 401);
            return;
        }
        if (password_needs_rehash($u['senha_hash'], PASSWORD_DEFAULT)) Usuario::atualizarHash((int)$u['id_usuario'], $senha);

        session_regenerate_id(true);
        $_SESSION['usuario'] = ['id' => (int)$u['id_usuario'], 'nome' => $u['nome'], 'perfil' => $u['perfil']];
        auditar('login', 'usuario:' . $u['id_usuario']);
        $destino = $_SESSION['voltar_para'] ?? '';
        unset($_SESSION['voltar_para']);
        $ehAdmin = $u['perfil'] === 'admin';
        $valido = str_starts_with($destino, '/') && !str_starts_with($destino, '//') && str_starts_with($destino, '/admin') === $ehAdmin;
        redirect($valido ? $destino : ($ehAdmin ? '/admin/agendamentos' : '/meus-agendamentos'));
    }

    public static function sair(): void
    {
        if (usuario()) auditar('logout', 'usuario:' . usuario()['id']);
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
        flash('info', 'Você saiu da sua conta.');
        redirect('/');
    }
}
