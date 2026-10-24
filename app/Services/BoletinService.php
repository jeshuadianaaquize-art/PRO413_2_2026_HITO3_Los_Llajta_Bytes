<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Docente\AsignacionDocente;
use App\Models\Docente\GestionAcademica;
use App\Models\Docker\Estudiante;
use App\Models\Docker\Inscripcion;
use Illuminate\Support\Collection;
use SplPriorityQueue;

/**
 * Módulo 3 - Boletín de notas y ranking de estudiantes.
 * Combina información de LOS DOS servidores MySQL.
 *
 * ESTRUCTURAS DE DATOS utilizadas (y por qué):
 *  1. Mapa asociativo id => modelo (Collection::keyBy):
 *       une registros de servidores distintos en O(1) sin JOIN.
 *  2. Agrupación (Collection::groupBy):
 *       agrupa las inscripciones por gestión o por estudiante.
 *  3. Arreglo asociativo anidado (array):
 *       estructura final, simple y predecible, que recibe la vista Blade.
 *  4. Cola de prioridad (SplPriorityQueue):
 *       obtiene el ranking extrayendo siempre el mayor promedio,
 *       sin ordenar toda la lista.
 */
final class BoletinService
{
    public function __construct(private readonly ResolutorRelacionesCruzadas $resolutor)
    {
    }

    /**
     * Boletín de notas de un estudiante agrupado por gestión.
     *
     * @return array{
     *   gestiones: array<string, array{gestion: string, materias: list<array<string, mixed>>, promedio: float|null}>,
     *   promedioGeneral: float|null
     * }
     */
    public function boletin(Estudiante $estudiante): array
    {
        // Servidor Docker: inscripciones + asignatura + calificaciones
        $inscripciones = $estudiante->inscripciones()->with(['asignatura', 'calificaciones'])->get();

        // Servidor docente: gestiones y docentes, consultados una sola vez
        $gestiones = $this->resolutor->gestionesPorId($inscripciones->pluck('gestion_id'));
        $docentesPorCurso = $this->docentesPorCurso($inscripciones->pluck('gestion_id')->unique()->values()->all());

        $porGestion = $inscripciones
            ->groupBy('gestion_id')
            ->sortKeysDesc()
            ->mapWithKeys(function (Collection $grupo, int|string $gestionId) use ($gestiones, $docentesPorCurso): array {
                $codigo = $gestiones->get((int) $gestionId)?->codigo ?? "#{$gestionId} (no encontrada)";

                $materias = $grupo
                    ->sortBy(fn (Inscripcion $i) => $i->asignatura?->sigla)
                    ->map(function (Inscripcion $i) use ($docentesPorCurso): array {
                        $nota = $i->notaFinal();

                        return [
                            'sigla' => $i->asignatura?->sigla,
                            'nombre' => $i->asignatura?->nombre,
                            'docente' => $docentesPorCurso["{$i->gestion_id}:{$i->asignatura_id}"] ?? 'Sin asignar',
                            'notas' => $nota->comoArreglo(),
                            'acumulada' => $nota->acumulada(),
                            'porcentaje' => $nota->porcentajeEvaluado(),
                            'situacion' => $i->estaRetirado() ? 'retirado' : $nota->situacion(),
                            'estado' => $i->estado,
                        ];
                    })
                    ->values();

                $completas = $materias->where('porcentaje', 100);

                return [$codigo => [
                    'gestion' => $codigo,
                    'materias' => $materias->all(),
                    'promedio' => $completas->isEmpty() ? null : round((float) $completas->avg('acumulada'), 2),
                ]];
            });

        $promedios = $porGestion->pluck('promedio')->filter(fn ($p) => $p !== null);

        return [
            'gestiones' => $porGestion->all(),
            'promedioGeneral' => $promedios->isEmpty() ? null : round((float) $promedios->avg(), 2),
        ];
    }

    /**
     * Ranking de estudiantes por nota acumulada promedio en una gestión.
     * Cola de prioridad: cada inserción es O(log n) y extract() devuelve
     * siempre el mayor promedio.
     *
     * @return list<array{estudiante: string, promedio: float, materias: int}>
     */
    public function ranking(GestionAcademica $gestion, int $limite = 5): array
    {
        $porEstudiante = Inscripcion::query()
            ->with(['estudiante', 'calificaciones'])
            ->where('gestion_id', $gestion->id)
            ->where('estado', '!=', 'retirado')
            ->get()
            ->filter(fn (Inscripcion $i) => $i->calificaciones->isNotEmpty())
            ->groupBy('estudiante_id');

        $cola = new SplPriorityQueue();
        $cola->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        foreach ($porEstudiante as $inscripciones) {
            $promedio = round((float) $inscripciones->avg(fn (Inscripcion $i) => $i->notaFinal()->acumulada()), 2);
            // Prioridad entera: el promedio en centésimas
            $cola->insert([
                'estudiante' => $inscripciones->first()->estudiante?->nombreCompleto() ?? '—',
                'promedio' => $promedio,
                'materias' => $inscripciones->count(),
            ], (int) round($promedio * 100));
        }

        $resultado = [];
        while (! $cola->isEmpty() && count($resultado) < $limite) {
            $resultado[] = $cola->extract();
        }

        return $resultado;
    }

    /**
     * Mapa "gestion_id:asignatura_id" => nombres de docentes (servidor docente).
     *
     * @param  list<int>  $gestionIds
     * @return array<string, string>
     */
    private function docentesPorCurso(array $gestionIds): array
    {
        if ($gestionIds === []) {
            return [];
        }

        return AsignacionDocente::query()
            ->with('docente')
            ->whereIn('gestion_id', $gestionIds)
            ->get()
            ->groupBy(fn (AsignacionDocente $a) => "{$a->gestion_id}:{$a->asignatura_id}")
            ->map(fn (Collection $g) => $g->map(fn (AsignacionDocente $a) => $a->docente?->etiqueta())->filter()->unique()->implode(', '))
            ->all();
    }
}
