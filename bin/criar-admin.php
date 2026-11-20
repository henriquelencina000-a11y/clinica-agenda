<?php
// Uso: php bin/criar-admin.php "Nome" email@exemplo.com
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Somente pela linha de comando.');
require __DIR__ . '/../app/bootstrap.php';

[, $nome, $email] = $argv + [null, '', ''];
$email = mb_strtolower(trim((string)$email));
if (mb_strlen(trim((string)$nome)) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php bin/criar-admin.php \"Nome Completo\" email@exemplo.com\n");
    exit(1);
}
echo 'Senha (8 a 72 caracteres): ';
$senha = rtrim((string)fgets(STDIN), "\r\n");
if (strlen($senha) < 8 || strlen($senha) > 72) {
    fwrite(STDERR, "Senha inválida.\n");
    exit(1);
}
try {
    $id = Usuario::criarAdmin(trim($nome), $email, $senha);
    auditar('criar_admin', "usuario:$id", 'ok', $id);
    echo "Administrador criado (id $id).\n";
} catch (PDOException $e) {
    fwrite(STDERR, $e->getCode() === '23000' ? "Já existe um usuário com este e-mail.\n" : $e->getMessage() . "\n");
    exit(1);
}
