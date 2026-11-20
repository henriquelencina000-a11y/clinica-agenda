<div class="row justify-content-center"><div class="col-md-7 col-lg-5">
  <h1 class="h3 mb-3">4. Revise e confirme</h1>
  <div class="card mb-3"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-5">Especialidade</dt><dd class="col-7"><?= e($nomes['especialidade']) ?></dd>
      <dt class="col-5">Profissional</dt><dd class="col-7"><?= e($nomes['profissional']) ?></dd>
      <dt class="col-5">Data</dt><dd class="col-7"><?= $inicio->format('d/m/Y') ?> (<?= e(DIAS[(int)$inicio->format('w')]) ?>)</dd>
      <dt class="col-5">Horário</dt><dd class="col-7"><?= $inicio->format('H:i') ?></dd>
    </dl>
  </div></div>
  <form method="post" action="<?= url('/agendamentos') ?>" class="d-flex gap-2">
    <?= csrf_field() ?>
    <input type="hidden" name="especialidade" value="<?= (int)$esp ?>">
    <input type="hidden" name="profissional" value="<?= (int)$prof ?>">
    <input type="hidden" name="inicio" value="<?= $inicio->format('Y-m-d H:i') ?>">
    <button class="btn btn-primary">5. Confirmar agendamento</button>
    <a class="btn btn-outline-secondary" href="<?= url('/horarios?' . http_build_query(['especialidade' => $esp, 'profissional' => $prof, 'data' => $inicio->format('Y-m-d')])) ?>">Voltar</a>
  </form>
</div></div>
