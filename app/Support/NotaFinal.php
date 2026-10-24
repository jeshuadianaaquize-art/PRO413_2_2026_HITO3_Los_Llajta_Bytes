<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Objeto de valor que calcula la nota final ponderada de una inscripción.
 *
 * POO - Encapsulamiento: las notas y los pesos son privados e inmutables
 * (readonly); solo se exponen resultados calculados mediante métodos.
 *
 * Estructura de datos: arreglo asociativo  tipo_evaluacion => nota
 * (por ejemplo ['primer_parcial' => 78.0, 'examen_final' => 75.0]).
 */
final class NotaFinal
{
    /** Ponderación de cada evaluación (suma 100 %). */
    public const PESOS = [
        'primer_parcial' => 20,
        'segundo_parcial' => 20,
        'practicas' => 20,
        'examen_final' => 40,
    ];

    public const NOTA_MINIMA_APROBACION = 51.0;

    /** @param array<string, float> $notas */
    private function __construct(private readonly array $notas)
    {
    }

    /**
     * @param iterable<string, float|int|string> $notasPorTipo
     */
    public static function desde(iterable $notasPorTipo): self
    {
        $limpias = [];
        foreach ($notasPorTipo as $tipo => $nota) {
            if (array_key_exists($tipo, self::PESOS)) {
                $limpias[$tipo] = (float) $nota;
            }
        }

        return new self($limpias);
    }

    /** Nota acumulada con las evaluaciones registradas hasta ahora (0-100). */
    public function acumulada(): float
    {
        $total = 0.0;
        foreach ($this->notas as $tipo => $nota) {
            $total += $nota * self::PESOS[$tipo] / 100;
        }

        return round($total, 2);
    }

    /** Porcentaje del total de la materia que ya fue evaluado. */
    public function porcentajeEvaluado(): int
    {
        return array_sum(array_intersect_key(self::PESOS, $this->notas));
    }

    public function estaCompleta(): bool
    {
        return $this->porcentajeEvaluado() === 100;
    }

    /** Devuelve 'aprobado', 'reprobado' o 'en curso'. */
    public function situacion(): string
    {
        if (! $this->estaCompleta()) {
            return 'en curso';
        }

        return $this->acumulada() >= self::NOTA_MINIMA_APROBACION ? 'aprobado' : 'reprobado';
    }

    public function nota(string $tipo): ?float
    {
        return $this->notas[$tipo] ?? null;
    }

    /** @return array<string, float> */
    public function comoArreglo(): array
    {
        return $this->notas;
    }
}
