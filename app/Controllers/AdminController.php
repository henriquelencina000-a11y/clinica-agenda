<?php
declare(strict_types=1);

final class AdminController
{
    private const MAX_DIAS_FILTRO = 366;

    public static function inicio(): void
    {
        exigir('admin');
        redirect('/admin/agendamentos');
    }

    public static function agendamentos(): void
    {
        exigir('admin');
        $f = self::filtros(30);
        auditar('consultar_agendamentos', "admin/agendamentos {$f['de']}..{$f['ate']}");
        view('admin/agendamentos', $f + [
            'titulo' => 'Agendamentos',
            'profissionais' => Catalogo::profissionais(false),
            'agendamentos' => Agendamento::listar($f['de'], $f['ate'], $f['prof'], $f['situacao']),
        ]);
    }

    public static function situacao(): void
    {
        $u = exigir('admin');
        $id = inteiro('id');
        $nova = entrada('situacao', 20);
        AgendamentoService::atualizarSituacao($u['id'], $id, $nova, entrada('motivo', 200));
        auditar('atualizar_situacao', "agendamento:$id:$nova");
        flash('success', 'Situação atualizada.');
        voltar('/admin/agendamentos');
    }

    public static function especialidades(): void
    {
        exigir('admin');
        view('admin/especialidades', ['titulo' => 'Especialidades', 'especialidades' => Catalogo::especialidades(false)]);
    }

    public static function salvarEspecialidade(): void
    {
        exigir('admin');
        $id = inteiro('id') ?: null;
        AgendaService::salvarEspecialidade($id, entrada('nome', 80), entrada('descricao', 1000), !empty($_POST['ativa']));
        auditar('salvar_especialidade', 'especialidade:' . ($id ?? 'nova'));
        flash('success', 'Especialidade salva.');
        redirect('/admin/especialidades');
    }

    public static function profissionais(): void
    {
        exigir('admin');
        view('admin/profissionais', [
            'titulo' => 'Profissionais',
            'profissionais' => Catalogo::profissionais(false),
            'especialidades' => Catalogo::especialidades(false),
        ]);
    }

    public static function salvarProfissional(): void
    {
        exigir('admin');
        $esps = is_array($_POST['especialidades'] ?? null) ? $_POST['especialidades'] : [];
        $id = AgendaService::salvarProfissional(inteiro('id') ?: null, entrada('nome', 120), entrada('registro', 40), !empty($_POST['ativo']), $esps);
        auditar('salvar_profissional', "profissional:$id");
        flash('success', 'Profissional salvo.');
        redirect('/admin/profissionais');
    }

    public static function agenda(): void
    {
        exigir('admin');
        $prof = inteiro('profissional');
        view('admin/agenda', [
            'titulo' => 'Agenda e bloqueios',
            'profissionais' => Catalogo::profissionais(false),
            'prof' => $prof,
            'regras' => $prof ? Agenda::regras($prof) : [],
            'bloqueios' => $prof ? Agenda::bloqueiosFuturos($prof) : [],
        ]);
    }

    public static function salvarRegra(): void
    {
        exigir('admin');
        $prof = inteiro('profissional');
        AgendaService::salvarRegra($prof, $_POST);
        auditar('criar_regra', "profissional:$prof");
        flash('success', 'Regra de agenda adicionada.');
        redirect("/admin/agenda?profissional=$prof");
    }

    public static function removerRegra(): void
    {
        exigir('admin');
        $prof = inteiro('profissional');
        $id = inteiro('id');
        AgendaService::removerRegra($prof, $id);
        auditar('remover_regra', "regra:$id");
        flash('success', 'Regra removida.');
        redirect("/admin/agenda?profissional=$prof");
    }

    public static function salvarBloqueio(): void
    {
        $u = exigir('admin');
        $prof = inteiro('profissional');
        $todos = !empty($_POST['todos']);
        $alvos = $todos
            ? array_map('intval', array_column(Catalogo::profissionais(), 'id_profissional'))
            : [$prof];
        AgendaService::criarBloqueio($alvos, $u['id'], entrada('inicio', 16), entrada('fim', 16), entrada('motivo', 200));
        auditar('criar_bloqueio', $todos ? 'profissional:todos' : "profissional:$prof");
        flash('success', $todos ? 'Bloqueio registrado para todos os profissionais.' : 'Bloqueio registrado.');
        redirect("/admin/agenda?profissional=$prof");
    }

    public static function removerBloqueio(): void
    {
        exigir('admin');
        $prof = inteiro('profissional');
        $id = inteiro('id');
        Agenda::removerBloqueio($id, $prof);
        auditar('remover_bloqueio', "bloqueio:$id");
        flash('success', 'Bloqueio removido. Os horários voltam à grade.');
        redirect("/admin/agenda?profissional=$prof");
    }

    public static function relatorios(): void
    {
        exigir('admin');
        $f = self::filtros(-30);
        auditar('emitir_relatorio', "relatorio {$f['de']}..{$f['ate']}");
        view('admin/relatorios', $f + [
            'titulo' => 'Relatórios',
            'profissionais' => Catalogo::profissionais(false),
            'linhas' => Agendamento::relatorio($f['de'], $f['ate'], $f['prof'], $f['situacao']),
            'lista' => Agendamento::listar($f['de'], $f['ate'], $f['prof'], $f['situacao']),
        ]);
    }

    /** $dias define o período padrão a partir de hoje (negativo = para trás). */
    private static function filtros(int $dias): array
    {
        $hoje = agora()->format('Y-m-d');
        $outroExtremo = agora()->modify("$dias days")->format('Y-m-d');
        $datas = ['de' => min($hoje, $outroExtremo), 'ate' => max($hoje, $outroExtremo)];
        $avisos = [];

        foreach (['de' => 'De', 'ate' => 'Até'] as $campo => $rotulo) {
            $valor = entrada($campo, 10);
            if ($valor === '') continue;
            if (data_valida($valor)) $datas[$campo] = $valor;
            else $avisos[] = "A data \"$rotulo\" é inválida; foi usado o valor padrão.";
        }
        ['de' => $de, 'ate' => $ate] = $datas;
        if ($ate < $de) {
            [$de, $ate] = [$ate, $de];
            $avisos[] = 'A data final era anterior à inicial; as datas foram invertidas.';
        }
        if ((new DateTimeImmutable($de))->diff(new DateTimeImmutable($ate))->days > self::MAX_DIAS_FILTRO) {
            $ate = (new DateTimeImmutable($de))->modify('+' . self::MAX_DIAS_FILTRO . ' days')->format('Y-m-d');
            $avisos[] = 'O período máximo é de ' . self::MAX_DIAS_FILTRO . ' dias; a data final foi ajustada.';
        }
        $situacao = entrada('situacao', 20);
        if ($situacao !== '' && !isset(SITUACOES[$situacao])) {
            $situacao = '';
            $avisos[] = 'Situação inválida; foram consideradas todas.';
        }
        return ['de' => $de, 'ate' => $ate, 'prof' => inteiro('profissional'), 'situacao' => $situacao, 'avisos' => $avisos];
    }
}
