# Clínica Agenda

Sistema web para agendamento de consultas em uma clínica: o paciente consulta especialidades e profissionais,
vê os dias com horários livres, agenda, cancela e reagenda; o administrador mantém profissionais,
especialidades, expediente, bloqueios e emite relatórios.

Projeto Tecnológico em Desenvolvimento de Sistemas — ULBRA
Aluno: Henrique Candaten Lencina

**Versão publicada:** https://clinica-agenda.freehosting.dev
(os logins de demonstração estão no documento de implantação)

## Tecnologias

| Tecnologia | Versão |
|---|---|
| PHP (sem framework, padrão MVC) | 8.3 no desenvolvimento; funciona a partir do 8.0 |
| MySQL com InnoDB | 8.4 (também funciona em MariaDB 10.4 ou superior) |
| Bootstrap | 5.3.3 |
| HTML5, CSS3 e JavaScript | sem bibliotecas além do Bootstrap |
| PHPUnit | 11.5 |

## Executar no próprio computador

O jeito mais simples no Windows é usar o XAMPP, que já traz PHP e MySQL.

### 1. Instalar

Instale o XAMPP (https://www.apachefriends.org) com PHP 8.0 ou superior.
Abra o **XAMPP Control Panel** e clique em **Start** no **MySQL**.

### 2. Criar o banco

1. Clique em **Admin** na linha do MySQL (abre o phpMyAdmin).
2. Na aba **Importar**, importe `database/schema.sql` (cria o banco `clinica_agenda` e as tabelas).
3. Em seguida, importe `database/seed.sql` (especialidades, profissionais e horários de exemplo).

### 3. Configurar

Copie `app/config.example.php` para `app/config.php` e ajuste o acesso ao banco.
No XAMPP, o usuário padrão é `root` sem senha:

```php
'dsn'     => 'mysql:host=127.0.0.1;port=3306;dbname=clinica_agenda;charset=utf8mb4',
'usuario' => 'root',
'senha'   => '',
```

### 4. Criar um administrador

Abra o terminal (Prompt de Comando) na pasta do projeto e rode:

```
C:\xampp\php\php.exe bin\criar-admin.php "Seu Nome" admin@exemplo.com
```

O comando pede a senha (mínimo de 8 caracteres).
Contas de paciente são criadas pelo próprio site, em **Criar conta**.

### 5. Iniciar o site

Na mesma pasta, rode:

```
C:\xampp\php\php.exe -S localhost:8000 -t public public\index.php
```

Abra **http://localhost:8000** no navegador. Para parar, feche o terminal ou use Ctrl+C.

## Testes automatizados (opcional)

Os testes usam um banco separado, porque apagam e recriam as tabelas a cada execução.

1. Instale o Composer (https://getcomposer.org) e rode `composer install` na pasta do projeto.
2. No phpMyAdmin, crie um banco vazio chamado `clinica_agenda_teste`.
3. Copie `app/config.example.php` para `app/config.testing.php`, com `dbname=clinica_agenda_teste` e o mesmo usuário do passo 3.
4. Rode `C:\xampp\php\php.exe vendor\bin\phpunit`.

O resultado da última execução e o relatório completo estão em `docs/`.

## Orientações para análise do código

Todas as requisições passam por `public/index.php`, que define as rotas, confere o token CSRF e trata os erros.
Os pontos principais das regras de negócio estão em:

- `app/Services/Disponibilidade.php`: monta a grade de horários a partir do expediente, da duração,
  do intervalo, dos bloqueios e das consultas já marcadas, e calcula os dias livres do calendário.
- `app/Services/AgendamentoService.php`: agendar, cancelar, reagendar e mudar a situação.
  Cada operação roda numa transação que bloqueia (`SELECT ... FOR UPDATE`) o paciente e o profissional
  antes de conferir a disponibilidade, o que impede duas reservas no mesmo horário.
- `app/Services/AgendaService.php`: manutenção de regras de expediente, bloqueios, profissionais
  e especialidades, recusando alterações que afetariam consultas confirmadas.
- `app/helpers.php`: conexão com o banco, sessão, controle de acesso por perfil (`exigir()`) e auditoria.
- `database/schema.sql`: tabelas, chaves estrangeiras e restrições de integridade.
- `tests/`: os testes seguem os casos T01 a T15 do plano de testes do projeto;
  `tests/Integration/ConcorrenciaTest.php` dispara oito reservas simultâneas para o mesmo horário.

## Estrutura

```
app/Controllers   recebem as requisições e verificam o perfil do usuário
app/Services      regras de negócio: grade de horários, agendamento, cancelamento, reagendamento
app/Models        acesso ao banco (consultas parametrizadas e transações)
app/Views         telas
public/           ponto de entrada (index.php), .htaccess e CSS
database/         script de criação do banco e dados de exemplo
bin/              criar administrador e gerar pacote para hospedagem
tests/            testes de unidade, integração e concorrência
docs/             relatório de testes
```
