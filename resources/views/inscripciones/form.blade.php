@extends('layouts.app')

@php($editando = $inscripcion->exists)

@section('titulo', $editando ? 'Editar inscripción' : 'Nueva inscripción')

@section('contenido')
    <x-encabezado :titulo="$editando ? 'Editar inscripción' : 'Nueva inscripción'" servidor="docker" />

    <form method="POST" class="panel formulario" novalidate
          action="{{ $editando ? route('inscripciones.update', $inscripcion) : route('inscripciones.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <div class="filas">
            <x-seleccion name="estudiante_id" label="Estudiante" :opciones="$opcionesEstudiante" :value="$inscripcion->estudiante_id" required />
            <x-seleccion name="asignatura_id" label="Asignatura" :opciones="$opcionesAsignatura" :value="$inscripcion->asignatura_id" required />
            <x-seleccion name="gestion_id" label="Gestión académica" :opciones="$opcionesGestion" :value="$inscripcion->gestion_id" required
                         ayuda="Viene del servidor MySQL del docente. Solo se aceptan nuevas inscripciones en la gestión activa." />
            <x-campo name="fecha_inscripcion" label="Fecha de inscripción" type="date" :value="$inscripcion->fecha_inscripcion?->format('Y-m-d')" required />
            <x-seleccion name="estado" label="Estado" :opciones="\App\Models\Docker\Inscripcion::ESTADOS" :value="$inscripcion->estado" :vacio="false" required />
        </div>

        <div class="botonera">
            <button type="submit" class="btn btn-primario">Guardar</button>
            <a href="{{ route('inscripciones.index') }}" class="btn">Cancelar</a>
        </div>
    </form>
@endsection
