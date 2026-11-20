<?php
declare(strict_types=1);

final class PublicoController
{
    public static function inicio(): void
    {
        $proximos = [];
        foreach (Catalogo::profissionais() as $p) {
            $esp = (int)explode(',', (string)$p['ids_especialidades'])[0];
            if ($esp && ($horarios = Disponibilidade::proximos((int)$p['id_profissional']))) {
                $proximos[] = ['profissional' => $p, 'esp' => $esp, 'horarios' => $horarios];
            }
            if (count($proximos) === 3) break;
        }
        view('inicio', ['titulo' => 'Início', 'especialidades' => Catalogo::especialidades(), 'proximos' => $proximos]);
    }

    public static function especialidades(): void
    {
        $porEspecialidade = [];
        foreach (Catalogo::profissionais() as $p) {
            foreach (array_filter(explode(',', (string)$p['ids_especialidades'])) as $esp) {
                $porEspecialidade[(int)$esp][] = $p['nome'];
            }
        }
        view('especialidades', ['titulo' => 'Especialidades', 'especialidades' => Catalogo::especialidades(), 'profissionais' => $porEspecialidade]);
    }

    public static function profissionais(): void
    {
        view('profissionais', ['titulo' => 'Profissionais', 'profissionais' => Catalogo::profissionais()]);
    }

    public static function privacidade(): void
    {
        view('privacidade', ['titulo' => 'Aviso de privacidade']);
    }
}
