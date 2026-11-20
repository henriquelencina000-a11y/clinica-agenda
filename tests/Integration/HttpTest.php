<?php
declare(strict_types=1);

final class HttpTest extends BancoTestCase
{
    private const PORTA = 8765;
    private static $servidor;
    private string $cookie = '';

    public static function setUpBeforeClass(): void
    {
        $pub = dirname(__DIR__, 2) . '/public';
        $log = sys_get_temp_dir() . '/clinica-agenda-teste-servidor.log';
        self::$servidor = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . self::PORTA, '-t', $pub, "$pub/index.php"],
                                    [1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes,
                                    null, getenv() + ['CLINICA_CONFIG' => getenv('CLINICA_CONFIG')]);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', self::PORTA); $i++) usleep(100_000);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$servidor) proc_terminate(self::$servidor);
    }

    private function req(string $metodo, string $caminho, array $dados = []): array
    {
        $ctx = stream_context_create(['http' => [
            'method' => $metodo,
            'header' => "Cookie: {$this->cookie}\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($dados),
            'ignore_errors' => true,
            'follow_location' => 0,
        ]]);
        $corpo = (string)file_get_contents('http://127.0.0.1:' . self::PORTA . $caminho, false, $ctx);
        foreach ($http_response_header as $h) {
            if (preg_match('/^Set-Cookie:\s*(clinica_sid=[^;]+)/i', $h, $m)) $this->cookie = $m[1];
        }
        $local = '';
        foreach ($http_response_header as $h) {
            if (preg_match('/^Location:\s*(.+)$/i', $h, $m)) $local = trim($m[1]);
        }
        return [(int)explode(' ', $http_response_header[0])[1], $corpo, $local];
    }

    public function testAposLoginVoltaParaAPaginaPedida(): void
    {
        Usuario::criarPaciente('Fulano', 'fulano@teste.local', 'senha-teste-123', '');
        $pedido = '/horarios?especialidade=1&profissional=1';
        $this->assertSame(302, $this->req('GET', $pedido)[0]);
        $resposta = $this->req('POST', '/login', ['email' => 'fulano@teste.local', 'senha' => 'senha-teste-123', '_csrf' => $this->csrf()]);
        $this->assertSame($pedido, $resposta[2]);
    }

    public function testPacienteNaoEDesviadoParaAreaAdminAposLogin(): void
    {
        Usuario::criarPaciente('Fulano', 'fulano@teste.local', 'senha-teste-123', '');
        $this->req('GET', '/admin/relatorios');
        $resposta = $this->req('POST', '/login', ['email' => 'fulano@teste.local', 'senha' => 'senha-teste-123', '_csrf' => $this->csrf()]);
        $this->assertSame('/meus-agendamentos', $resposta[2]);
    }

    private function csrf(string $caminho = '/login'): string
    {
        [, $html] = $this->req('GET', $caminho);
        preg_match('/name="_csrf" value="([^"]+)"/', $html, $m);
        return $m[1];
    }

    private function entrar(string $email, string $senha): int
    {
        return $this->req('POST', '/login', ['email' => $email, 'senha' => $senha, '_csrf' => $this->csrf()])[0];
    }

    public function testT03LoginComSenhaIncorretaMensagemGenerica(): void
    {
        Usuario::criarPaciente('Fulano', 'fulano@teste.local', 'senha-correta-1', '');
        [$status, $html] = $this->req('POST', '/login', ['email' => 'fulano@teste.local', 'senha' => 'errada', '_csrf' => $this->csrf()]);
        $this->assertSame(401, $status);
        $this->assertStringContainsString('Não foi possível autenticar', $html);
        $this->assertSame(1, self::contar("SELECT COUNT(*) FROM registro_auditoria WHERE acao = 'login_falha'"));
        $this->assertSame(0, self::contar("SELECT COUNT(*) FROM registro_auditoria WHERE recurso LIKE '%errada%' OR recurso LIKE '%fulano%'"));
    }

    public function testT04PacienteNaoAcessaConsultaDeOutroEEventoERegistrado(): void
    {
        $a = Usuario::criarPaciente('Paciente A', 'a@teste.local', 'senha-teste-123', '');
        $b = Usuario::criarPaciente('Paciente B', 'b@teste.local', 'senha-teste-123', '');
        $daB = AgendamentoService::agendar($b, 1, 1, self::segunda() . ' 08:00');

        $this->assertSame(302, $this->entrar('a@teste.local', 'senha-teste-123'));
        [$status] = $this->req('GET', "/agendamentos/reagendar?id=$daB");
        $this->assertSame(302, $status);
        [, $html] = $this->req('GET', '/meus-agendamentos');
        $this->assertStringContainsString('Acesso negado', $html);
        $this->assertStringNotContainsString('Paciente B', $html);

        $this->req('POST', '/agendamentos/cancelar', ['id' => $daB, '_csrf' => $this->csrf('/meus-agendamentos')]);
        $this->assertSame('confirmado', self::situacao($daB));
        $this->assertSame(2, self::contar("SELECT COUNT(*) FROM registro_auditoria WHERE acao = 'acesso_negado' AND id_usuario = ? AND recurso = ?", [$a, "agendamento:$daB"]));
    }

    public function testPacienteNaoAcessaAreaAdministrativa(): void
    {
        Usuario::criarPaciente('Fulano', 'fulano@teste.local', 'senha-teste-123', '');
        $this->entrar('fulano@teste.local', 'senha-teste-123');
        $this->assertSame(403, $this->req('GET', '/admin/relatorios')[0]);
        $this->assertSame(403, $this->req('POST', '/admin/agendamentos/situacao', ['id' => 1, 'situacao' => 'cancelado', '_csrf' => $this->csrf('/meus-agendamentos')])[0]);
    }

    public function testVisitanteEnviadoAoLoginNasAreasRestritas(): void
    {
        $this->assertSame(302, $this->req('GET', '/meus-agendamentos')[0]);
        $this->assertSame(302, $this->req('GET', '/admin/agendamentos')[0]);
    }

    public function testFormularioSemTokenCsrfERecusado(): void
    {
        $this->assertSame(419, $this->req('POST', '/login', ['email' => 'x@teste.local', 'senha' => 'x'])[0]);
    }

    public function testT01CatalogoPublicoSemLogin(): void
    {
        [$status, $html] = $this->req('GET', '/especialidades');
        $this->assertSame(200, $status);
        $this->assertStringContainsString('Cardiologia', $html);
    }

    public function testFluxoCompletoDeAgendamentoPelaInterface(): void
    {
        Usuario::criarPaciente('Fulano', 'fulano@teste.local', 'senha-teste-123', '');
        $this->entrar('fulano@teste.local', 'senha-teste-123');
        $dados = ['especialidade' => 1, 'profissional' => 1, 'inicio' => self::segunda() . ' 08:00'];
        [$status, $html] = $this->req('GET', '/agendamentos/confirmar?' . http_build_query($dados));
        $this->assertSame(200, $status);
        preg_match('/name="_csrf" value="([^"]+)"/', $html, $m);
        $this->assertSame(302, $this->req('POST', '/agendamentos', $dados + ['_csrf' => $m[1]])[0]);
        [, $html] = $this->req('GET', '/meus-agendamentos');
        $this->assertStringContainsString('Consulta confirmada', $html);
    }
}
