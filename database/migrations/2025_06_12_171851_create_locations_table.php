<?php
// database/migrations/2025_06_12_000000_create_locations_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('geoid')->index()->comment('ZCTA o GEOID numérico');
            $table->string('name')->nullable()->comment('Nombre opcional de la zona');

            // Ahora como geometría POINT, no geography:
            $table->geometry('centroid', 'Point', 4326)
                  ->comment('Centroide del polígono (geometry Point SRID 4326)');
            $table->spatialIndex('centroid');

            $table->timestamps();
        });

        // Polígono original (MultiPolygon) vía SQL:
        DB::statement(<<<SQL
            ALTER TABLE locations
            ADD COLUMN boundary MULTIPOLYGON NOT NULL SRID 4326;
        SQL
        );
        DB::statement('ALTER TABLE locations ADD SPATIAL INDEX boundary_spatial_idx (boundary)');
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
