<?php
declare(strict_types=1);

final class AgendamentoTest extends BancoTestCase
{
    public function testT01VisitanteVeApenasEspecialidadesAtivas(): void
    {
        q("UPDATE especialidade SET ativa = 0 WHERE nome = 'Pediatria'");
        $nomes = array_column(Catalogo::especialidades(), 'nome');
        $this->assertNotContains('Pediatria', $nomes);
        $this->assertContains('Cardiologia', $nomes);
    }

    public function testT02CadastroComEmailExistenteERecusado(): void
    {
        Usuario::criarPaciente('Fulano', 'repetido@teste.local', 'senha-teste-123', '');
        try {
            Usuario::criarPaciente('Outro', 'repetido@teste.local', 'senha-teste-123', '');
            $this->fail('Deveria recusar e-mail duplicado.');
        } catch (PDOException $e) {
            $this->assertSame('23000', $e->getCode());
        }
        $this->assertSame(1, self::contar("SELECT COUNT(*) FROM usuario WHERE email = 'repetido@teste.local'"));
    }

    public function testSenhaGuardadaSomenteComoHash(): void
    {
        $id = self::novoPaciente();
        $hash = (string)q('SELECT senha_hash FROM usuario WHERE id_usuario = ?', [$id])->fetchColumn();
        $this->assertNotSame('senha-teste-123', $hash);
        $this->assertTrue(password_verify('senha-teste-123', $hash));
    }

    public function testT05AgendamentoEmHorarioLivreApareceEmMeusAgendamentos(): void
    {
        $p = self::novoPaciente();
        $id = AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 08:00');
        $meus = Agendamento::doPaciente($p);
        $this->assertCount(1, $meus);
        $this->assertSame($id, (int)$meus[0]['id_agendamento']);
        $this->assertSame('confirmado', $meus[0]['situacao']);
        $this->assertNotContains('08:00', array_map(fn($s) => $s['inicio']->format('H:i'), Disponibilidade::livres(1, self::segunda())));
    }

    public function testCalendarioDoMesBateComHorariosDoDia(): void
    {
        $seg = self::segunda();
        AgendamentoService::agendar(self::novoPaciente(), 1, 1, "$seg 08:00");
        $mes = Disponibilidade::porMes(1, new DateTimeImmutable($seg));
        $this->assertSame(count(Disponibilidade::livres(1, $seg)), $mes[$seg]);
        $this->assertSame(11, $mes[$seg]);
        $domingo = (new DateTimeImmutable($seg))->modify('-1 day')->format('Y-m-d');
        $this->assertArrayNotHasKey($domingo, $mes);
    }

    public function testT07HorarioForaDoExpedienteNaoEOfertadoNemAceito(): void
    {
        $horas = array_map(fn($s) => $s['inicio']->format('H:i'), Disponibilidade::livres(1, self::segunda()));
        $this->assertNotContains('07:00', $horas);
        $this->assertNotContains('12:00', $horas);
        foreach (['07:00', '12:00', '08:10'] as $h) {
            try {
                AgendamentoService::agendar(self::novoPaciente(), 1, 1, self::segunda() . " $h");
                $this->fail("Reserva às $h deveria ser recusada.");
            } catch (RegraException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, self::contar('SELECT COUNT(*) FROM agendamento'));
    }

    public function testT08CancelamentoComAntecedenciaLiberaOHorario(): void
    {
        $p = self::novoPaciente();
        $id = AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 08:00');
        AgendamentoService::cancelar($p, $id);
        $this->assertSame('cancelado', self::situacao($id));
        $this->assertContains('08:00', array_map(fn($s) => $s['inicio']->format('H:i'), Disponibilidade::livres(1, self::segunda())));
        $this->assertSame(2, self::contar('SELECT COUNT(*) FROM historico_agendamento WHERE id_agendamento = ?', [$id]));

        $this->assertSame('Esta consulta já estava cancelada.', AgendamentoService::cancelar($p, $id));
    }

    public function testT09CancelamentoComMenosDe24hERecusado(): void
    {
        $p = self::novoPaciente();
        $ini = agora()->modify('+2 hours');
        $id = Agendamento::criar($p, 1, 1, $ini, $ini->modify('+30 minutes'));
        $this->expectException(RegraException::class);
        try {
            AgendamentoService::cancelar($p, $id);
        } finally {
            $this->assertSame('confirmado', self::situacao($id));
        }
    }

    public function testT10ReagendamentoParaHorarioOcupadoMantemOriginal(): void
    {
        $a = self::novoPaciente('A');
        $b = self::novoPaciente('B');
        $original = AgendamentoService::agendar($a, 1, 1, self::segunda() . ' 08:00');
        AgendamentoService::agendar($b, 1, 1, self::segunda() . ' 08:40');
        try {
            AgendamentoService::reagendar($a, $original, self::segunda() . ' 08:40');
            $this->fail('Deveria recusar horário ocupado.');
        } catch (RegraException) {
        }
        $this->assertSame('confirmado', self::situacao($original));
        $this->assertSame(1, self::contar("SELECT COUNT(*) FROM agendamento WHERE id_paciente = ? AND situacao = 'confirmado'", [$a]));
    }

    public function testReagendamentoValidoCriaNovaECancelaOriginal(): void
    {
        $p = self::novoPaciente();
        $original = AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 08:00');
        $novo = AgendamentoService::reagendar($p, $original, self::segunda() . ' 09:20');
        $this->assertSame('cancelado', self::situacao($original));
        $this->assertSame('confirmado', self::situacao($novo));
    }

    public function testRn01LimiteDeCincoAgendamentosFuturos(): void
    {
        $p = self::novoPaciente();
        foreach (['08:00', '08:40', '09:20', '10:00', '10:40'] as $h) {
            AgendamentoService::agendar($p, 1, 1, self::segunda() . " $h");
        }
        $this->expectException(RegraException::class);
        AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 11:20');
    }

    public function testT11InativarProfissionalComConsultasFuturasERecusado(): void
    {
        AgendamentoService::agendar(self::novoPaciente(), 1, 1, self::segunda() . ' 08:00');
        try {
            AgendaService::salvarProfissional(1, 'Dra. Ana Ribeiro', 'CRM-RS 00001', false, [1, 2]);
            $this->fail('Deveria alertar sobre consultas futuras.');
        } catch (RegraException $e) {
            $this->assertStringContainsString('1 consulta(s)', $e->getMessage());
        }
        $this->assertSame(1, self::contar('SELECT ativo FROM profissional WHERE id_profissional = 1'));
    }

    public function testRn09BloqueioSobreConsultaConfirmadaERecusado(): void
    {
        AgendamentoService::agendar(self::novoPaciente(), 1, 1, self::segunda() . ' 08:00');
        $this->expectException(RegraException::class);
        AgendaService::criarBloqueio([1], self::novoAdmin(), self::segunda() . 'T07:00', self::segunda() . 'T12:00', 'Teste');
    }

    public function testFeriadoBloqueiaTodosOsProfissionais(): void
    {
        AgendaService::criarBloqueio([1, 2, 3], self::novoAdmin(), self::segunda() . 'T00:00', self::segunda() . 'T23:59', 'Feriado');
        foreach ([1, 2, 3] as $prof) $this->assertSame([], Disponibilidade::livres($prof, self::segunda()));
    }

    public function testT12RelatorioRespeitaFiltros(): void
    {
        $p = self::novoPaciente();
        $id = AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 08:00');
        AgendamentoService::agendar($p, 1, 1, self::segunda() . ' 08:40');
        AgendamentoService::cancelar($p, $id);

        $todos = Agendamento::relatorio(self::segunda(), self::segunda(), 0, '');
        $this->assertSame(2, (int)$todos[0]['total']);
        $this->assertSame(1, (int)$todos[0]['cancelados']);
        $cancelados = Agendamento::relatorio(self::segunda(), self::segunda(), 0, 'cancelado');
        $this->assertSame(1, (int)$cancelados[0]['total']);
        $this->assertSame([], Agendamento::relatorio(self::segunda(), self::segunda(), 2, ''));
    }

    public function testUc13SituacaoAtualizadaComHistoricoEConsultaFuturaNaoPodeSerRealizada(): void
    {
        $admin = self::novoAdmin();
        $id = AgendamentoService::agendar(self::novoPaciente(), 1, 1, self::segunda() . ' 08:00');
        try {
            AgendamentoService::atualizarSituacao($admin, $id, 'realizado');
            $this->fail('Consulta futura não pode ser marcada como realizada.');
        } catch (RegraException) {
        }
        AgendamentoService::atualizarSituacao($admin, $id, 'cancelado', 'Paciente pediu por telefone');
        $this->assertSame('cancelado', self::situacao($id));
        $this->assertSame(1, self::contar("SELECT COUNT(*) FROM historico_agendamento WHERE id_agendamento = ? AND autor = ? AND motivo = 'Paciente pediu por telefone'", [$id, $admin]));
    }

    public function testT14InjecaoSqlNaoEExecutada(): void
    {
        $this->assertNull(Usuario::porEmail("' OR '1'='1"));
        $nome = "x'); DROP TABLE agendamento; --";
        AgendaService::salvarEspecialidade(null, $nome, '', true);
        $this->assertSame(1, self::contar('SELECT COUNT(*) FROM especialidade WHERE nome = ?', [$nome]));
        $this->assertSame(0, self::contar('SELECT COUNT(*) FROM agendamento'));
    }

    public function testT15ErroDuranteGravacaoFazRollback(): void
    {
        $p = self::novoPaciente();
        try {
            transacao(function () use ($p) {
                $ini = new DateTimeImmutable(self::segunda() . ' 08:00');
                Agendamento::criar($p, 1, 1, $ini, $ini->modify('+30 minutes'));
                throw new RuntimeException('falha simulada');
            });
        } catch (RuntimeException) {
        }
        $this->assertSame(0, self::contar('SELECT COUNT(*) FROM agendamento'));
    }

    public function testRegraDeAgendaIncoerenteOuSobrepostaERecusada(): void
    {
        $base = ['dia_semana' => 1, 'duracao_min' => 30, 'intervalo_min' => 10, 'vigencia_inicio' => '2026-01-01', 'vigencia_fim' => ''];
        foreach ([
            ['hora_inicio' => '10:00', 'hora_fim' => '09:00'],
            ['hora_inicio' => '07:00', 'hora_fim' => '09:00'],
            ['hora_inicio' => '18:00', 'hora_fim' => '19:00', 'intervalo_min' => 0],
        ] as $caso) {
            try {
                AgendaService::salvarRegra(1, $caso + $base);
                $this->fail('Regra inválida aceita: ' . json_encode($caso));
            } catch (RegraException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
