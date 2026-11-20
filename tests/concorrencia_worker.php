<?php
// Usado pelo ConcorrenciaTest.
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

[, $paciente, $inicio, $largada] = $argv;
while (microtime(true) < (float)$largada) usleep(500);
try {
    AgendamentoService::agendar((int)$paciente, 1, 1, $inicio);
    echo 'ok';
} catch (RegraException) {
    echo 'recusado';
}
