@extends('layouts.app')

@section('titulo', 'Inscripciones')

@section('contenido')
    <x-encabezado titulo="Inscripciones" servidor="docker" :nuevo="route('inscripciones.create')" texto-nuevo="Nueva inscripción" />

    <p class="tenue">La columna <em>Gestión</em> se obtiene del servidor del docente (relación entre servidores resuelta en la aplicación).</p>

    <x-buscador placeholder="Estudiante, código o sigla">
        <select name="gestion_id">
            <option value="">Todas las gestiones</option>
            @foreach ($opcionesGestion as $id => $texto)
                <option value="{{ $id }}" @selected((string) request('gestion_id') === (string) $id)>{{ $texto }}</option>
            @endforeach
        </select>
    </x-buscador>

    <div class="tabla-envoltura">
        <table>
            <thead>
            <tr><th>Gestión</th><th>Estudiante</th><th>Asignatura</th><th>Fecha</th><th class="num">Nota acumulada</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($inscripciones as $i)
                @php($nota = $i->notaFinal())
                <tr>
                    <td>
                        @if ($g = $gestiones->get($i->gestion_id))
                            <span class="etiqueta-servidor docente">{{ $g->codigo }}</span>
                        @else
                            <span class="chip rojo">#{{ $i->gestion_id }} no encontrada</span>
                        @endif
                    </td>
                    <td><a href="{{ route('estudiantes.show', $i->estudiante_id) }}">{{ $i->estudiante?->nombreCompleto() }}</a><br><small class="tenue">{{ $i->estudiante?->codigo }}</small></td>
                    <td><code>{{ $i->asignatura?->sigla }}</code> {{ $i->asignatura?->nombre }}</td>
                    <td>{{ $i->fecha_inscripcion?->format('d/m/Y') }}</td>
                    <td class="num">{{ number_format($nota->acumulada(), 2) }} <small class="tenue">({{ $nota->porcentajeEvaluado() }}%)</small></td>
                    <td><span @class(['chip', 'verde' => $i->estado === 'aprobado', 'rojo' => in_array($i->estado, ['reprobado', 'retirado'], true)])>{{ \App\Models\Docker\Inscripcion::ESTADOS[$i->estado] }}</span></td>
                    <td>
                        <x-acciones :editar="route('inscripciones.edit', $i)" :eliminar="route('inscripciones.destroy', $i)"
                                    confirmar="¿Eliminar la inscripción? También se eliminarán sus calificaciones.">
                            @unless ($i->estaRetirado())
                                <a href="{{ route('calificaciones.create', ['inscripcion_id' => $i->id]) }}" class="btn btn-sm">+ Nota</a>
                            @endunless
                        </x-acciones>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No se encontraron inscripciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $inscripciones->links() }}
@endsection
