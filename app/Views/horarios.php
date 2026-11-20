<h1 class="h2 mb-1">Agendar consulta</h1>
<p class="text-muted mb-4">Escolha a especialidade e o profissional. Os dias em verde têm horários livres.</p>

<form method="get" action="<?= url('/horarios') ?>" class="row g-3 align-items-end mb-4">
  <div class="col-md-5">
    <label for="f-esp" class="form-label">Especialidade</label>
    <select id="f-esp" name="especialidade" class="form-select" required onchange="this.form.profissional.value='';this.form.submit()">
      <option value="">Selecione</option>
      <?php foreach ($especialidades as $especialidade): ?>
        <option value="<?= (int)$especialidade['id_especialidade'] ?>" <?= $esp === (int)$especialidade['id_especialidade'] ? 'selected' : '' ?>><?= e($especialidade['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-5">
    <label for="f-prof" class="form-label">Profissional</label>
    <select id="f-prof" name="profissional" class="form-select" onchange="this.form.submit()" <?= $profissionais ? '' : 'disabled' ?>>
      <option value=""><?= $esp && !$profissionais ? 'Nenhum profissional com agenda' : 'Selecione' ?></option>
      <?php foreach ($profissionais as $p): ?>
        <option value="<?= (int)$p['id_profissional'] ?>" <?= $prof === (int)$p['id_profissional'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <noscript><div class="col-md-2"><button class="btn btn-primary w-100">Mostrar agenda</button></div></noscript>
</form>

<?php if ($esp && $prof): ?>
  <div class="row g-4">
    <div class="col-lg-7">
      <?php
      $sel = $data;
      $link = fn(array $p) => url('/horarios?' . http_build_query(['especialidade' => $esp, 'profissional' => $prof] + $p));
      require __DIR__ . '/_calendario.php';
      ?>
    </div>
    <div class="col-lg-5" id="horarios">
      <?php if (!$dias): ?>
        <div class="painel">
          <p class="mb-2">Não há horários livres neste mês.</p>
          <ul class="mb-0">
            <?php if ($alternativas['data']): ?>
              <li><a href="<?= $link(['data' => $alternativas['data'], 'mes' => substr($alternativas['data'], 0, 7)]) ?>#horarios">
                Próxima data livre: <?= data_extenso(new DateTimeImmutable($alternativas['data'])) ?></a></li>
            <?php endif; ?>
            <?php foreach ($alternativas['profissionais'] as $p): ?>
              <li><a href="<?= url('/horarios?' . http_build_query(['especialidade' => $esp, 'profissional' => $p['id_profissional'], 'mes' => $mes->format('Y-m')])) ?>">
                <?= e($p['nome']) ?> tem horários neste mês</a></li>
            <?php endforeach; ?>
            <?php if (!$alternativas['data'] && !$alternativas['profissionais']): ?>
              <li>Tente outra especialidade ou volte mais tarde.</li>
            <?php endif; ?>
          </ul>
        </div>
      <?php elseif ($horarios === null): ?>
        <div class="painel text-muted">Selecione um dia no calendário para ver os horários.</div>
      <?php elseif (!$horarios): ?>
        <div class="painel">Não há horários livres neste dia. Escolha outro dia em verde.</div>
      <?php else: ?>
        <div class="painel">
          <h2 class="dia-escolhido"><?= data_extenso(new DateTimeImmutable($data)) ?></h2>
          <div class="horarios">
            <?php foreach ($horarios as $i => $horario): ?>
              <a class="horario" style="--i:<?= $i ?>" href="<?= url('/agendamentos/confirmar?' . http_build_query([
                  'especialidade' => $esp, 'profissional' => $prof, 'inicio' => $horario['inicio']->format('Y-m-d H:i')])) ?>">
                <?= $horario['inicio']->format('H:i') ?>
              </a>
            <?php endforeach; ?>
          </div>
          <p class="small text-muted mt-3 mb-0">A vaga só é garantida quando você confirmar.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
