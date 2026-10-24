<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DominioException;
use App\Models\Docker\Calificacion;
use App\Models\Docker\Inscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CalificacionService extends ServicioCrudBase
{
    protected function claseModelo(): string
    {
        return Calificacion::class;
    }

    public function nombreEntidad(): string
    {
        return 'calificación';
    }

    protected function relacionesListado(): array
    {
        return ['inscripcion.estudiante', 'inscripcion.asignatura'];
    }

    protected function aplicarFiltros(Builder $consulta, array $filtros): void
    {
        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $consulta->whereHas('inscripcion', function (Builder $i) use ($texto): void {
                $i->whereHas('estudiante', fn (Builder $e) => $e
                    ->where('nombres', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%")
                    ->orWhere('codigo', 'like', "%{$texto}%"))
                    ->orWhereHas('asignatura', fn (Builder $a) => $a->where('sigla', 'like', "%{$texto}%"));
            });
        }

        if (! empty($filtros['inscripcion_id'])) {
            $consulta->where('inscripcion_id', (int) $filtros['inscripcion_id']);
        }
    }

    protected function ordenarListado(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('inscripcion_id')->orderBy('tipo');
    }

    protected function antesDeGuardar(array $datos, ?Model $existente): array
    {
        $inscripcion = Inscripcion::query()->findOrFail((int) $datos['inscripcion_id']);

        if ($inscripcion->estaRetirado()) {
            throw DominioException::porRegla('No se pueden registrar notas en una inscripción retirada.');
        }

        $repetida = Calificacion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->where('tipo', $datos['tipo'])
            ->when($existente !== null, fn (Builder $q) => $q->whereKeyNot($existente->getKey()))
            ->exists();

        if ($repetida) {
            $tipo = Calificacion::TIPOS[$datos['tipo']] ?? $datos['tipo'];
            throw DominioException::porRegla("Esa inscripción ya tiene registrada la nota de: {$tipo}. Edítela en lugar de crear otra.");
        }

        $datos['nota'] = round((float) $datos['nota'], 2);

        return $datos;
    }
}
