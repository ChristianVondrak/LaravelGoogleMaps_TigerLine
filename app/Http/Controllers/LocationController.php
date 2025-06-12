<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationController extends Controller
{
    /**
     * Devuelve la vista con el mapa y el buscador.
     */
    public function map()
    {
        return view('locations.map');
    }

    /**
     * Busca por geoid (ZIP) o por nombre (LIKE) y devuelve
     * boundary y centroid de un solo registro en JSON.
     */
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if ($q === '') {
            return response()->json(['error' => 'Introduce un término de búsqueda.'], 422);
        }

        $query = Location::query();

        if (ctype_digit($q)) {
            // ZIP codes numéricos
            $query->where('geoid', $q);
        } else {
            // Búsqueda de texto en el nombre
            $query->where('name', 'LIKE', "%{$q}%");
        }

        try {
            $loc = $query->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => "No se encontró «{$q}»."] , 404);
        }

        // Extraemos sólo el "coordinates" del GeoJSON
        $geo = $loc->boundary_geo_json; 
        $coords = $geo['coordinates'] ?? [];

        return response()->json([
            'geoid'    => $loc->geoid,
            'name'     => $loc->name,
            'boundary' => $coords,
            'centroid' => [
                $loc->centroid->getLng(),
                $loc->centroid->getLat(),
            ],
        ]);
    }

     /**
     * Devuelve hasta 5 sugerencias (name o geoid) para autocompletar.
     */
    public function autocomplete(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $query = Location::query();
        if (ctype_digit($q)) {
            $query->where('geoid', 'like', "{$q}%");
        } else {
            $query->where('name', 'LIKE', "%{$q}%");
        }

        $results = $query
            ->limit(5)
            ->get(['geoid','name'])
            ->map(fn($loc) => [
                // Si hay nombre, sugerimos nombre; si no, sugerimos ZIP
                'value' => $loc->name ?: $loc->geoid,
            ]);

        return response()->json($results);
    }
}
