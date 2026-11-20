-- Dados fictícios
USE clinica_agenda;

INSERT INTO especialidade (nome, descricao) VALUES
 ('Clínica Geral',  'Atendimento inicial, check-up e acompanhamento de rotina.'),
 ('Cardiologia',    'Prevenção e acompanhamento de doenças do coração.'),
 ('Dermatologia',   'Cuidados com a pele, cabelos e unhas.'),
 ('Pediatria',      'Acompanhamento da saúde de crianças e adolescentes.');

INSERT INTO profissional (nome, registro_profissional) VALUES
 ('Dra. Ana Ribeiro',   'CRM-RS 00001'),
 ('Dr. Bruno Martins',  'CRM-RS 00002'),
 ('Dra. Carla Souza',   'CRM-RS 00003');

INSERT INTO profissional_especialidade (id_profissional, id_especialidade) VALUES
 (1, 1), (1, 2), (2, 3), (3, 1), (3, 4);

INSERT INTO agenda_regra (id_profissional, dia_semana, hora_inicio, hora_fim, duracao_min, intervalo_min, vigencia_inicio)
SELECT p.id, d.dia, h.ini, h.fim, 30, 10, '2026-01-01'
FROM (SELECT 1 AS id UNION SELECT 2 UNION SELECT 3) p
CROSS JOIN (SELECT 1 AS dia UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
CROSS JOIN (SELECT '08:00:00' AS ini, '12:00:00' AS fim UNION SELECT '13:30:00', '17:30:00') h;
