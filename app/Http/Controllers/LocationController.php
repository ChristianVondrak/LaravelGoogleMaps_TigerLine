<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Zipcode;
use App\Models\Place;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationController extends Controller
{
    /**
     * Muestra la vista del mapa y buscador.
     */
    public function map()
    {
        return view('locations.map');
    }

    /**
     * Endpoint GET /search?term=...
     */
    public function search(Request $request)
    {
        $searchTerm = trim($request->get('term', ''));

        if ($searchTerm === '') {
            return response()->json([
                'error' => 'Introduce un término de búsqueda.'
            ], 422);
        }

        // Elige modelo y condición
        if (ctype_digit($searchTerm)) {
            $modelClass = Zipcode::class;
            $query      = $modelClass::selectRaw(implode(',', [
                'geoid',
                'name',
                'ST_AsGeoJSON(boundary) AS boundary_geojson',
                'ST_AsGeoJSON(centroid) AS centroid_geojson',
            ]))
            ->where('geoid', (int)$searchTerm);
        } else {
            $modelClass = Place::class;
            $query      = $modelClass::selectRaw(implode(',', [
                'geoid',
                'name',
                'ST_AsGeoJSON(boundary) AS boundary_geojson',
                'ST_AsGeoJSON(centroid) AS centroid_geojson',
            ]))
            ->where('name', 'LIKE', "%{$searchTerm}%");
        }

        $record = $query->first();

        if (! $record) {
            return response()->json([
                'error' => "No se encontró «{$searchTerm}».",
            ], 404);
        }

        $boundaryCoords = json_decode($record->boundary_geojson, true)['coordinates'] ?? [];
        $centroidCoords = json_decode($record->centroid_geojson, true)['coordinates'] ?? [];

        return response()->json([
            'geoid'    => $record->geoid,
            'name'     => $record->name,
            'boundary' => $boundaryCoords,
            'centroid' => $centroidCoords,
        ]);
    }

    /**
     * Endpoint GET /autocomplete?term=...
     */
    public function autocomplete(Request $request)
    {
        $searchTerm = trim($request->get('term', ''));
        if ($searchTerm === '') {
            return response()->json([]);
        }

        if (ctype_digit($searchTerm)) {
            $results = Zipcode::where('geoid', 'like', "{$searchTerm}%")
                ->limit(5)
                ->get(['geoid', 'name']);
        } else {
            $results = Place::where('name', 'LIKE', "%{$searchTerm}%")
                ->limit(5)
                ->get(['geoid', 'name']);
        }

        $suggestions = $results->map(function ($item) {
            return ['value' => $item->name ?: (string)$item->geoid];
        });

        return response()->json($suggestions);
    }
}
