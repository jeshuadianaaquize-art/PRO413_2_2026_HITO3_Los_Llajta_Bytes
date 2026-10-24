<?php

declare(strict_types=1);

namespace App\Models\Docker;

use App\Support\NotaFinal;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * gestion_id apunta a una gestión académica que vive en el OTRO servidor
 * (mysql_docente). Esa relación no se declara aquí como relación Eloquent:
 * se resuelve en los servicios con un mapa id => gestión (ver
 * App\Services\ResolutorRelacionesCruzadas).
 */
class Inscripcion extends ModeloDocker
{
    public const ESTADOS = [
        'inscrito' => 'Inscrito',
        'retirado' => 'Retirado',
        'aprobado' => 'Aprobado',
        'reprobado' => 'Reprobado',
    ];

    protected $table = 'inscripciones';

    protected $fillable = ['estudiante_id', 'asignatura_id', 'gestion_id', 'fecha_inscripcion', 'estado'];

    protected function casts(): array
    {
        return [
            'estudiante_id' => 'integer',
            'asignatura_id' => 'integer',
            'gestion_id' => 'integer',
            'fecha_inscripcion' => 'date',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function asignatura(): BelongsTo
    {
        return $this->belongsTo(Asignatura::class, 'asignatura_id');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'inscripcion_id');
    }

    public function notaFinal(): NotaFinal
    {
        // Colección -> arreglo asociativo tipo => nota
        return NotaFinal::desde($this->calificaciones->pluck('nota', 'tipo'));
    }

    public function estaRetirado(): bool
    {
        return $this->estado === 'retirado';
    }

    public function etiqueta(): string
    {
        $estudiante = $this->estudiante?->nombreCompleto() ?? "Estudiante #{$this->estudiante_id}";
        $asignatura = $this->asignatura?->sigla ?? "Asignatura #{$this->asignatura_id}";

        return "{$estudiante} · {$asignatura} (gestión #{$this->gestion_id})";
    }
}
