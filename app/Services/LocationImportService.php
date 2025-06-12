<?php
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
     */
    public function import(string $path, int $batchSize = 500): int
    {
        if (! file_exists($path)) {
            throw new \RuntimeException("Fichero no encontrado: {$path}");
        }

        DB::connection()->disableQueryLog();
        $iterator = Items::fromFile($path, ['pointer' => '/features']);

        $batch = [];
        $total = 0;

        foreach ($iterator as $feature) {
            if (! isset($feature->properties, $feature->geometry)) {
                continue;
            }

            $props = $feature->properties;
            $geoid = $props->GEOID ?? ($props->ZCTA5CE20 ?? null);
            if (! $geoid) {
                continue;
            }

            $geomJson = addslashes(json_encode($feature->geometry));
            [$lng, $lat] = $this->calculateBoundingBoxCenter($feature->geometry);

            $batch[] = [
                'geoid'    => $geoid,
                'name'     => $props->NAME ?? null,

                // boundary: GeoJSON → Geometry → WKT → Geometry con SRID 4326
                'boundary' => DB::raw(<<<SQL
                    ST_GeomFromText(
                      ST_AsText(
                        ST_GeomFromGeoJSON('{$geomJson}')
                      ),
                      4326
                    )
                SQL
                ),

                // centroid: WKT POINT(lat lon) con SRID 4326
                'centroid' => DB::raw(sprintf(
                    "ST_GeomFromText('POINT(%F %F)', 4326)",
                    $lat,
                    $lng
                )),

                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                $this->flushBatch($batch, $total);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            $this->flushBatch($batch, $total, true);
        }

        return $total;
    }

    protected function flushBatch(array $batch, int &$total, bool $isFinal = false): void
    {
        try {
            DB::table('locations')->upsert(
                $batch,
                ['geoid'],
                ['name', 'boundary', 'centroid', 'updated_at']
            );
            $total += count($batch);
            $this->logger->info(sprintf(
                '%s batch de %d features procesado.',
                $isFinal ? 'Final' : 'Intermedio',
                count($batch)
            ));
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

        return [
            ($minLng + $maxLng) / 2,
            ($minLat + $maxLat) / 2,
        ];
    }
}
