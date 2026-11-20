<h1 class="h3 mb-3">Especialidades</h1>
<?php foreach (array_merge($especialidades, [['id_especialidade' => 0, 'nome' => '', 'descricao' => '', 'ativa' => 1]]) as $x): $id = (int)$x['id_especialidade']; ?>
  <form method="post" action="<?= url('/admin/especialidades') ?>" class="row g-2 align-items-end bg-white p-3 rounded-3 shadow-sm mb-2">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <?php if (!$id): ?><div class="col-12 fw-semibold">Nova especialidade</div><?php endif; ?>
    <div class="col-md-3">
      <label class="form-label small" for="n<?= $id ?>">Nome</label>
      <input id="n<?= $id ?>" name="nome" class="form-control" value="<?= e($x['nome']) ?>" maxlength="80" required>
    </div>
    <div class="col-md-6">
      <label class="form-label small" for="d<?= $id ?>">Descrição</label>
      <input id="d<?= $id ?>" name="descricao" class="form-control" value="<?= e($x['descricao']) ?>" maxlength="1000">
    </div>
    <div class="col-6 col-md-1 form-check ms-2 mb-2">
      <input id="a<?= $id ?>" class="form-check-input" type="checkbox" name="ativa" value="1" <?= $x['ativa'] ? 'checked' : '' ?>>
      <label class="form-check-label" for="a<?= $id ?>">Ativa</label>
    </div>
    <div class="col-5 col-md-1"><button class="btn btn-<?= $id ? 'outline-primary' : 'primary' ?> w-100"><?= $id ? 'Salvar' : 'Adicionar' ?></button></div>
  </form>
<?php endforeach; ?>
