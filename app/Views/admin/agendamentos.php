<h1 class="h3 mb-3">Agendamentos</h1>
<?php require __DIR__ . '/_filtros.php'; ?>
<?php if (!$agendamentos): ?>
  <p class="text-muted">Nenhum agendamento encontrado com estes filtros. Ajuste o período, o profissional ou a situação.</p>
<?php else: ?>
  <div class="table-responsive bg-white rounded-3 shadow-sm">
    <table class="table table-sm align-middle mb-0">
      <caption class="visually-hidden">Agendamentos filtrados</caption>
      <thead><tr><th scope="col">Data e hora</th><th scope="col">Paciente</th><th scope="col">Profissional</th><th scope="col">Especialidade</th><th scope="col">Situação</th><th scope="col">Ações</th></tr></thead>
      <tbody>
      <?php foreach ($agendamentos as $a): ?>
        <tr>
          <td><?= fmt($a['inicio']) ?></td>
          <td><?= e($a['paciente']) ?></td>
          <td><?= e($a['profissional']) ?></td>
          <td><?= e($a['especialidade']) ?></td>
          <td><?= badge_situacao($a['situacao']) ?></td>
          <td>
            <?php if ($a['situacao'] === 'confirmado'): $aid = (int)$a['id_agendamento']; ?>
              <form method="post" action="<?= url('/admin/agendamentos/situacao') ?>" class="d-flex flex-wrap gap-1"
                    data-confirmar data-detalhe="<?= e(fmt($a['inicio']) . ' | ' . $a['paciente'] . ' | ' . $a['profissional']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $aid ?>">
                <label class="visually-hidden" for="m<?= $aid ?>">Motivo</label>
                <input id="m<?= $aid ?>" name="motivo" class="form-control form-control-sm w-auto" placeholder="Motivo (opcional)" maxlength="200">
                <?php if (new DateTimeImmutable($a['inicio']) <= agora()): ?>
                  <button name="situacao" value="realizado" class="btn btn-sm btn-outline-success"
                          data-titulo="Marcar consulta como realizada?" data-botao="Sim, marcar realizada"
                          data-aviso="Depois de marcada como realizada, a situação não pode ser alterada.">Realizada</button>
                <?php endif; ?>
                <button name="situacao" value="cancelado" class="btn btn-sm btn-outline-danger"
                        data-titulo="Tem certeza que deseja cancelar esta consulta?" data-botao="Sim, cancelar" data-voltar="Manter consulta" data-tom="perigo"
                        data-aviso="O paciente perde a reserva e o horário volta para a agenda, podendo ser marcado por outra pessoa.">Cancelar</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
