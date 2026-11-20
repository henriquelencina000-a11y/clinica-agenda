<?php
$campo = function (string $nome, string $rotulo, string $tipo = 'text', bool $obrig = true, string $extra = '') use ($d, $erros) {
    $id = "f-$nome"; $err = $erros[$nome] ?? null; ?>
  <div class="mb-3">
    <label for="<?= $id ?>" class="form-label"><?= e($rotulo) ?><?= $obrig ? ' <span aria-hidden="true" class="text-danger">*</span>' : ' <span class="text-muted small">(opcional)</span>' ?></label>
    <input id="<?= $id ?>" name="<?= $nome ?>" type="<?= $tipo ?>" class="form-control<?= $err ? ' is-invalid' : '' ?>"
           value="<?= $tipo === 'password' ? '' : e($d[$nome] ?? '') ?>" <?= $obrig ? 'required' : '' ?> <?= $extra ?>
           <?= $err ? "aria-invalid=\"true\" aria-describedby=\"$id-erro\"" : '' ?>>
    <?php if ($err): ?><div id="<?= $id ?>-erro" class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
  </div>
<?php }; ?>
<div class="row justify-content-center"><div class="col-md-7 col-lg-5">
  <h1 class="h3 mb-3">Criar conta</h1>
  <form method="post" action="<?= url('/cadastro') ?>" novalidate class="bg-white p-4 rounded-3 shadow-sm">
    <?= csrf_field() ?>
    <?php $campo('nome', 'Nome completo', 'text', true, 'autocomplete="name" maxlength="120"'); ?>
    <?php $campo('email', 'E-mail', 'email', true, 'autocomplete="email" maxlength="190"'); ?>
    <?php $campo('telefone', 'Telefone', 'tel', false, 'autocomplete="tel" maxlength="20"'); ?>
    <?php $campo('senha', 'Senha (mínimo 8 caracteres)', 'password', true, 'autocomplete="new-password" minlength="8" maxlength="72"'); ?>
    <?php $campo('senha_confirmacao', 'Confirme a senha', 'password', true, 'autocomplete="new-password"'); ?>
    <div class="form-check mb-3">
      <input class="form-check-input<?= isset($erros['consentimento']) ? ' is-invalid' : '' ?>" type="checkbox" id="f-consentimento" name="consentimento" value="1" required>
      <label class="form-check-label" for="f-consentimento">
        Li e estou ciente do <a href="<?= url('/privacidade') ?>" target="_blank">aviso de privacidade</a> (versão <?= e(config('versao_aviso')) ?>).
      </label>
      <?php if (isset($erros['consentimento'])): ?><div class="invalid-feedback"><?= e($erros['consentimento']) ?></div><?php endif; ?>
    </div>
    <button class="btn btn-primary w-100">Cadastrar</button>
    <p class="small mt-3 mb-0">Já tem conta? <a href="<?= url('/login') ?>">Entrar</a></p>
  </form>
</div></div>
