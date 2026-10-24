@extends('layouts.app')

@php($editando = $calificacion->exists)

@section('titulo', $editando ? 'Editar calificación' : 'Nueva calificación')

@section('contenido')
    <x-encabezado :titulo="$editando ? 'Editar calificación' : 'Nueva calificación'" servidor="docker" />

    <form method="POST" class="panel formulario" novalidate
          action="{{ $editando ? route('calificaciones.update', $calificacion) : route('calificaciones.store') }}">
        @csrf
        @if ($editando) @method('PUT') @endif

        <x-seleccion name="inscripcion_id" label="Inscripción (gestión · asignatura · estudiante)" :opciones="$opcionesInscripcion"
                     :value="$calificacion->inscripcion_id" required ayuda="No se muestran inscripciones retiradas." />
        <div class="filas">
            <x-seleccion name="tipo" label="Tipo de evaluación" :opciones="\App\Models\Docker\Calificacion::TIPOS" :value="$calificacion->tipo" required />
            <x-campo name="nota" label="Nota (0 a 100)" type="number" :value="$calificacion->nota" required min="0" max="100" step="0.01" />
        </div>
        <x-campo name="observacion" label="Observación" :value="$calificacion->observacion" maxlength="255" />

        <div class="botonera">
            <button type="submit" class="btn btn-primario">Guardar</button>
            <a href="{{ route('calificaciones.index') }}" class="btn">Cancelar</a>
        </div>
    </form>
@endsection
