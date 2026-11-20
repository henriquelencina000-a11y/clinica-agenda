<?php
declare(strict_types=1);

$arquivoConfig = getenv('CLINICA_CONFIG') ?: __DIR__ . '/config.php';
if (!is_file($arquivoConfig)) {
    exit('Configuração ausente: copie app/config.example.php para app/config.php.');
}
$GLOBALS['CONFIG'] = require $arquivoConfig;
date_default_timezone_set($GLOBALS['CONFIG']['timezone']);

spl_autoload_register(function (string $classe): void {
    foreach (['Controllers', 'Services', 'Models'] as $pasta) {
        $arquivo = __DIR__ . "/$pasta/$classe.php";
        if (is_file($arquivo)) { require $arquivo; return; }
    }
});

require __DIR__ . '/helpers.php';
