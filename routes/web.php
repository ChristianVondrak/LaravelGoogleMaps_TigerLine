<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;

// Muestra la página con el mapa y el buscador
Route::get('/', [LocationController::class, 'map'])
     ->name('home');

// Endpoint que busca por ZIP o nombre y devuelve el GeoJSON
Route::get('/search', [LocationController::class, 'search'])
     ->name('locations.search');

// Endpoint AJAX para sugerencias (autocompletar)
Route::get('/autocomplete', [LocationController::class, 'autocomplete'])
     ->name('locations.autocomplete');