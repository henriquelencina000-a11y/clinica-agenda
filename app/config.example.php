<?php
// Copie para app/config.php.
return [
    'db' => [
        'dsn'     => 'mysql:host=127.0.0.1;port=3306;dbname=clinica_agenda;charset=utf8mb4',
        'usuario' => 'clinica_app',
        'senha'   => 'troque-esta-senha',
    ],
    'timezone'           => 'America/Sao_Paulo',
    'sessao_minutos'     => 30,
    'versao_aviso'       => '1.0',
    'limite_futuros'     => 5,
    'antecedencia_horas' => 24,
    'debug'              => false,
    'forcar_https'       => false,
    'base'               => null, // '' quando o site fica na raiz do domínio
];
