<?php
declare(strict_types=1);

// Os testes apagam as tabelas: use sempre um banco separado.
if (!getenv('CLINICA_CONFIG')) {
    putenv('CLINICA_CONFIG=' . dirname(__DIR__) . '/app/config.testing.php');
}
if (!is_file(getenv('CLINICA_CONFIG'))) {
    fwrite(STDERR, "Crie app/config.testing.php (copie config.example.php) apontando para o banco clinica_agenda_teste.\n");
    exit(1);
}
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';

if (!str_contains(config('db')['dsn'], 'teste')) {
    fwrite(STDERR, "Recusado: o banco de testes precisa ter 'teste' no nome (os testes apagam os dados).\n");
    exit(1);
}

require __DIR__ . '/Integration/BancoTestCase.php';
