<?php
$u = usuario();
$atual = $GLOBALS['caminho'] ?? '/';
$item = fn(string $href, string $rotulo) => '<li class="nav-item"><a class="nav-link' . ($atual === $href ? ' active" aria-current="page' : '')
    . '" href="' . url($href) . '">' . $rotulo . '</a></li>';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Início') ?> | Clínica Agenda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lexend:wght@300..700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= url('/assets/app.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<a class="visually-hidden-focusable position-absolute p-2 bg-white" href="#conteudo">Pular para o conteúdo</a>
<nav class="navbar navbar-expand-lg navbar-dark topo" aria-label="Menu principal">
  <div class="container">
    <a class="navbar-brand" href="<?= url('/') ?>">
      <span class="marca" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
          <rect x="2" y="3" width="12" height="11" rx="2"/><path d="M2 7h12M5.5 1.5v3M10.5 1.5v3"/>
        </svg>
      </span>
      Clínica Agenda
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-controls="menu" aria-expanded="false" aria-label="Abrir menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <?php if ($u && $u['perfil'] === 'admin'): ?>
          <?= $item('/admin/agendamentos', 'Agendamentos') ?>
          <?= $item('/admin/agenda', 'Agenda') ?>
          <?= $item('/admin/profissionais', 'Profissionais') ?>
          <?= $item('/admin/especialidades', 'Especialidades') ?>
          <?= $item('/admin/relatorios', 'Relatórios') ?>
        <?php else: ?>
          <?= $item('/especialidades', 'Especialidades') ?>
          <?= $item('/profissionais', 'Profissionais') ?>
          <?php if ($u): ?>
            <?= $item('/horarios', 'Agendar consulta') ?>
            <?= $item('/meus-agendamentos', 'Meus agendamentos') ?>
            <?= $item('/meus-dados', 'Meus dados') ?>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
      <?php if ($u): ?>
        <span class="navbar-text text-white me-3">Olá, <?= e(explode(' ', $u['nome'])[0]) ?></span>
        <form method="post" action="<?= url('/logout') ?>" class="d-inline"><?= csrf_field() ?>
          <button class="btn btn-outline-light btn-sm">Sair</button>
        </form>
      <?php else: ?>
        <a class="btn btn-outline-light btn-sm me-2" href="<?= url('/login') ?>">Entrar</a>
        <a class="btn btn-light btn-sm" href="<?= url('/cadastro') ?>">Criar conta</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main id="conteudo" class="container py-4 flex-grow-1">
  <div aria-live="polite">
    <?php foreach (flash() as [$tipo, $msg]): ?>
      <div class="alert alert-<?= e($tipo) ?>" role="alert"><?= e($msg) ?></div>
    <?php endforeach; ?>
  </div>
  <?= $conteudo ?>
</main>

<footer class="py-4 small">
  <div class="container d-flex justify-content-between flex-wrap gap-2">
    <span>Clínica Agenda, <?= date('Y') ?></span>
    <a href="<?= url('/privacidade') ?>">Aviso de privacidade</a>
  </div>
</footer>
<div class="modal fade" id="modal-confirmar" tabindex="-1" aria-labelledby="confirmar-titulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content aviso-confirmar">
      <div class="modal-body">
        <span class="aviso-icone" aria-hidden="true">!</span>
        <h2 class="h4 mt-3 mb-2" id="confirmar-titulo" data-campo="titulo"></h2>
        <p class="fw-medium mb-3" data-campo="detalhe"></p>
        <p class="aviso-texto mb-0" data-campo="aviso"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal" data-campo="voltar">Voltar</button>
        <button type="button" class="btn" data-campo="botao"></button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
  const el = document.getElementById('modal-confirmar');
  const modal = new bootstrap.Modal(el);
  const campo = n => el.querySelector(`[data-campo=${n}]`);
  let pendente = null;
  document.addEventListener('submit', ev => {
    const form = ev.target;
    if (!('confirmar' in form.dataset) || form.dataset.ok) return;
    ev.preventDefault();
    const d = { ...form.dataset, ...(ev.submitter ? ev.submitter.dataset : {}) };
    campo('titulo').textContent = d.titulo || 'Confirmar?';
    campo('detalhe').textContent = d.detalhe || '';
    campo('aviso').textContent = d.aviso || '';
    campo('aviso').hidden = !d.aviso;
    campo('botao').textContent = d.botao || 'Confirmar';
    campo('voltar').textContent = d.voltar || 'Voltar';
    campo('botao').className = 'btn ' + (d.tom === 'perigo' ? 'btn-danger' : 'btn-primary');
    el.classList.toggle('perigo', d.tom === 'perigo');
    pendente = [form, ev.submitter];
    modal.show();
  });
  campo('botao').addEventListener('click', () => {
    const [form, botao] = pendente;
    form.dataset.ok = '1';
    modal.hide();
    form.requestSubmit(botao || undefined);
  });
})();
</script>
</body>
</html>
