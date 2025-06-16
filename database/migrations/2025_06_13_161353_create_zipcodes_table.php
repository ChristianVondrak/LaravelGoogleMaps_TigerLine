<?php
// database/migrations/2025_06_14_000001_create_zipcodes_table.php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zipcodes', function (Blueprint $table) {
            $table->id();

            // GeoID numérico
            $table->unsignedInteger('geoid')
                  ->unique()
                  ->comment('Código postal como entero');

            $table->string('name', 100)
                  ->nullable()
                  ->comment('Etiqueta opcional');

            // Centroide como POINT
            $table->geometry('centroid', 'Point', 4326)
                  ->comment('Centroide del ZCTA (Point SRID 4326)');
            $table->spatialIndex('centroid');

            $table->timestamps();
        });

        // Boundary como MultiPolygon + índice espacial
        DB::statement(<<<SQL
            ALTER TABLE zipcodes
            ADD COLUMN boundary MULTIPOLYGON NOT NULL SRID 4326;
        SQL
        );
        DB::statement('ALTER TABLE zipcodes ADD SPATIAL INDEX boundary_spatial_idx (boundary)');
    }

    public function down(): void
    {
        Schema::dropIfExists('zipcodes');
    }
};
