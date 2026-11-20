<h1 class="h2 mb-1">Reagendar consulta</h1>
<p class="text-muted mb-4">
  Consulta atual: <strong><?= fmt($consulta['inicio']) ?></strong> com <?= e($consulta['profissional']) ?> (<?= e($consulta['especialidade']) ?>).
  Ela continua valendo até você confirmar o novo horário.
</p>

<div class="row g-4">
  <div class="col-lg-7">
    <?php
    $sel = $data;
    $link = fn(array $p) => url('/agendamentos/reagendar?' . http_build_query(['id' => (int)$consulta['id_agendamento']] + $p));
    require __DIR__ . '/_calendario.php';
    ?>
  </div>
  <div class="col-lg-5" id="horarios">
    <?php if ($horarios === null): ?>
      <div class="painel text-muted"><?= $dias ? 'Selecione um dia no calendário.' : 'Não há horários livres neste mês. Veja o próximo.' ?></div>
    <?php elseif (!$horarios): ?>
      <div class="painel">Não há horários livres neste dia.</div>
    <?php else: ?>
      <div class="painel">
        <h2 class="dia-escolhido"><?= data_extenso(new DateTimeImmutable($data)) ?></h2>
        <div class="horarios">
          <?php foreach ($horarios as $i => $horario): ?>
            <form method="post" action="<?= url('/agendamentos/reagendar') ?>" data-confirmar
                  data-titulo="Confirmar o novo horário?" data-botao="Sim, reagendar" data-voltar="Escolher outro horário"
                  data-detalhe="<?= e(data_extenso($horario['inicio']) . ', às ' . $horario['inicio']->format('H:i') . ' com ' . $consulta['profissional']) ?>"
                  data-aviso="Sua consulta atual (<?= e(fmt($consulta['inicio'])) ?>) será cancelada e o horário dela poderá ser marcado por outra pessoa.">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$consulta['id_agendamento'] ?>">
              <input type="hidden" name="inicio" value="<?= $horario['inicio']->format('Y-m-d H:i') ?>">
              <button class="horario" style="--i:<?= $i ?>"><?= $horario['inicio']->format('H:i') ?></button>
            </form>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
    <p class="mt-3"><a href="<?= url('/meus-agendamentos') ?>">Voltar aos meus agendamentos</a></p>
  </div>
</div>
