<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h2 mb-0">Meus agendamentos</h1>
  <a class="btn btn-primary" href="<?= url('/horarios') ?>">Nova consulta</a>
</div>
<?php if (!$agendamentos): ?>
  <div class="painel">Você ainda não tem consultas. <a href="<?= url('/horarios') ?>">Veja os horários livres</a>.</div>
<?php else: ?>
  <div class="painel p-0 overflow-hidden">
    <?php foreach ($agendamentos as $a): $ini = new DateTimeImmutable($a['inicio']); ?>
      <div class="consulta<?= $ini < agora() ? ' passada' : '' ?>">
        <div class="folha" aria-hidden="true">
          <span class="m"><?= mb_substr(MESES[(int)$ini->format('n') - 1], 0, 3) ?></span>
          <span class="d"><?= $ini->format('j') ?></span>
        </div>
        <div class="flex-grow-1">
          <div class="fw-medium"><?= e(data_extenso($ini)) ?>, às <?= $ini->format('H:i') ?></div>
          <div class="text-muted small"><?= e($a['profissional']) ?>, <?= e($a['especialidade']) ?></div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
          <?= badge_situacao($a['situacao']) ?>
          <?php if (AgendamentoService::elegivel($a)): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= url('/agendamentos/reagendar?id=' . (int)$a['id_agendamento']) ?>">Reagendar</a>
            <form method="post" action="<?= url('/agendamentos/cancelar') ?>" data-confirmar
                  data-titulo="Tem certeza que deseja cancelar?" data-botao="Sim, cancelar" data-voltar="Manter consulta" data-tom="perigo"
                  data-detalhe="<?= e(data_extenso($ini) . ', às ' . $ini->format('H:i') . ' com ' . $a['profissional']) ?>"
                  data-aviso="Ao cancelar, o horário volta para a agenda e pode ser marcado por outra pessoa. Se mudar de ideia, ele poderá não estar mais disponível.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id_agendamento'] ?>">
              <button class="btn btn-sm btn-outline-danger">Cancelar</button>
            </form>
          <?php elseif ($a['situacao'] === 'confirmado' && $ini > agora()): ?>
            <span class="small text-muted">Alterações só até <?= (int)config('antecedencia_horas') ?>h antes</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
