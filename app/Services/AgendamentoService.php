<?php
declare(strict_types=1);

/** Cada operação roda em transação com FOR UPDATE no paciente e no profissional. */
final class AgendamentoService
{
    public static function agendar(int $paciente, int $prof, int $esp, string $inicio): int
    {
        return transacao(function () use ($paciente, $prof, $esp, $inicio) {
            Agendamento::travarPaciente($paciente);
            Catalogo::travarProfissional($prof);
            if (!Catalogo::oferece($prof, $esp)) {
                throw new RegraException('Este profissional não está disponível para a especialidade escolhida.');
            }
            $limite = (int)config('limite_futuros');
            if (Agendamento::futurosAtivos($paciente) >= $limite) {
                throw new RegraException("Você já possui $limite agendamentos futuros ativos. Cancele um deles ou aguarde uma consulta antes de marcar outra.");
            }
            $horario = self::horarioLivre($prof, $inicio);
            $id = Agendamento::criar($paciente, $prof, $esp, $horario['inicio'], $horario['fim']);
            Agendamento::registrarHistorico($id, null, 'confirmado', $paciente, 'Agendado pelo paciente');
            return $id;
        });
    }

    public static function cancelar(int $paciente, int $id): string
    {
        return transacao(function () use ($paciente, $id) {
            $consulta = Agendamento::travar($id, $paciente) ?? throw new RegraException('Agendamento não encontrado.');
            if ($consulta['situacao'] === 'cancelado') return 'Esta consulta já estava cancelada.';
            self::exigirElegivel($consulta);
            Agendamento::mudarSituacao($consulta, 'cancelado', $paciente, 'Cancelado pelo paciente');
            return 'Consulta cancelada. O horário foi liberado.';
        });
    }

    /** A nova reserva é gravada antes de cancelar a original. */
    public static function reagendar(int $paciente, int $id, string $novoInicio): int
    {
        return transacao(function () use ($paciente, $id, $novoInicio) {
            Agendamento::travarPaciente($paciente);
            $consulta = Agendamento::travar($id, $paciente) ?? throw new RegraException('Agendamento não encontrado.');
            self::exigirElegivel($consulta);
            $prof = (int)$consulta['id_profissional'];
            Catalogo::travarProfissional($prof);
            $horario = self::horarioLivre($prof, $novoInicio, $id);
            if (sql_dt($horario['inicio']) === $consulta['inicio']) {
                throw new RegraException('Escolha um horário diferente do atual.');
            }
            $novo = Agendamento::criar($paciente, $prof, (int)$consulta['id_especialidade'], $horario['inicio'], $horario['fim']);
            Agendamento::registrarHistorico($novo, null, 'confirmado', $paciente, "Reagendamento da consulta #$id");
            Agendamento::mudarSituacao($consulta, 'cancelado', $paciente, "Reagendada para a consulta #$novo");
            return $novo;
        });
    }

    public static function atualizarSituacao(int $admin, int $id, string $nova, string $motivo = ''): void
    {
        transacao(function () use ($admin, $id, $nova, $motivo) {
            $consulta = Agendamento::travar($id, null) ?? throw new RegraException('Agendamento não encontrado.');
            if ($consulta['situacao'] !== 'confirmado' || !in_array($nova, ['realizado', 'cancelado'], true)) {
                throw new RegraException('Transição de situação não permitida.');
            }
            if ($nova === 'realizado' && new DateTimeImmutable($consulta['inicio']) > agora()) {
                throw new RegraException('Só é possível marcar como realizada uma consulta que já começou.');
            }
            Agendamento::mudarSituacao($consulta, $nova, $admin, $motivo !== '' ? $motivo : 'Alterado pelo administrador');
        });
    }

    public static function elegivel(array $consulta): bool
    {
        return $consulta['situacao'] === 'confirmado'
            && new DateTimeImmutable($consulta['inicio']) >= agora()->modify('+' . (int)config('antecedencia_horas') . ' hours');
    }

    public static function mensagemAntecedencia(): string
    {
        return 'Só é possível cancelar ou reagendar consultas confirmadas com pelo menos '
            . (int)config('antecedencia_horas') . ' horas de antecedência.';
    }

    private static function exigirElegivel(array $consulta): void
    {
        if (!self::elegivel($consulta)) throw new RegraException(self::mensagemAntecedencia());
    }

    private static function horarioLivre(int $prof, string $inicio, int $ignorar = 0): array
    {
        $desejado = data_valida($inicio, 'Y-m-d H:i') ?? throw new RegraException('Horário inválido.');
        foreach (Disponibilidade::livres($prof, $desejado->format('Y-m-d'), $ignorar) as $horario) {
            if ($horario['inicio'] == $desejado) return $horario;
        }
        throw new RegraException('Esse horário acabou de ficar indisponível. Escolha outro horário.');
    }
}
