<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Docker\Calificacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CalificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'inscripcion_id' => ['required', 'integer', 'exists:mysql_docker.inscripciones,id'],
            'tipo' => ['required', Rule::in(array_keys(Calificacion::TIPOS))],
            'nota' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
