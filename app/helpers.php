<?php
declare(strict_types=1);

final class RegraException extends RuntimeException {}

function config(string $chave): mixed
{
    return $GLOBALS['CONFIG'][$chave];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $pdo = new PDO($c['dsn'], $c['usuario'], $c['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/** READ COMMITTED: depois do lock as leituras veem o último commit. */
function transacao(callable $fn): mixed
{
    $pdo = db();
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function agora(): DateTimeImmutable { return new DateTimeImmutable(); }
function sql_dt(DateTimeInterface $d): string { return $d->format('Y-m-d H:i:s'); }
function fmt(string $dt): string { return (new DateTimeImmutable($dt))->format('d/m/Y H:i'); }

function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $caminho = '/'): string { return BASE . $caminho; }

function redirect(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

function voltar(string $padrao = '/'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $mesmoHost = $ref !== '' && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    header('Location: ' . ($mesmoHost ? $ref : url($padrao)));
    exit;
}

function view(string $nome, array $dados = [], int $status = 200): void
{
    http_response_code($status);
    extract($dados);
    ob_start();
    require __DIR__ . "/Views/$nome.php";
    $conteudo = ob_get_clean();
    require __DIR__ . '/Views/layout.php';
}

function flash(?string $tipo = null, ?string $msg = null): array
{
    if ($tipo !== null) {
        $_SESSION['flash'][] = [$tipo, $msg];
        return [];
    }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf'] ?? '') . '">';
}

function iniciar_sessao(): void
{
    session_name('clinica_sid');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => https(),
        'path'     => '/',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();

    if (isset($_SESSION['usuario']) && time() - ($_SESSION['ultimo_acesso'] ?? 0) > config('sessao_minutos') * 60) {
        unset($_SESSION['usuario']);
        session_regenerate_id(true);
        flash('warning', 'Sua sessão expirou por inatividade. Entre novamente.');
    }
    $_SESSION['ultimo_acesso'] = time();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function usuario(): ?array { return $_SESSION['usuario'] ?? null; }

function exigir(string $perfil): array
{
    $u = usuario();
    if (!$u) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $q = $_SERVER['QUERY_STRING'] ?? '';
            $_SESSION['voltar_para'] = ($GLOBALS['caminho'] ?? '/') . ($q !== '' ? "?$q" : '');
        }
        flash('warning', 'Entre na sua conta para continuar.');
        redirect('/login');
    }
    if ($u['perfil'] !== $perfil) {
        auditar('acesso_negado', (string)($_SERVER['REQUEST_URI'] ?? ''), 'negado');
        view('erro', ['titulo' => 'Acesso negado', 'mensagem' => 'Você não tem permissão para acessar esta página.'], 403);
        exit;
    }
    return $u;
}

function auditar(string $acao, string $recurso, string $resultado = 'ok', ?int $idUsuario = null): void
{
    q('INSERT INTO registro_auditoria (id_usuario, acao, recurso, data_hora, resultado) VALUES (?, ?, ?, ?, ?)',
      [$idUsuario ?? usuario()['id'] ?? null, $acao, mb_substr($recurso, 0, 190), sql_dt(agora()), $resultado]);
}

function entrada(string $chave, int $max = 255): string
{
    $v = $_POST[$chave] ?? $_GET[$chave] ?? '';
    return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
}

function inteiro(string $chave): int
{
    return (int)(filter_var($_POST[$chave] ?? $_GET[$chave] ?? null, FILTER_VALIDATE_INT) ?: 0);
}

function data_valida(string $s, string $formato = 'Y-m-d'): ?DateTimeImmutable
{
    $d = DateTimeImmutable::createFromFormat("!$formato", $s);
    return $d && $d->format($formato) === $s ? $d : null;
}

const SITUACOES = ['confirmado' => 'Confirmada', 'realizado' => 'Realizada', 'cancelado' => 'Cancelada'];
const DIAS = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
const MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

function data_extenso(DateTimeInterface $d): string
{
    return DIAS[(int)$d->format('w')] . ', ' . $d->format('j') . ' de ' . MESES[(int)$d->format('n') - 1];
}

function mes_param(string $valor): DateTimeImmutable
{
    $atual = agora()->modify('first day of this month')->setTime(0, 0);
    $m = data_valida($valor, 'Y-m');
    if (!$m || $m < $atual) return $atual;
    return $m > $atual->modify('+12 months') ? $atual->modify('+12 months') : $m;
}

function badge_situacao(string $s): string
{
    $cor = ['confirmado' => 'primary', 'realizado' => 'success', 'cancelado' => 'secondary'][$s] ?? 'light';
    return '<span class="badge text-bg-' . $cor . '">' . e(SITUACOES[$s] ?? $s) . '</span>';
}
