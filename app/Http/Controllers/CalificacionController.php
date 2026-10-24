<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CalificacionRequest;
use App\Models\Docker\Calificacion;
use App\Models\Docker\Inscripcion;
use App\Services\CalificacionService;
use App\Services\ResolutorRelacionesCruzadas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CalificacionController extends Controller
{
    public function __construct(
        private readonly CalificacionService $servicio,
        private readonly ResolutorRelacionesCruzadas $resolutor,
    ) {
    }

    public function index(Request $request): View
    {
        return view('calificaciones.index', [
            'calificaciones' => $this->servicio->listar($request->only('q', 'inscripcion_id')),
        ]);
    }

    public function create(Request $request): View
    {
        return view('calificaciones.form', $this->datosFormulario(new Calificacion([
            'inscripcion_id' => $request->integer('inscripcion_id') ?: null,
        ])));
    }

    public function store(CalificacionRequest $request): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->crear($request->validated()),
            'calificaciones.index',
            'Calificación registrada correctamente.'
        );
    }

    public function edit(Calificacion $calificacion): View
    {
        return view('calificaciones.form', $this->datosFormulario($calificacion));
    }

    public function update(CalificacionRequest $request, Calificacion $calificacion): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->actualizar($calificacion, $request->validated()),
            'calificaciones.index',
            'Calificación actualizada correctamente.'
        );
    }

    public function destroy(Calificacion $calificacion): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->eliminar($calificacion),
            'calificaciones.index',
            'Calificación eliminada.'
        );
    }

    /** @return array<string, mixed> */
    private function datosFormulario(Calificacion $calificacion): array
    {
        // Solo inscripciones vigentes; la gestión se resuelve desde el servidor docente
        $inscripciones = Inscripcion::query()
            ->with(['estudiante', 'asignatura'])
            ->where('estado', '!=', 'retirado')
            ->orderByDesc('gestion_id')
            ->get();

        $gestiones = $this->resolutor->gestionesPorId($inscripciones->pluck('gestion_id'));

        return [
            'calificacion' => $calificacion,
            'opcionesInscripcion' => $inscripciones->mapWithKeys(fn (Inscripcion $i) => [
                $i->id => sprintf(
                    '%s · %s · %s',
                    $gestiones->get($i->gestion_id)?->codigo ?? '#'.$i->gestion_id,
                    $i->asignatura?->sigla,
                    $i->estudiante?->nombreCompleto()
                ),
            ])->all(),
        ];
    }
}
