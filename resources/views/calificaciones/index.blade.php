@extends('layouts.app')

@section('titulo', 'Calificaciones')

@section('contenido')
    <x-encabezado titulo="Calificaciones" servidor="docker" :nuevo="route('calificaciones.create')" texto-nuevo="Nueva calificación" />

    <p class="tenue">Ponderación: primer parcial 20 %, segundo parcial 20 %, prácticas 20 %, examen final 40 %. Nota mínima de aprobación: 51.</p>

    <x-buscador placeholder="Estudiante, código o sigla" />

    <div class="tabla-envoltura">
        <table>
            <thead>
            <tr><th>Estudiante</th><th>Asignatura</th><th>Evaluación</th><th class="num">Nota</th><th>Observación</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($calificaciones as $c)
                <tr>
                    <td>{{ $c->inscripcion?->estudiante?->nombreCompleto() }}</td>
                    <td><code>{{ $c->inscripcion?->asignatura?->sigla }}</code></td>
                    <td>{{ $c->nombreTipo() }}</td>
                    <td class="num"><span @class(['chip', 'verde' => $c->nota >= 51, 'rojo' => $c->nota < 51])>{{ number_format($c->nota, 2) }}</span></td>
                    <td class="tenue">{{ $c->observacion }}</td>
                    <td><x-acciones :editar="route('calificaciones.edit', $c)" :eliminar="route('calificaciones.destroy', $c)" confirmar="¿Eliminar esta calificación?" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">No se encontraron calificaciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $calificaciones->links() }}
@endsection
