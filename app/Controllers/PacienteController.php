<?php
declare(strict_types=1);

final class PacienteController
{
    public static function horarios(): void
    {
        exigir('paciente');
        $esp = inteiro('especialidade');
        $prof = inteiro('profissional');

        $profissionais = $esp ? Catalogo::profissionaisParaAgendar($esp) : [];
        if (!in_array($prof, array_map('intval', array_column($profissionais, 'id_profissional')), true)) $prof = 0;

        [$data, $mes] = self::diaEMes();
        $dias = [];
        $horarios = null;
        $alternativas = ['data' => null, 'profissionais' => []];
        if ($esp && $prof) {
            $dias = Disponibilidade::porMes($prof, $mes);
            if ($data) $horarios = Disponibilidade::livres($prof, $data);
            if (!$dias) $alternativas = self::alternativas($prof, $mes, $profissionais);
        }
        view('horarios', [
            'titulo' => 'Agendar consulta',
            'especialidades' => Catalogo::especialidades(),
            'profissionais' => $profissionais,
            'esp' => $esp, 'prof' => $prof, 'data' => $data, 'mes' => $mes,
            'dias' => $dias, 'horarios' => $horarios,
            'alternativas' => $alternativas,
        ]);
    }

    private static function alternativas(int $prof, DateTimeImmutable $mes, array $profissionais): array
    {
        $proxima = null;
        for ($i = 1; $i <= 3 && !$proxima; $i++) {
            $proxima = array_key_first(Disponibilidade::porMes($prof, $mes->modify("first day of +$i month")));
        }
        $outros = array_values(array_filter($profissionais, fn($p) => (int)$p['id_profissional'] !== $prof
            && Disponibilidade::porMes((int)$p['id_profissional'], $mes)));
        return ['data' => $proxima, 'profissionais' => $outros];
    }

    private static function diaEMes(): array
    {
        $d = data_valida(entrada('data', 10));
        if ($d && $d < agora()->setTime(0, 0)) $d = null;
        $mes = mes_param(entrada('mes', 7) ?: ($d ? $d->format('Y-m') : ''));
        return [$d?->format('Y-m-d') ?? '', $mes];
    }

    public static function confirmar(): void
    {
        exigir('paciente');
        $esp = inteiro('especialidade');
        $prof = inteiro('profissional');
        $inicio = data_valida(entrada('inicio', 16), 'Y-m-d H:i');
        $nomes = Catalogo::nomes($prof, $esp);
        if (!$inicio || !$nomes || !Catalogo::oferece($prof, $esp)) {
            flash('danger', 'Seleção inválida. Escolha o horário novamente.');
            redirect('/horarios');
        }
        view('confirmar', ['titulo' => 'Confirmar agendamento', 'esp' => $esp, 'prof' => $prof, 'inicio' => $inicio, 'nomes' => $nomes]);
    }

    public static function agendar(): void
    {
        $u = exigir('paciente');
        $esp = inteiro('especialidade');
        $prof = inteiro('profissional');
        $inicio = entrada('inicio', 16);
        try {
            $id = AgendamentoService::agendar($u['id'], $prof, $esp, $inicio);
        } catch (RegraException $e) {
            flash('danger', $e->getMessage());
            $dia = substr($inicio, 0, 10);
            redirect('/horarios?' . http_build_query(['especialidade' => $esp, 'profissional' => $prof, 'data' => $dia, 'mes' => substr($dia, 0, 7)]) . '#horarios');
        }
        auditar('agendar', "agendamento:$id");
        flash('success', 'Consulta confirmada! Confira os detalhes abaixo.');
        redirect('/meus-agendamentos');
    }

    public static function meus(): void
    {
        $u = exigir('paciente');
        view('meus', ['titulo' => 'Meus agendamentos', 'agendamentos' => Agendamento::doPaciente($u['id'])]);
    }

    public static function cancelar(): void
    {
        $u = exigir('paciente');
        $id = inteiro('id');
        self::meuAgendamento($u['id'], $id);
        flash('success', AgendamentoService::cancelar($u['id'], $id));
        auditar('cancelar', "agendamento:$id");
        redirect('/meus-agendamentos');
    }

    public static function formReagendar(): void
    {
        $u = exigir('paciente');
        $id = inteiro('id');
        $consulta = self::meuAgendamento($u['id'], $id);
        if (!AgendamentoService::elegivel($consulta)) {
            flash('warning', AgendamentoService::mensagemAntecedencia());
            redirect('/meus-agendamentos');
        }
        [$data, $mes] = self::diaEMes();
        $prof = (int)$consulta['id_profissional'];
        view('reagendar', [
            'titulo' => 'Reagendar consulta', 'consulta' => $consulta, 'data' => $data, 'mes' => $mes,
            'dias' => Disponibilidade::porMes($prof, $mes, $id),
            'horarios' => $data ? Disponibilidade::livres($prof, $data, $id) : null,
        ]);
    }

    public static function reagendar(): void
    {
        $u = exigir('paciente');
        $id = inteiro('id');
        self::meuAgendamento($u['id'], $id);
        $novo = AgendamentoService::reagendar($u['id'], $id, entrada('inicio', 16));
        auditar('reagendar', "agendamento:{$id}>{$novo}");
        flash('success', 'Consulta reagendada com sucesso.');
        redirect('/meus-agendamentos');
    }

    public static function meusDados(): void
    {
        $u = exigir('paciente');
        auditar('consultar_dados_titular', "usuario:{$u['id']}");
        view('meus-dados', ['titulo' => 'Meus dados', 'dados' => Usuario::dadosPaciente($u['id'])]);
    }

    private static function meuAgendamento(int $paciente, int $id): array
    {
        $consulta = $id ? (Agendamento::doPaciente($paciente, $id)[0] ?? null) : null;
        if (!$consulta) {
            auditar('acesso_negado', "agendamento:$id", 'negado');
            flash('danger', 'Acesso negado ou consulta não encontrada.');
            redirect('/meus-agendamentos');
        }
        return $consulta;
    }
}
