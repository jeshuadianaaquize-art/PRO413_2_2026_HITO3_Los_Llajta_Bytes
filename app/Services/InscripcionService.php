<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DominioException;
use App\Models\Docente\GestionAcademica;
use App\Models\Docker\Estudiante;
use App\Models\Docker\Inscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class InscripcionService extends ServicioCrudBase
{
    protected function claseModelo(): string
    {
        return Inscripcion::class;
    }

    public function nombreEntidad(): string
    {
        return 'inscripción';
    }

    protected function relacionesListado(): array
    {
        return ['estudiante', 'asignatura', 'calificaciones'];
    }

    protected function aplicarFiltros(Builder $consulta, array $filtros): void
    {
        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $consulta->where(function (Builder $q) use ($texto): void {
                $q->whereHas('estudiante', fn (Builder $e) => $e
                    ->where('nombres', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%")
                    ->orWhere('codigo', 'like', "%{$texto}%"))
                    ->orWhereHas('asignatura', fn (Builder $a) => $a
                        ->where('sigla', 'like', "%{$texto}%")
                        ->orWhere('nombre', 'like', "%{$texto}%"));
            });
        }

        if (! empty($filtros['gestion_id'])) {
            $consulta->where('gestion_id', (int) $filtros['gestion_id']);
        }
    }

    protected function ordenarListado(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('gestion_id')->orderByDesc('id');
    }

    /**
     * Reglas de negocio de una inscripción:
     *  1. La gestión debe existir en el servidor del DOCENTE (relación entre servidores).
     *  2. Solo se crean inscripciones nuevas en la gestión activa.
     *  3. El estudiante debe estar activo.
     *  4. No puede repetirse estudiante + asignatura + gestión.
     */
    protected function antesDeGuardar(array $datos, ?Model $existente): array
    {
        $gestion = GestionAcademica::query()->find((int) $datos['gestion_id']);
        if ($gestion === null) {
            throw DominioException::porRegla('La gestión académica seleccionada no existe en el servidor institucional.');
        }

        $esNueva = $existente === null;
        if ($esNueva && ! $gestion->activa) {
            throw DominioException::porRegla("Solo se permiten nuevas inscripciones en la gestión activa ({$gestion->codigo} no está activa).");
        }

        $estudiante = Estudiante::query()->findOrFail((int) $datos['estudiante_id']);
        if ($esNueva && ! $estudiante->estaActivo()) {
            throw DominioException::porRegla("{$estudiante->nombreCompleto()} está inactivo y no puede inscribirse.");
        }

        $duplicada = Inscripcion::query()
            ->where('estudiante_id', $datos['estudiante_id'])
            ->where('asignatura_id', $datos['asignatura_id'])
            ->where('gestion_id', $datos['gestion_id'])
            ->when($existente !== null, fn (Builder $q) => $q->whereKeyNot($existente->getKey()))
            ->exists();

        if ($duplicada) {
            throw DominioException::porRegla("{$estudiante->nombreCompleto()} ya está inscrito(a) en esa asignatura durante la gestión {$gestion->codigo}.");
        }

        $datos['estado'] ??= 'inscrito';

        return $datos;
    }
}
