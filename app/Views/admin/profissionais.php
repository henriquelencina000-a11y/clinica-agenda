<h1 class="h3 mb-3">Profissionais</h1>
<p class="small text-muted">Para deixar de ofertar atendimentos, desmarque “Ativo”. O histórico é preservado; consultas futuras precisam ser tratadas antes.</p>
<?php foreach (array_merge($profissionais, [['id_profissional' => 0, 'nome' => '', 'registro_profissional' => '', 'ativo' => 1, 'ids_especialidades' => '']]) as $p):
    $id = (int)$p['id_profissional'];
    $vinculadas = array_map('intval', array_filter(explode(',', (string)$p['ids_especialidades']))); ?>
  <form method="post" action="<?= url('/admin/profissionais') ?>" class="row g-2 align-items-end bg-white p-3 rounded-3 shadow-sm mb-2">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <?php if (!$id): ?><div class="col-12 fw-semibold">Novo profissional</div><?php endif; ?>
    <div class="col-md-3">
      <label class="form-label small" for="n<?= $id ?>">Nome</label>
      <input id="n<?= $id ?>" name="nome" class="form-control" value="<?= e($p['nome']) ?>" maxlength="120" required>
    </div>
    <div class="col-md-2">
      <label class="form-label small" for="r<?= $id ?>">Registro</label>
      <input id="r<?= $id ?>" name="registro" class="form-control" value="<?= e($p['registro_profissional']) ?>" maxlength="40">
    </div>
    <fieldset class="col-md-5">
      <legend class="form-label small mb-1">Especialidades</legend>
      <?php foreach ($especialidades as $x): $eid = (int)$x['id_especialidade']; ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" id="e<?= $id ?>-<?= $eid ?>" name="especialidades[]" value="<?= $eid ?>" <?= in_array($eid, $vinculadas, true) ? 'checked' : '' ?>>
          <label class="form-check-label" for="e<?= $id ?>-<?= $eid ?>"><?= e($x['nome']) ?><?= $x['ativa'] ? '' : ' (inativa)' ?></label>
        </div>
      <?php endforeach; ?>
    </fieldset>
    <div class="col-6 col-md-1 form-check ms-2 mb-2">
      <input id="a<?= $id ?>" class="form-check-input" type="checkbox" name="ativo" value="1" <?= $p['ativo'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="a<?= $id ?>">Ativo</label>
    </div>
    <div class="col-5 col-md-auto"><button class="btn btn-<?= $id ? 'outline-primary' : 'primary' ?>"><?= $id ? 'Salvar' : 'Adicionar' ?></button></div>
  </form>
<?php endforeach; ?>
