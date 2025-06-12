<?php
// app/Models/Location.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TarfinLabs\LaravelSpatial\Traits\HasSpatial;
use TarfinLabs\LaravelSpatial\Types\Point;
use TarfinLabs\LaravelSpatial\Casts\LocationCast;
use \Illuminate\Support\Facades\DB;

class Location extends Model
{
    use HasFactory, HasSpatial;

    protected $fillable = [
        'geoid',
        'name',
        'centroid',
    ];

    /** Define qué campos usa HasSpatial (solo Point). */
    protected array $spatialFields = ['centroid'];

    protected $casts = [
        'geoid'    => 'integer',
        'centroid' => LocationCast::class,
    ];

    /**
     * (Opcional) Accessor para exponer el boundary MultiPolygon si lo necesitas.
     */
    public function getBoundaryGeoJsonAttribute(): ?array
    {
        $wkb = $this->getRawOriginal('boundary');
        if (! $wkb) {
            return null;
        }
        // Convierte WKB a GeoJSON con ST_AsGeoJSON
        $geojson = DB::selectOne(
            "SELECT ST_AsGeoJSON(boundary) AS geojson FROM locations WHERE id = ?",
            [$this->id]
        )->geojson;
        return json_decode($geojson, true);
    }
}
