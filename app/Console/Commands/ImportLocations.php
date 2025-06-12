<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\LocationImportService;

class ImportLocations extends Command
{
    protected $signature   = 'import:locations
                              {file=places.geojson : GeoJSON filename in storage/app/geojson}';
    protected $description = 'Importa GeoJSON a la tabla locations';

    public function __construct(protected LocationImportService $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        // Sin límites de memoria ni tiempo en CLI
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '0');

        // Desactiva el query log de Laravel
        DB::connection()->disableQueryLog();

        $filename = $this->argument('file');
        $path     = storage_path("app/geojson/{$filename}");

        $this->info("Iniciando import desde {$path}...");

        try {
            $total = $this->importer->import($path);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("✅ Importación completada. Total features: {$total}");
        return self::SUCCESS;
    }
}
