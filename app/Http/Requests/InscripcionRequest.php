<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Docker\Inscripcion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InscripcionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'estudiante_id' => ['required', 'integer', 'exists:mysql_docker.estudiantes,id'],
            'asignatura_id' => ['required', 'integer', 'exists:mysql_docker.asignaturas,id'],
            // La gestión se valida contra el OTRO servidor (mysql_docente)
            'gestion_id' => ['required', 'integer', 'exists:mysql_docente.gestiones_academicas,id'],
            'fecha_inscripcion' => ['required', 'date', 'before_or_equal:today'],
            'estado' => ['required', Rule::in(array_keys(Inscripcion::ESTADOS))],
        ];
    }
}
