@extends('layouts.app')

@section('titulo', 'Boletín · '.$estudiante->nombreCompleto())

@section('contenido')
    <x-encabezado :titulo="'Boletín de '.$estudiante->nombreCompleto()" servidor="ambos">
        <a href="{{ route('estudiantes.edit', $estudiante) }}" class="btn">Editar datos</a>
        <a href="{{ route('estudiantes.index') }}" class="btn">Volver</a>
    </x-encabezado>

    <div class="kpis">
        <div class="kpi"><div class="rotulo">Código</div><div class="valor">{{ $estudiante->codigo }}</div></div>
        <div class="kpi"><div class="rotulo">CI</div><div class="valor">{{ $estudiante->ci }}</div></div>
        <div class="kpi"><div class="rotulo">Edad</div><div class="valor">{{ $estudiante->edad() }}</div></div>
        <div class="kpi">
            <div class="rotulo">Promedio general</div>
            <div class="valor">{{ $boletin['promedioGeneral'] !== null ? number_format($boletin['promedioGeneral'], 2) : '—' }}</div>
        </div>
    </div>

    <p class="tenue">
        Inscripciones, asignaturas y notas provienen del <strong>servidor Docker</strong>; el código de la gestión y el
        docente de cada curso provienen del <strong>servidor del docente</strong>. Se unen en la aplicación con mapas
        <code>id =&gt; registro</code> (<code>keyBy</code>) y se agrupan por gestión (<code>groupBy</code>).
    </p>

    @forelse ($boletin['gestiones'] as $bloque)
        <section class="panel">
            <h2>
                Gestión {{ $bloque['gestion'] }}
                @if ($bloque['promedio'] !== null)
                    <span class="chip">Promedio: {{ number_format($bloque['promedio'], 2) }}</span>
                @endif
            </h2>
            <div class="tabla-envoltura">
                <table>
                    <thead>
                    <tr>
                        <th>Asignatura</th><th>Docente</th>
                        @foreach (\App\Models\Docker\Calificacion::TIPOS as $rotulo)
                            <th class="num">{{ $rotulo }}</th>
                        @endforeach
                        <th class="num">Acumulada</th><th>Situación</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($bloque['materias'] as $m)
                        <tr>
                            <td><strong>{{ $m['sigla'] }}</strong> <span class="tenue">{{ $m['nombre'] }}</span></td>
                            <td>{{ $m['docente'] }}</td>
                            @foreach (array_keys(\App\Models\Docker\Calificacion::TIPOS) as $tipo)
                                <td class="num">{{ isset($m['notas'][$tipo]) ? number_format($m['notas'][$tipo], 2) : '—' }}</td>
                            @endforeach
                            <td class="num"><strong>{{ number_format($m['acumulada'], 2) }}</strong> <small class="tenue">({{ $m['porcentaje'] }}%)</small></td>
                            <td>
                                <span @class([
                                    'chip',
                                    'verde' => $m['situacion'] === 'aprobado',
                                    'rojo' => in_array($m['situacion'], ['reprobado', 'retirado'], true),
                                    'ambar' => $m['situacion'] === 'en curso',
                                ])>{{ ucfirst($m['situacion']) }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="alerta aviso">El estudiante todavía no tiene inscripciones.</div>
    @endforelse
@endsection
