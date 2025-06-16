<?php
// app/Console/Commands/ImportLocations.php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocationImportService;

class ImportLocations extends Command
{
    protected $signature   = 'import:locations
                              {file                 : GeoJSON filename en storage/app/geojson}
                              {--type=              : Tipo: zipcode ó place}
                              {--batch= : Batch size (opcional, usa valor por defecto según tipo)}';
    protected $description = 'Importa GeoJSON a zipcodes o places';

    public function handle(LocationImportService $importer): int
    {
        // Asegura CLI sin límites
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $file      = $this->argument('file');
        $type      = strtolower($this->option('type')   ?? '');
        $batch     = $this->option('batch') !== null
                    ? (int) $this->option('batch')
                    : null;

        if (! in_array($type, ['zipcode', 'place'], true)) {
            $this->error('Usa --type=zipcode o --type=place');
            return self::FAILURE;
        }

        $table = $type === 'zipcode' ? 'zipcodes' : 'places';
        $path  = storage_path("app/geojson/{$file}");

        $this->info("Importando tipo '{$type}' en tabla '{$table}'" 
                    . ($batch ? " (batch={$batch})" : '')
                    . " desde {$path}");

        try {
            $count = $importer->import($path, $table, $type, $batch);
        } catch (\Throwable $e) {
            $this->error("⚠️  {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("✅ Importación completa: {$count} registros procesados.");
        return self::SUCCESS;
    }
}
