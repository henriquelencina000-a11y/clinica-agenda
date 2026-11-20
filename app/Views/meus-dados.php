<h1 class="h3 mb-3">Meus dados</h1>
<div class="card mb-3" style="max-width: 40rem"><div class="card-body">
  <dl class="row mb-0">
    <dt class="col-sm-5">Nome</dt><dd class="col-sm-7"><?= e($dados['nome']) ?></dd>
    <dt class="col-sm-5">E-mail</dt><dd class="col-sm-7"><?= e($dados['email']) ?></dd>
    <dt class="col-sm-5">Telefone</dt><dd class="col-sm-7"><?= e($dados['telefone'] ?: 'não informado') ?></dd>
    <dt class="col-sm-5">Conta criada em</dt><dd class="col-sm-7"><?= fmt($dados['criado_em']) ?></dd>
    <dt class="col-sm-5">Ciência do aviso de privacidade</dt>
    <dd class="col-sm-7"><?= fmt($dados['consentimento_em']) ?> (versão <?= e($dados['versao_aviso']) ?>)</dd>
  </dl>
</div></div>
<p>Seus agendamentos estão em <a href="<?= url('/meus-agendamentos') ?>">Meus agendamentos</a>.
  Não guardamos prontuários nem diagnósticos.</p>
<p>Para pedir correção, eliminação ou outras informações sobre seus dados (LGPD, art. 18), entre em contato com a clínica.
  Veja o <a href="<?= url('/privacidade') ?>">aviso de privacidade</a>.</p>
