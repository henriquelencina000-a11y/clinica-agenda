<?php
declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $arquivo = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($arquivo && is_file($arquivo) && str_starts_with($arquivo, __DIR__ . DIRECTORY_SEPARATOR . 'assets')) return false;
}

require __DIR__ . '/../app/bootstrap.php';

define('BASE', $GLOBALS['CONFIG']['base'] ?? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

if (!empty($GLOBALS['CONFIG']['forcar_https']) && !https()) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}
if (https()) header('Strict-Transport-Security: max-age=31536000');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$rotas = [
    'GET /'                            => [PublicoController::class, 'inicio'],
    'GET /especialidades'              => [PublicoController::class, 'especialidades'],
    'GET /profissionais'               => [PublicoController::class, 'profissionais'],
    'GET /privacidade'                 => [PublicoController::class, 'privacidade'],
    'GET /cadastro'                    => [AuthController::class, 'formCadastro'],
    'POST /cadastro'                   => [AuthController::class, 'cadastrar'],
    'GET /login'                       => [AuthController::class, 'formLogin'],
    'POST /login'                      => [AuthController::class, 'entrar'],
    'POST /logout'                     => [AuthController::class, 'sair'],
    'GET /horarios'                    => [PacienteController::class, 'horarios'],
    'GET /agendamentos/confirmar'      => [PacienteController::class, 'confirmar'],
    'POST /agendamentos'               => [PacienteController::class, 'agendar'],
    'GET /meus-agendamentos'           => [PacienteController::class, 'meus'],
    'GET /meus-dados'                  => [PacienteController::class, 'meusDados'],
    'POST /agendamentos/cancelar'      => [PacienteController::class, 'cancelar'],
    'GET /agendamentos/reagendar'      => [PacienteController::class, 'formReagendar'],
    'POST /agendamentos/reagendar'     => [PacienteController::class, 'reagendar'],
    'GET /admin'                       => [AdminController::class, 'inicio'],
    'GET /admin/agendamentos'          => [AdminController::class, 'agendamentos'],
    'POST /admin/agendamentos/situacao'=> [AdminController::class, 'situacao'],
    'GET /admin/especialidades'        => [AdminController::class, 'especialidades'],
    'POST /admin/especialidades'       => [AdminController::class, 'salvarEspecialidade'],
    'GET /admin/profissionais'         => [AdminController::class, 'profissionais'],
    'POST /admin/profissionais'        => [AdminController::class, 'salvarProfissional'],
    'GET /admin/agenda'                => [AdminController::class, 'agenda'],
    'POST /admin/agenda/regra'         => [AdminController::class, 'salvarRegra'],
    'POST /admin/agenda/regra/remover' => [AdminController::class, 'removerRegra'],
    'POST /admin/agenda/bloqueio'      => [AdminController::class, 'salvarBloqueio'],
    'POST /admin/agenda/bloqueio/remover' => [AdminController::class, 'removerBloqueio'],
    'GET /admin/relatorios'            => [AdminController::class, 'relatorios'],
];

$caminho = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (BASE !== '' && str_starts_with($caminho, BASE)) $caminho = substr($caminho, strlen(BASE));
$caminho = '/' . trim(preg_replace('#^/index\.php#', '', $caminho), '/');
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    iniciar_sessao();

    if ($metodo === 'POST' && !hash_equals($_SESSION['csrf'], is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : '')) {
        view('erro', ['titulo' => 'Sessão inválida', 'mensagem' => 'O formulário expirou. Volte, recarregue a página e tente novamente.'], 419);
        exit;
    }

    $acao = $rotas["$metodo $caminho"] ?? null;
    if (!$acao) {
        view('erro', ['titulo' => 'Página não encontrada', 'mensagem' => 'O endereço acessado não existe.'], 404);
        exit;
    }
    $acao();
} catch (RegraException $e) {
    flash('danger', $e->getMessage());
    voltar('/');
} catch (Throwable $e) {
    $codigo = bin2hex(random_bytes(4));
    error_log("[clinica-agenda][$codigo] $metodo $caminho usuario=" . (usuario()['id'] ?? '-') . ' ' . $e);
    while (ob_get_level()) ob_end_clean();
    view('erro', [
        'titulo' => 'Erro inesperado',
        'mensagem' => (config('debug') ? $e->getMessage() . ' — ' : '') . "Não foi possível concluir agora. Tente novamente mais tarde. (código $codigo)",
    ], 500);
}
