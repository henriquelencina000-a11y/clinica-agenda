<?php
$calIni = $mes->modify('first day of this month');
$calFim = $calIni->modify('+1 month');
$calHoje = date('Y-m-d');
$calVoltar = $calIni > agora()->modify('first day of this month')->setTime(0, 0);
$calMes = MESES[(int)$calIni->format('n') - 1];
?>
<div class="cal">
  <div class="cal-topo">
    <a class="btn btn-icone<?= $calVoltar ? '' : ' disabled' ?>" href="<?= $link(['mes' => $calIni->modify('-1 month')->format('Y-m')]) ?>"
       aria-label="Mês anterior"<?= $calVoltar ? '' : ' aria-disabled="true" tabindex="-1"' ?>>&lsaquo;</a>
    <h2 class="cal-mes" id="cal-titulo"><?= mb_convert_case($calMes, MB_CASE_TITLE) ?> de <?= $calIni->format('Y') ?></h2>
    <a class="btn btn-icone" href="<?= $link(['mes' => $calFim->format('Y-m')]) ?>" aria-label="Próximo mês">&rsaquo;</a>
  </div>
  <div class="cal-grade" aria-labelledby="cal-titulo">
    <?php foreach (['D', 'S', 'T', 'Q', 'Q', 'S', 'S'] as $calSem): ?>
      <span class="cal-sem" aria-hidden="true"><?= $calSem ?></span>
    <?php endforeach; ?>
    <?php for ($calI = 0; $calI < (int)$calIni->format('w'); $calI++): ?><span></span><?php endfor; ?>
    <?php for ($calD = $calIni; $calD < $calFim; $calD = $calD->modify('+1 day')):
        $calData = $calD->format('Y-m-d');
        $calN = $dias[$calData] ?? 0;
        $calClasse = 'cal-dia' . ($calN ? ' livre' : '') . ($calData === $calHoje ? ' hoje' : '') . ($calData === $sel ? ' sel' : ''); ?>
      <?php if ($calN): ?>
        <a class="<?= $calClasse ?>" href="<?= $link(['data' => $calData, 'mes' => $calIni->format('Y-m')]) ?>#horarios"
           aria-label="<?= $calD->format('j') ?> de <?= $calMes ?>, <?= $calN ?> <?= $calN > 1 ? 'horários livres' : 'horário livre' ?>"<?= $calData === $sel ? ' aria-current="date"' : '' ?>>
          <span class="num"><?= $calD->format('j') ?></span>
          <span class="qtd"><?= $calN ?> <?= $calN > 1 ? 'livres' : 'livre' ?></span>
        </a>
      <?php else: ?>
        <span class="<?= $calClasse ?>" aria-hidden="true"><span class="num"><?= $calD->format('j') ?></span></span>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <div class="cal-legenda"><span class="l-livre">Com horários</span><span class="l-hoje">Hoje</span></div>
</div>
