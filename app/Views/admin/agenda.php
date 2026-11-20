<h1 class="h3 mb-3">Agenda e bloqueios</h1>
<form method="get" class="row g-2 align-items-end mb-4">
  <div class="col-sm-8 col-md-5">
    <label for="f-prof" class="form-label">Profissional</label>
    <select id="f-prof" name="profissional" class="form-select" onchange="this.form.submit()">
      <option value="">Selecione…</option>
      <?php foreach ($profissionais as $p): ?>
        <option value="<?= (int)$p['id_profissional'] ?>" <?= $prof === (int)$p['id_profissional'] ? 'selected' : '' ?>><?= e($p['nome']) ?><?= $p['ativo'] ? '' : ' (inativo)' ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-outline-primary">Abrir</button></div>
</form>

<?php if ($prof): ?>
<div class="row g-4">
  <section class="col-lg-6" aria-labelledby="t-regras">
    <h2 id="t-regras" class="h5">Expediente (regras)</h2>
    <div class="table-responsive bg-white rounded-3 shadow-sm mb-3">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th scope="col">Dia</th><th scope="col">Horário</th><th scope="col">Consulta + intervalo</th><th scope="col">Vigência</th><th scope="col"><span class="visually-hidden">Ações</span></th></tr></thead>
        <tbody>
        <?php if (!$regras): ?><tr><td colspan="5" class="text-muted">Sem regras: o profissional não aparece para agendamento.</td></tr><?php endif; ?>
        <?php foreach ($regras as $r): ?>
          <tr>
            <td><?= e(DIAS[(int)$r['dia_semana']]) ?></td>
            <td class="text-nowrap"><?= substr($r['hora_inicio'], 0, 5) ?>–<?= substr($r['hora_fim'], 0, 5) ?></td>
            <td><?= (int)$r['duracao_min'] ?> + <?= (int)$r['intervalo_min'] ?> min
              <?php
              $grade = Disponibilidade::gerar('2000-01-03', [$r], [], new DateTimeImmutable('1999-01-01')); ?>
              <div class="small text-muted">Grade: <?= e(implode(', ', array_map(fn($s) => $s['inicio']->format('H:i'), $grade))) ?></div>
            </td>
            <td class="small"><?= date('d/m/Y', strtotime($r['vigencia_inicio'])) ?> – <?= $r['vigencia_fim'] ? date('d/m/Y', strtotime($r['vigencia_fim'])) : 'sem fim' ?></td>
            <td>
              <form method="post" action="<?= url('/admin/agenda/regra/remover') ?>" onsubmit="return confirm('Remover esta regra?')">
                <?= csrf_field() ?><input type="hidden" name="profissional" value="<?= $prof ?>"><input type="hidden" name="id" value="<?= (int)$r['id_regra'] ?>">
                <button class="btn btn-sm btn-outline-danger">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="<?= url('/admin/agenda/regra') ?>" class="row g-2 bg-white p-3 rounded-3 shadow-sm">
      <?= csrf_field() ?><input type="hidden" name="profissional" value="<?= $prof ?>">
      <div class="col-12 fw-semibold">Nova regra</div>
      <div class="col-6 col-md-4"><label class="form-label small" for="r-dia">Dia</label>
        <select id="r-dia" name="dia_semana" class="form-select"><?php foreach (DIAS as $i => $d): ?><option value="<?= $i ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select></div>
      <div class="col-3 col-md-4"><label class="form-label small" for="r-hi">Início</label><input id="r-hi" type="time" name="hora_inicio" class="form-control" value="08:00" required></div>
      <div class="col-3 col-md-4"><label class="form-label small" for="r-hf">Fim</label><input id="r-hf" type="time" name="hora_fim" class="form-control" value="12:00" required></div>
      <div class="col-6 col-md-3"><label class="form-label small" for="r-dur">Duração (min)</label><input id="r-dur" type="number" name="duracao_min" class="form-control" value="30" min="5" max="480" required></div>
      <div class="col-6 col-md-3"><label class="form-label small" for="r-int">Intervalo (min)</label><input id="r-int" type="number" name="intervalo_min" class="form-control" value="10" min="1" max="240" required></div>
      <div class="col-6 col-md-3"><label class="form-label small" for="r-vi">Vigência início</label><input id="r-vi" type="date" name="vigencia_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
      <div class="col-6 col-md-3"><label class="form-label small" for="r-vf">Vigência fim</label><input id="r-vf" type="date" name="vigencia_fim" class="form-control"></div>
      <div class="col-12"><button class="btn btn-primary">Adicionar regra</button></div>
    </form>
  </section>

  <section class="col-lg-6" aria-labelledby="t-bloq">
    <h2 id="t-bloq" class="h5">Bloqueios futuros (férias, ausências, feriados)</h2>
    <div class="table-responsive bg-white rounded-3 shadow-sm mb-3">
      <table class="table table-sm align-middle mb-0">
        <thead><tr><th scope="col">Início</th><th scope="col">Fim</th><th scope="col">Motivo</th><th scope="col"><span class="visually-hidden">Ações</span></th></tr></thead>
        <tbody>
        <?php if (!$bloqueios): ?><tr><td colspan="4" class="text-muted">Nenhum bloqueio futuro.</td></tr><?php endif; ?>
        <?php foreach ($bloqueios as $b): ?>
          <tr>
            <td><?= fmt($b['inicio']) ?></td><td><?= fmt($b['fim']) ?></td><td><?= e($b['motivo']) ?></td>
            <td>
              <form method="post" action="<?= url('/admin/agenda/bloqueio/remover') ?>" onsubmit="return confirm('Remover este bloqueio?')">
                <?= csrf_field() ?><input type="hidden" name="profissional" value="<?= $prof ?>"><input type="hidden" name="id" value="<?= (int)$b['id_bloqueio'] ?>">
                <button class="btn btn-sm btn-outline-danger">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <form method="post" action="<?= url('/admin/agenda/bloqueio') ?>" class="row g-2 bg-white p-3 rounded-3 shadow-sm">
      <?= csrf_field() ?><input type="hidden" name="profissional" value="<?= $prof ?>">
      <div class="col-12 fw-semibold">Novo bloqueio</div>
      <div class="col-md-6"><label class="form-label small" for="b-ini">Início</label><input id="b-ini" type="datetime-local" name="inicio" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label small" for="b-fim">Fim</label><input id="b-fim" type="datetime-local" name="fim" class="form-control" required></div>
      <div class="col-12"><label class="form-label small" for="b-mot">Motivo</label><input id="b-mot" name="motivo" class="form-control" maxlength="200"></div>
      <div class="col-12 form-check ms-2">
        <input class="form-check-input" type="checkbox" id="b-todos" name="todos" value="1">
        <label class="form-check-label" for="b-todos">Aplicar a todos os profissionais ativos (feriado)</label>
      </div>
      <div class="col-12"><button class="btn btn-primary">Bloquear período</button></div>
    </form>
  </section>
</div>
<?php endif; ?>
