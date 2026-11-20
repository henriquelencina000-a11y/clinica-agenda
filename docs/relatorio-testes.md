# Relatório de testes — Clínica Agenda

Data da execução: 20/11/2026
Ambiente: Windows 11, PHP 8.3.36, MySQL 8.4.9 (InnoDB), servidor embutido do PHP, Chrome.
Dados: somente fictícios, em banco de testes separado (`clinica_agenda_teste`).

## 1. Testes automatizados (PHPUnit 11)

Resultado: **35 testes, 81 verificações, todos aprovados.**
Saída completa: `testes-automatizados.html` e `testes-automatizados.txt` nesta pasta.
Para repetir: `vendor/bin/phpunit`.

| ID | Cenário | Resultado esperado | Resultado observado | Situação |
|---|---|---|---|---|
| T01 | Visitante consulta especialidades ativas | Lista só itens ativos, sem login | Especialidade inativa não aparece; página pública abre sem login | Aprovado |
| T02 | Cadastro com e-mail já existente | Cadastro recusado | Segundo cadastro recusado pelo índice único; um único registro gravado | Aprovado |
| T03 | Login com senha incorreta | Acesso negado, mensagem genérica | HTTP 401, "Não foi possível autenticar…", tentativa registrada sem e-mail ou senha no log | Aprovado |
| T04 | Paciente tenta abrir consulta de outro | Acesso negado, evento registrado | Redirecionado com "Acesso negado", consulta alheia intacta, 2 eventos `acesso_negado` na auditoria | Aprovado |
| T05 | Agendamento em horário livre | Reserva confirmada e visível | Situação "confirmado", aparece em "Meus agendamentos", some da grade | Aprovado |
| T06 | 8 pacientes reservam o mesmo horário ao mesmo tempo | Só uma reserva confirmada | 1 "ok", 7 "recusado", 1 registro no banco | Aprovado |
| T07 | Consulta fora do expediente | Não ofertado e recusado no servidor | 07:00, 12:00 e 08:10 ausentes da grade e recusados | Aprovado |
| T08 | Cancelamento com 24h ou mais | Cancelada e horário liberado | Situação "cancelado", horário volta à grade, histórico gravado, repetição não altera nada | Aprovado |
| T09 | Cancelamento com menos de 24h | Recusado | Operação recusada, consulta continua confirmada | Aprovado |
| T10 | Reagendamento para horário ocupado | Original permanece confirmada | Recusado; paciente mantém exatamente 1 consulta confirmada | Aprovado |
| T11 | Inativar profissional com consultas futuras | Alerta e registros preservados | Recusado com a lista das consultas afetadas; profissional continua ativo | Aprovado |
| T12 | Relatório filtrado | Resultado conforme filtros | Totais corretos por situação e por profissional | Aprovado |
| T13 | Navegação sem mouse | Teclado, foco visível, ordem lógica | Ver seção 3 | Aprovado com correção |
| T14 | Tentativa de injeção SQL | Conteúdo não executado | `' OR '1'='1` não autentica; texto com `DROP TABLE` gravado literalmente | Aprovado |
| T15 | Erro durante gravação | Rollback | Nenhum registro parcial após falha simulada | Aprovado |

Também aprovados: RN01 (limite de 5), RN05/RN06 (grade, bloqueio parcial, sobreposição), RN09 (bloqueio sobre consulta),
feriado para todos os profissionais, regras de agenda inválidas, UC13 (autor e motivo no histórico),
paciente barrado na área administrativa (HTTP 403), CSRF (HTTP 419), retorno à página pedida após login,
reagendamento válido e calendário mensal coerente com a grade do dia.

## 2. Desempenho — RNF01 (meta: até 3 s)

Dez requisições por operação, ambiente local:

| Operação | Média | Pior caso |
|---|---|---|
| Login | 0,061 s | 0,080 s |
| Página inicial (próximos horários) | 0,012 s | 0,033 s |
| Calendário do mês | 0,007 s | 0,007 s |
| Calendário + horários do dia | 0,007 s | 0,008 s |
| Meus agendamentos | 0,007 s | 0,020 s |
| Confirmar agendamento | 0,011 s | — |
| Admin: agendamentos | 0,008 s | 0,008 s |
| Admin: relatório anual | 0,011 s | 0,024 s |

Concorrência (T06): 8 reservas simultâneas concluídas em cerca de 0,5 s.
Os tempos devem ser medidos novamente no ambiente publicado.

## 3. Verificações manuais

**Acessibilidade (T13):** ordem de tabulação lógica (pular para o conteúdo → menu → formulário → rodapé);
foco visível com contorno âmbar de 3 px; rótulos associados a todos os campos; calendário com descrição
por dia para leitores de tela ("24 de novembro, 12 horários livres"); animações desligadas quando o sistema pede menos movimento.
Defeito encontrado e corrigido: o link "Pular para o conteúdo" ficava escondido atrás da barra superior ao receber foco.

**Responsividade (RNF03), largura de 390 px:** início, agendamento com calendário e "Meus agendamentos"
sem rolagem horizontal. Defeito encontrado e corrigido: o espaçamento das colunas da página inicial ultrapassava a tela em 12 px.

**Revisão visual:** todas as telas de paciente e de administrador revisadas no Chrome.
Defeitos encontrados e corrigidos: horários do dia errado no painel ao lado do calendário;
coluna "Horário" quebrando linha na tela de agenda do administrador.

## 4. Ambiente publicado

Endereço: https://clinica-agenda.freehosting.dev (InfinityFree, Apache, PHP 8.x, MySQL, banco em utf8mb4).

| Verificação | Resultado |
|---|---|
| Páginas públicas | Todas responderam 200, em cerca de 0,25 s (RNF01) |
| Áreas restritas sem login | Redirecionam para o login |
| Arquivos internos (`/app/config.php`) | Bloqueados (403) |
| `http://` | Redireciona para `https://`; cabeçalho HSTS ativo (RNF10) |
| Página inexistente | 404 |
| Login de paciente: agendar e cancelar | Aprovado (teste manual) |
| Login de administrador: agendamentos, agenda e relatórios | Aprovado (teste manual) |

## 5. Pendências

- Testes nos navegadores Firefox e Safari.
- Teste com leitor de tela real (NVDA ou similar).
- Teste de backup e restauração (Apêndice N).
