<?php $perfil = usuario()['perfil'] ?? null; ?>
<section class="abertura row gx-4 gy-4 gx-lg-5 align-items-center">
  <div class="col-lg-6">
    <h1 class="mb-3">Marque sua consulta sem precisar ligar</h1>
    <p class="lead mb-4">Veja os dias com vaga, escolha o horário e acompanhe tudo pelo celular ou computador.</p>
    <?php if ($perfil === 'admin'): ?>
      <a class="btn btn-primary btn-lg" href="<?= url('/admin/agendamentos') ?>">Abrir painel</a>
    <?php elseif ($perfil): ?>
      <a class="btn btn-primary btn-lg me-2" href="<?= url('/horarios') ?>">Agendar consulta</a>
      <a class="btn btn-outline-primary btn-lg" href="<?= url('/meus-agendamentos') ?>">Meus agendamentos</a>
    <?php else: ?>
      <a class="btn btn-primary btn-lg me-2" href="<?= url('/cadastro') ?>">Criar conta</a>
      <a class="btn btn-outline-primary btn-lg" href="<?= url('/login') ?>">Já tenho conta</a>
    <?php endif; ?>
  </div>
  <div class="col-lg-6">
    <div class="proximos">
      <h2 class="mb-1">Próximos horários livres</h2>
      <?php if (!$proximos): ?>
        <p class="mb-0 mt-3">Nenhum horário livre nos próximos dias.</p>
      <?php endif; ?>
      <?php foreach ($proximos as $item): $p = $item['profissional']; $dia = $item['horarios'][0]['inicio']; ?>
        <div class="proximo">
          <div class="d-flex justify-content-between flex-wrap gap-1">
            <span class="quem"><?= e($p['nome']) ?></span>
            <span class="onde"><?= data_extenso($dia) ?></span>
          </div>
          <div class="onde mb-2"><?= e($p['especialidades']) ?></div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($item['horarios'] as $horario): ?>
              <a class="chip" href="<?= url('/horarios?' . http_build_query([
                  'especialidade' => $item['esp'], 'profissional' => $p['id_profissional'],
                  'data' => $dia->format('Y-m-d'), 'mes' => $dia->format('Y-m')])) ?>#horarios"><?= $horario['inicio']->format('H:i') ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="mt-4" aria-labelledby="t-esp">
  <h2 id="t-esp" class="h4 mb-3">Especialidades atendidas</h2>
  <?php if (!$especialidades): ?>
    <p class="text-muted">Nenhuma especialidade disponível no momento.</p>
  <?php endif; ?>
  <div class="row">
    <?php foreach ($especialidades as $esp): ?>
      <div class="col-md-6">
        <div class="especialidade">
          <h3><?= e($esp['nome']) ?></h3>
          <p class="text-muted mb-0"><?= e($esp['descricao']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
