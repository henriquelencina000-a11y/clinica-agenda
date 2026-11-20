<h1 class="h2 mb-4">Especialidades</h1>
<?php if (!$especialidades): ?>
  <p class="text-muted">Nenhuma especialidade disponível no momento.</p>
<?php endif; ?>
<div class="row g-3">
  <?php foreach ($especialidades as $esp): $nomes = $profissionais[(int)$esp['id_especialidade']] ?? []; ?>
    <div class="col-md-6">
      <div class="painel h-100">
        <h2 class="h5 mb-1"><?= e($esp['nome']) ?></h2>
        <p class="mb-2"><?= e($esp['descricao']) ?></p>
        <p class="small text-muted mb-0"><?= $nomes ? 'Com ' . e(implode(', ', $nomes)) : 'Nenhum profissional no momento' ?></p>
      </div>
    </div>
  <?php endforeach; ?>
</div>
