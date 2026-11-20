<?php
declare(strict_types=1);

final class AgendaService
{
    public static function salvarRegra(int $prof, array $dados): void
    {
        $diaSemana = filter_var($dados['dia_semana'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 6]]);
        $horaInicio = data_valida((string)($dados['hora_inicio'] ?? ''), 'H:i');
        $horaFim = data_valida((string)($dados['hora_fim'] ?? ''), 'H:i');
        $duracao = (int)($dados['duracao_min'] ?? 0);
        $intervalo = (int)($dados['intervalo_min'] ?? 0);
        $vigenciaInicio = data_valida((string)($dados['vigencia_inicio'] ?? ''));
        $vigenciaFimTexto = (string)($dados['vigencia_fim'] ?? '');
        $vigenciaFim = $vigenciaFimTexto === '' ? null : data_valida($vigenciaFimTexto);

        if ($diaSemana === false) throw new RegraException('Dia da semana inválido.');
        if (!$horaInicio || !$horaFim || $horaFim <= $horaInicio) throw new RegraException('O horário final deve ser posterior ao inicial.');
        if ($duracao < 5 || $duracao > 480) throw new RegraException('A duração deve ficar entre 5 e 480 minutos.');
        if ($intervalo < 1 || $intervalo > 240) throw new RegraException('O intervalo deve ficar entre 1 e 240 minutos.');
        if (!$vigenciaInicio || ($vigenciaFimTexto !== '' && !$vigenciaFim)) throw new RegraException('Informe datas de vigência válidas.');
        if ($vigenciaFim && $vigenciaFim < $vigenciaInicio) throw new RegraException('A vigência final não pode ser anterior à inicial.');

        $inicio = $horaInicio->format('H:i:s');
        $fim = $horaFim->format('H:i:s');
        $vigenteDe = $vigenciaInicio->format('Y-m-d');
        $vigenteAte = $vigenciaFim?->format('Y-m-d');

        transacao(function () use ($prof, $diaSemana, $inicio, $fim, $duracao, $intervalo, $vigenteDe, $vigenteAte) {
            Catalogo::travarProfissional($prof);
            if (Agenda::regraSobreposta($prof, $diaSemana, $inicio, $fim, $vigenteDe, $vigenteAte)) {
                throw new RegraException('Já existe uma regra que se sobrepõe a este horário neste dia da semana.');
            }
            Agenda::criarRegra($prof, $diaSemana, $inicio, $fim, $duracao, $intervalo, $vigenteDe, $vigenteAte);
        });
    }

    public static function removerRegra(int $prof, int $id): void
    {
        transacao(function () use ($prof, $id) {
            Catalogo::travarProfissional($prof);
            $regra = Agenda::regra($id, $prof) ?? throw new RegraException('Regra não encontrada.');
            // Verifica o dia da semana inteiro, mesmo que outra regra também cubra as consultas.
            self::recusarSeAfetar(
                Agendamento::futuros(['id_profissional' => $prof, 'dia_semana' => (int)$regra['dia_semana']]),
                'Remover esta regra afeta'
            );
            Agenda::removerRegra($id);
        });
    }

    public static function criarBloqueio(array $profissionais, int $admin, string $inicio, string $fim, string $motivo): void
    {
        $dataInicio = data_valida($inicio, 'Y-m-d\TH:i') ?? throw new RegraException('Início do bloqueio inválido.');
        $dataFim = data_valida($fim, 'Y-m-d\TH:i') ?? throw new RegraException('Fim do bloqueio inválido.');
        if ($dataFim <= $dataInicio) throw new RegraException('O fim do bloqueio deve ser posterior ao início.');
        if (!$profissionais) throw new RegraException('Selecione o profissional.');

        $inicio = sql_dt($dataInicio);
        $fim = sql_dt($dataFim);
        sort($profissionais); // ordem fixa de bloqueio evita deadlock entre transações

        transacao(function () use ($profissionais, $admin, $inicio, $fim, $motivo) {
            $afetadas = [];
            foreach ($profissionais as $prof) {
                Catalogo::travarProfissional($prof);
                array_push($afetadas, ...Agendamento::confirmadosSobrepostos($prof, $inicio, $fim));
            }
            self::recusarSeAfetar($afetadas, 'O bloqueio afeta');
            foreach ($profissionais as $prof) {
                Agenda::criarBloqueio($prof, $inicio, $fim, $motivo, $admin);
            }
        });
    }

    public static function salvarProfissional(?int $id, string $nome, string $registro, bool $ativo, array $especialidades): int
    {
        if (mb_strlen($nome) < 3) throw new RegraException('Informe o nome do profissional.');
        $especialidades = array_values(array_unique(array_map('intval', $especialidades)));

        return transacao(function () use ($id, $nome, $registro, $ativo, $especialidades) {
            if ($id) {
                Catalogo::travarProfissional($id);
                if (!$ativo) {
                    self::recusarSeAfetar(Agendamento::futuros(['id_profissional' => $id]), 'Inativar este profissional afeta');
                }
                foreach (array_diff(Catalogo::idsEspecialidadesAtivas($id), $especialidades) as $esp) {
                    self::recusarSeAfetar(Agendamento::futuros(['id_profissional' => $id, 'id_especialidade' => $esp]), 'Remover a especialidade afeta');
                }
            }
            $id = Catalogo::salvarProfissional($id, $nome, $registro, $ativo);
            foreach (Catalogo::especialidades(false) as $e) {
                $esp = (int)$e['id_especialidade'];
                Catalogo::definirVinculo($id, $esp, in_array($esp, $especialidades, true));
            }
            return $id;
        });
    }

    public static function salvarEspecialidade(?int $id, string $nome, string $descricao, bool $ativa): void
    {
        if (mb_strlen($nome) < 3) throw new RegraException('Informe o nome da especialidade.');
        try {
            Catalogo::salvarEspecialidade($id, $nome, $descricao, $ativa);
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') throw $e;
            throw new RegraException('Já existe uma especialidade com este nome.');
        }
    }

    private static function recusarSeAfetar(array $inicios, string $acao): void
    {
        if (!$inicios) return;
        $lista = implode(', ', array_map('fmt', array_slice($inicios, 0, 10))) . (count($inicios) > 10 ? ', …' : '');
        throw new RegraException("$acao " . count($inicios) . " consulta(s) confirmada(s): $lista. Trate essas consultas em Agendamentos antes de continuar.");
    }
}
