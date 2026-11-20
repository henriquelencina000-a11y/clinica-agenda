<div class="row justify-content-center"><div class="col-md-6 col-lg-4">
  <h1 class="h3 mb-3">Entrar</h1>
  <form method="post" action="<?= url('/login') ?>" class="bg-white p-4 rounded-3 shadow-sm">
    <?= csrf_field() ?>
    <?php if ($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
    <div class="mb-3">
      <label for="f-email" class="form-label">E-mail</label>
      <input id="f-email" name="email" type="email" class="form-control" value="<?= e($email) ?>" autocomplete="email" required>
    </div>
    <div class="mb-3">
      <label for="f-senha" class="form-label">Senha</label>
      <input id="f-senha" name="senha" type="password" class="form-control" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary w-100">Entrar</button>
    <p class="small mt-3 mb-0">Ainda não tem conta? <a href="<?= url('/cadastro') ?>">Cadastre-se</a></p>
  </form>
</div></div>
