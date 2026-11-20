CREATE DATABASE IF NOT EXISTS clinica_agenda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinica_agenda;

CREATE TABLE usuario (
  id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  senha_hash    VARCHAR(255) NOT NULL,
  perfil        ENUM('paciente','admin') NOT NULL,
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paciente (
  id_paciente      INT PRIMARY KEY,
  telefone         VARCHAR(20) NULL,
  consentimento_em DATETIME NULL,
  versao_aviso     VARCHAR(20) NOT NULL,
  FOREIGN KEY (id_paciente) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profissional (
  id_profissional       INT AUTO_INCREMENT PRIMARY KEY,
  nome                  VARCHAR(120) NOT NULL,
  registro_profissional VARCHAR(40) NULL,
  ativo                 TINYINT(1) NOT NULL DEFAULT 1,
  criado_em             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE especialidade (
  id_especialidade INT AUTO_INCREMENT PRIMARY KEY,
  nome             VARCHAR(80) NOT NULL UNIQUE,
  descricao        TEXT NULL,
  ativa            TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE profissional_especialidade (
  id_profissional  INT NOT NULL,
  id_especialidade INT NOT NULL,
  ativo            TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_profissional, id_especialidade),
  FOREIGN KEY (id_profissional) REFERENCES profissional(id_profissional),
  FOREIGN KEY (id_especialidade) REFERENCES especialidade(id_especialidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agenda_regra (
  id_regra        INT AUTO_INCREMENT PRIMARY KEY,
  id_profissional INT NOT NULL,
  dia_semana      TINYINT NOT NULL,             -- 0 = domingo ... 6 = sábado
  hora_inicio     TIME NOT NULL,
  hora_fim        TIME NOT NULL,
  duracao_min     SMALLINT NOT NULL,
  intervalo_min   SMALLINT NOT NULL,
  vigencia_inicio DATE NOT NULL,
  vigencia_fim    DATE NULL,
  FOREIGN KEY (id_profissional) REFERENCES profissional(id_profissional),
  CHECK (dia_semana BETWEEN 0 AND 6),
  CHECK (hora_fim > hora_inicio),
  CHECK (duracao_min > 0),
  CHECK (intervalo_min > 0),
  CHECK (vigencia_fim IS NULL OR vigencia_fim >= vigencia_inicio),
  INDEX (id_profissional, dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bloqueio_agenda (
  id_bloqueio     INT AUTO_INCREMENT PRIMARY KEY,
  id_profissional INT NOT NULL,
  inicio          DATETIME NOT NULL,
  fim             DATETIME NOT NULL,
  motivo          VARCHAR(200) NULL,
  criado_por      INT NOT NULL,
  FOREIGN KEY (id_profissional) REFERENCES profissional(id_profissional),
  FOREIGN KEY (criado_por) REFERENCES usuario(id_usuario),
  CHECK (fim > inicio),
  INDEX (id_profissional, inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE agendamento (
  id_agendamento   INT AUTO_INCREMENT PRIMARY KEY,
  id_paciente      INT NOT NULL,
  id_profissional  INT NOT NULL,
  id_especialidade INT NOT NULL,
  inicio           DATETIME NOT NULL,
  fim              DATETIME NOT NULL,
  situacao         ENUM('confirmado','realizado','cancelado') NOT NULL DEFAULT 'confirmado',
  criado_em        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelado_em     DATETIME NULL,
  FOREIGN KEY (id_paciente) REFERENCES paciente(id_paciente),
  FOREIGN KEY (id_profissional) REFERENCES profissional(id_profissional),
  FOREIGN KEY (id_especialidade) REFERENCES especialidade(id_especialidade),
  CHECK (fim > inicio),
  INDEX (id_profissional, inicio),
  INDEX (id_paciente, situacao),
  INDEX (situacao, inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historico_agendamento (
  id_historico      INT AUTO_INCREMENT PRIMARY KEY,
  id_agendamento    INT NOT NULL,
  situacao_anterior VARCHAR(20) NULL,
  situacao_nova     VARCHAR(20) NOT NULL,
  autor             INT NOT NULL,
  data_hora         DATETIME NOT NULL,
  motivo            TEXT NULL,
  FOREIGN KEY (id_agendamento) REFERENCES agendamento(id_agendamento),
  FOREIGN KEY (autor) REFERENCES usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE registro_auditoria (
  id_evento  BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NULL,
  acao       VARCHAR(60) NOT NULL,
  recurso    VARCHAR(190) NOT NULL,
  data_hora  DATETIME NOT NULL,
  resultado  VARCHAR(20) NOT NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  INDEX (recurso, data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
