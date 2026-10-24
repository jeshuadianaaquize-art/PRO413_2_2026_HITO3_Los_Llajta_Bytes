<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\InscripcionRequest;
use App\Models\Docente\GestionAcademica;
use App\Models\Docker\Estudiante;
use App\Models\Docker\Inscripcion;
use App\Services\InscripcionService;
use App\Services\ResolutorRelacionesCruzadas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class InscripcionController extends Controller
{
    public function __construct(
        private readonly InscripcionService $servicio,
        private readonly ResolutorRelacionesCruzadas $resolutor,
    ) {
    }

    public function index(Request $request): View
    {
        $inscripciones = $this->servicio->listar($request->only('q', 'gestion_id'));

        return view('inscripciones.index', [
            'inscripciones' => $inscripciones,
            // Mapa id => gestión traído del servidor docente para esta página
            'gestiones' => $this->resolutor->gestionesPorId($inscripciones->getCollection()->pluck('gestion_id')),
            'opcionesGestion' => $this->resolutor->opcionesGestiones(),
        ]);
    }

    public function create(): View
    {
        return view('inscripciones.form', $this->datosFormulario(new Inscripcion([
            'gestion_id' => GestionAcademica::activa()?->id,
            'fecha_inscripcion' => now()->toDateString(),
            'estado' => 'inscrito',
        ])));
    }

    public function store(InscripcionRequest $request): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->crear($request->validated()),
            'inscripciones.index',
            'Inscripción registrada correctamente.'
        );
    }

    public function edit(Inscripcion $inscripcion): View
    {
        return view('inscripciones.form', $this->datosFormulario($inscripcion));
    }

    public function update(InscripcionRequest $request, Inscripcion $inscripcion): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->actualizar($inscripcion, $request->validated()),
            'inscripciones.index',
            'Inscripción actualizada correctamente.'
        );
    }

    public function destroy(Inscripcion $inscripcion): RedirectResponse
    {
        return $this->ejecutar(
            fn () => $this->servicio->eliminar($inscripcion),
            'inscripciones.index',
            'Inscripción eliminada (junto con sus calificaciones).'
        );
    }

    /** @return array<string, mixed> */
    private function datosFormulario(Inscripcion $inscripcion): array
    {
        return [
            'inscripcion' => $inscripcion,
            'opcionesEstudiante' => Estudiante::query()->orderBy('apellidos')->get()
                ->mapWithKeys(fn (Estudiante $e) => [$e->id => $e->etiqueta().($e->estaActivo() ? '' : ' (inactivo)')])
                ->all(),
            'opcionesAsignatura' => $this->resolutor->opcionesAsignaturas(),
            'opcionesGestion' => $this->resolutor->opcionesGestiones(),
        ];
    }
}
