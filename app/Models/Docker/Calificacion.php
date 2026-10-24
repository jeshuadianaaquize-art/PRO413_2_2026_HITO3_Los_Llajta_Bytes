<?php

declare(strict_types=1);

namespace App\Models\Docker;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends ModeloDocker
{
    public const TIPOS = [
        'primer_parcial' => 'Primer parcial (20%)',
        'segundo_parcial' => 'Segundo parcial (20%)',
        'practicas' => 'Prácticas (20%)',
        'examen_final' => 'Examen final (40%)',
    ];

    protected $table = 'calificaciones';

    protected $fillable = ['inscripcion_id', 'tipo', 'nota', 'observacion'];

    protected function casts(): array
    {
        return ['inscripcion_id' => 'integer', 'nota' => 'float'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function nombreTipo(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function etiqueta(): string
    {
        return sprintf('%s: %.2f', $this->nombreTipo(), $this->nota);
    }
}
