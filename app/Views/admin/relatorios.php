<h1 class="h3 mb-3">Relatórios</h1>
<?php require __DIR__ . '/_filtros.php'; ?>
<?php if (!$linhas): ?>
  <p class="text-muted">Nenhum agendamento encontrado com estes filtros. Ajuste o período, o profissional ou a situação.</p>
<?php else: ?>
  <?php $tot = ['confirmados' => 0, 'realizados' => 0, 'cancelados' => 0, 'total' => 0]; ?>
  <div class="table-responsive bg-white rounded-3 shadow-sm">
    <table class="table mb-0">
      <caption class="px-3">Consultas de <?= date('d/m/Y', strtotime($de)) ?> a <?= date('d/m/Y', strtotime($ate)) ?>, por profissional</caption>
      <thead><tr><th scope="col">Profissional</th><th scope="col" class="text-end">Confirmadas</th><th scope="col" class="text-end">Realizadas</th><th scope="col" class="text-end">Canceladas</th><th scope="col" class="text-end">Total</th></tr></thead>
      <tbody>
      <?php foreach ($linhas as $l): foreach ($tot as $k => $_) $tot[$k] += (int)$l[$k]; ?>
        <tr>
          <td><?= e($l['profissional']) ?></td>
          <td class="text-end"><?= (int)$l['confirmados'] ?></td>
          <td class="text-end"><?= (int)$l['realizados'] ?></td>
          <td class="text-end"><?= (int)$l['cancelados'] ?></td>
          <td class="text-end fw-semibold"><?= (int)$l['total'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr class="fw-semibold"><th scope="row">Total</th>
        <td class="text-end"><?= $tot['confirmados'] ?></td><td class="text-end"><?= $tot['realizados'] ?></td>
        <td class="text-end"><?= $tot['cancelados'] ?></td><td class="text-end"><?= $tot['total'] ?></td></tr></tfoot>
    </table>
  </div>

  <h2 class="h5 mt-4">Listagem</h2>
  <div class="table-responsive bg-white rounded-3 shadow-sm">
    <table class="table table-sm mb-0">
      <caption class="px-3">Consultas do período (sem dados pessoais de pacientes<?= count($lista) >= 500 ? '; exibindo as 500 primeiras' : '' ?>)</caption>
      <thead><tr><th scope="col">Data e hora</th><th scope="col">Profissional</th><th scope="col">Especialidade</th><th scope="col">Situação</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $a): ?>
        <tr><td><?= fmt($a['inicio']) ?></td><td><?= e($a['profissional']) ?></td><td><?= e($a['especialidade']) ?></td><td><?= badge_situacao($a['situacao']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
