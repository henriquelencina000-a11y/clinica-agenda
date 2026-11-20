<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DisponibilidadeTest extends TestCase
{
    private const REGRA = [['hora_inicio' => '08:00:00', 'hora_fim' => '10:00:00', 'duracao_min' => 30, 'intervalo_min' => 10]];

    private static function horas(array $slots): array
    {
        return array_map(fn($s) => $s['inicio']->format('H:i'), $slots);
    }

    private static function gerar(array $ocupados = [], string $agora = '2026-01-01 00:00', array $regras = self::REGRA): array
    {
        return self::horas(Disponibilidade::gerar('2026-01-05', $regras, $ocupados, new DateTimeImmutable($agora)));
    }

    private static function periodo(string $ini, string $fim): array
    {
        return [new DateTimeImmutable("2026-01-05 $ini"), new DateTimeImmutable("2026-01-05 $fim")];
    }

    public function testRn05DuracaoMaisIntervaloSemUltrapassarOExpediente(): void
    {
        $this->assertSame(['08:00', '08:40', '09:20'], self::gerar());
    }

    public function testRn06BloqueioParcialRemoveOHorario(): void
    {
        $this->assertSame(['08:00', '09:20'], self::gerar([self::periodo('08:50', '09:00')]));
    }

    public function testIntervalosEncostadosNaoConflitam(): void
    {
        $this->assertSame(['08:00', '08:40', '09:20'], self::gerar([self::periodo('08:30', '08:40')]));
    }

    public function testConflitoPorSobreposicaoComDuracaoDiferente(): void
    {
        $this->assertSame(['08:40', '09:20'], self::gerar([self::periodo('07:45', '08:15')]));
    }

    public function testHorariosPassadosNaoSaoOferecidos(): void
    {
        $this->assertSame(['08:40', '09:20'], self::gerar([], '2026-01-05 08:00'));
    }

    public function testVariasRegrasNoMesmoDiaEmOrdem(): void
    {
        $regras = array_merge(self::REGRA, [['hora_inicio' => '13:00:00', 'hora_fim' => '14:00:00', 'duracao_min' => 50, 'intervalo_min' => 10]]);
        $this->assertSame(['08:00', '08:40', '09:20', '13:00'], self::gerar([], '2026-01-01 00:00', $regras));
    }
}
