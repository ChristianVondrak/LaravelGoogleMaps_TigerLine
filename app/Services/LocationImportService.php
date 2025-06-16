<?php
// app/Services/LocationImportService.php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use JsonMachine\Items;
use Illuminate\Database\QueryException;
use Psr\Log\LoggerInterface;

class LocationImportService
{
    public function __construct(protected LoggerInterface $logger) {}

    /**
     * Importa GeoJSON en batches, haciendo upsert para no duplicar.
     *
     * @param  string      $path       Ruta al fichero GeoJSON
     * @param  string      $table      'zipcodes' o 'places'
     * @param  string      $type       'zipcode' o 'place'
     * @param  int|null    $batchSize  Número de filas por batch (null = valor por defecto)
     */
    public function import(string $path, string $table, string $type, ?int $batchSize = null): int
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Fichero no encontrado: {$path}");
        }

        // Batch por defecto
        $batchSize = $batchSize
            ?? ($type === 'zipcode' ? 50 : 500);

        ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::connection()->disableQueryLog();

        $iterator = Items::fromFile($path, ['pointer' => '/features']);
        $batch    = [];
        $total    = 0;

        foreach ($iterator as $feature) {
            $props    = $feature->properties ?? null;
            $geometry = $feature->geometry   ?? null;
            if (! $props || ! $geometry) {
                continue;
            }

            // GEOID
            $geoid = match ($type) {
                'zipcode' => $props->ZCTA5CE20 ?? ($props->GEOID20 ?? null),
                'place'   => $props->GEOID   ?? null,
                default   => null,
            };
            if (! $geoid) {
                continue;
            }

            $name     = $props->NAME ?? null;
            $geomJson = addslashes(json_encode($geometry));

            // Centroide: si es zipcode y vienen INTPTLAT20/INTPTLON20, úsalos
            if ($type === 'zipcode' && isset($props->INTPTLAT20, $props->INTPTLON20)) {
                $lat = (float) $props->INTPTLAT20;
                $lng = (float) $props->INTPTLON20;
            } else {
                // fallback a bounding-box center
                [$lng, $lat] = $this->calculateBoundingBoxCenter($geometry);
            }

            $batch[] = [
                'geoid'      => (int) $geoid,
                'name'       => $name,
                'boundary'   => DB::raw("ST_GeomFromGeoJSON('{$geomJson}')"),
                // **lat lon** (MySQL espera Y X para SRID=4326)
                'centroid'   => DB::raw(sprintf(
                    "ST_GeomFromText('POINT(%F %F)', 4326)",
                    $lat,
                    $lng
                )),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                $total += $this->flushBatch($batch, $table);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $total += $this->flushBatch($batch, $table, true);
        }

        return $total;
    }

    protected function flushBatch(array $batch, string $table, bool $isFinal = false): int
    {
        try {
            DB::table($table)
              ->upsert(
                  $batch,
                  ['geoid'],
                  ['name', 'boundary', 'centroid', 'updated_at']
              );

            $count = count($batch);
            $this->logger->info(sprintf(
                '%s batch de %d filas en "%s".',
                $isFinal ? 'Final' : 'Intermedio',
                $count,
                $table
            ));

            return $count;
        } catch (QueryException $e) {
            $msg = $e->errorInfo[2] ?? $e->getMessage();
            throw new \RuntimeException(
                sprintf('SQL error %sen batch upsert: %s', $isFinal ? 'final ' : '', $msg)
            );
        }
    }

    protected function calculateBoundingBoxCenter(object $geometry): array
    {
        $coords = $geometry->coordinates;
        $minLng = $minLat = INF;
        $maxLng = $maxLat = -INF;

        $rings = match ($geometry->type) {
            'Polygon'      => $coords,
            'MultiPolygon' => array_merge(...$coords),
            default        => [],
        };

        foreach ($rings as $ring) {
            foreach ($ring as [$lng, $lat]) {
                $minLng = min($minLng, $lng);
                $maxLng = max($maxLng, $lng);
                $minLat = min($minLat, $lat);
                $maxLat = max($maxLat, $lat);
            }
        }

        return [($minLng + $maxLng) / 2, ($minLat + $maxLat) / 2];
    }
}
