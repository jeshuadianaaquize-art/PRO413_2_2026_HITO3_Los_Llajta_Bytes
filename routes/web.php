<?php

declare(strict_types=1);

use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\InscripcionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ---------- Módulo 3: inscripciones y calificaciones (servidor MySQL Docker) ----------
Route::resource('inscripciones', InscripcionController::class)->except('show')
    ->parameters(['inscripciones' => 'inscripcion']);
Route::resource('calificaciones', CalificacionController::class)->except('show')
    ->parameters(['calificaciones' => 'calificacion']);
