<?php foreach ($avisos as $aviso): ?>
  <div class="alert alert-warning" role="alert"><?= e($aviso) ?></div>
<?php endforeach; ?>
<form method="get" class="row g-2 align-items-end bg-white p-3 rounded-3 shadow-sm mb-3">
  <div class="col-sm-6 col-md-2">
    <label for="f-de" class="form-label">De</label>
    <input id="f-de" type="date" name="de" class="form-control" value="<?= e($de) ?>">
  </div>
  <div class="col-sm-6 col-md-2">
    <label for="f-ate" class="form-label">Até</label>
    <input id="f-ate" type="date" name="ate" class="form-control" value="<?= e($ate) ?>">
  </div>
  <div class="col-sm-6 col-md-3">
    <label for="f-prof" class="form-label">Profissional</label>
    <select id="f-prof" name="profissional" class="form-select">
      <option value="">Todos</option>
      <?php foreach ($profissionais as $p): ?>
        <option value="<?= (int)$p['id_profissional'] ?>" <?= $prof === (int)$p['id_profissional'] ? 'selected' : '' ?>><?= e($p['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-sm-6 col-md-3">
    <label for="f-sit" class="form-label">Situação</label>
    <select id="f-sit" name="situacao" class="form-select">
      <option value="">Todas</option>
      <?php foreach (SITUACOES as $k => $rotulo): ?>
        <option value="<?= $k ?>" <?= $situacao === $k ? 'selected' : '' ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
</form>
