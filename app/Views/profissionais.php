<h1 class="h2 mb-4">Profissionais</h1>
<?php if (!$profissionais): ?>
  <p class="text-muted">Nenhum profissional disponível no momento.</p>
<?php endif; ?>
<div class="row g-3">
  <?php foreach ($profissionais as $p):
      $partes = array_values(array_filter(explode(' ', $p['nome']), fn($w) => !in_array($w, ['Dr.', 'Dra.'], true)));
      $iniciais = mb_substr($partes[0] ?? '', 0, 1) . mb_substr(end($partes) ?: '', 0, 1);
      $ids = array_filter(explode(',', (string)$p['ids_especialidades']));
      $nomes = explode(', ', (string)$p['especialidades']); ?>
    <div class="col-md-6 col-lg-4">
      <div class="painel h-100 d-flex gap-3">
        <span class="monograma" aria-hidden="true"><?= e($iniciais) ?></span>
        <div>
          <h2 class="h5 mb-2"><?= e($p['nome']) ?></h2>
          <?php if (!$ids): ?><p class="text-muted mb-0">Sem especialidade vinculada</p><?php endif; ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($ids as $i => $eid): ?>
              <li class="mb-1"><?= e($nomes[$i] ?? '') ?>:
                <a href="<?= url('/horarios?' . http_build_query(['especialidade' => (int)$eid, 'profissional' => (int)$p['id_profissional']])) ?>">ver agenda</a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
