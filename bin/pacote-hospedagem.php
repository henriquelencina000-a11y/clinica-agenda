<?php
// Uso: php bin/pacote-hospedagem.php [pasta-de-saida]
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Somente pela linha de comando.');

$raiz = dirname(__DIR__);
$saida = $argv[1] ?? dirname($raiz) . '/clinica-agenda-publicacao';
$htdocs = "$saida/htdocs";

function copiar(string $de, string $para, array $ignorar = []): void
{
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($de, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $item) {
        $rel = substr($item->getPathname(), strlen($de) + 1);
        if (in_array(str_replace('\\', '/', $rel), $ignorar, true)) continue;
        $destino = "$para/$rel";
        $item->isDir() ? @mkdir($destino, 0775, true) : copy($item->getPathname(), $destino);
    }
}

function apagar(string $dir): void
{
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    rmdir($dir);
}

function senha(): string
{
    $c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 12; $i++) $s .= $c[random_int(0, strlen($c) - 1)];
    return $s;
}

apagar($saida);
mkdir("$htdocs/app", 0775, true);
mkdir("$htdocs/public", 0775, true);
copiar("$raiz/app", "$htdocs/app", ['config.php', 'config.testing.php']);
copiar("$raiz/public", "$htdocs/public");

file_put_contents("$htdocs/.htaccess", <<<'HT'
Options -Indexes
RewriteEngine On
RewriteRule ^(app|database|bin|tests|vendor|docs)(/|$) - [F,L]
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]

HT);
file_put_contents("$htdocs/app/.htaccess", "Require all denied\n");

$config = file_get_contents("$raiz/app/config.example.php");
$config = str_replace(
    ["'mysql:host=127.0.0.1;port=3306;dbname=clinica_agenda;charset=utf8mb4'", "'clinica_app'", "'troque-esta-senha'", "'base'               => null"],
    ["'mysql:host=HOST_DO_MYSQL;dbname=NOME_DO_BANCO;charset=utf8mb4'", "'USUARIO_DO_MYSQL'", "'SENHA_DO_MYSQL'", "'base'               => ''"],
    $config
);
file_put_contents("$htdocs/app/config.php", $config);

$admin = senha();
$paciente = senha();
$sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b.*$/mi', '', file_get_contents("$raiz/database/schema.sql") . "\n" . file_get_contents("$raiz/database/seed.sql"));
$hAdmin = password_hash($admin, PASSWORD_DEFAULT);
$hPaciente = password_hash($paciente, PASSWORD_DEFAULT);
$sql .= <<<SQL

-- Contas de demonstração
INSERT INTO usuario (nome, email, senha_hash, perfil) VALUES ('Administrador', 'admin@clinicaagenda.demo', '$hAdmin', 'admin');
INSERT INTO usuario (nome, email, senha_hash, perfil) VALUES ('Paciente Demonstração', 'paciente@clinicaagenda.demo', '$hPaciente', 'paciente');
INSERT INTO paciente (id_paciente, telefone, consentimento_em, versao_aviso) VALUES (LAST_INSERT_ID(), NULL, NOW(), '1.0');

SQL;
file_put_contents("$saida/banco-hospedagem.sql", ltrim($sql));

file_put_contents("$saida/logins-demonstracao.txt",
    "Administrador\n  e-mail: admin@clinicaagenda.demo\n  senha:  $admin\n\n" .
    "Paciente\n  e-mail: paciente@clinicaagenda.demo\n  senha:  $paciente\n\n" .
    "Estas senhas valem para o banco-hospedagem.sql gerado junto com este arquivo.\n");

$zip = new ZipArchive();
$zip->open("$saida/htdocs.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($htdocs, FilesystemIterator::SKIP_DOTS));
foreach ($it as $arq) {
    $zip->addFile($arq->getPathname(), str_replace('\\', '/', substr($arq->getPathname(), strlen($htdocs) + 1)));
}
$zip->close();

echo "Pacote gerado em $saida\n";
