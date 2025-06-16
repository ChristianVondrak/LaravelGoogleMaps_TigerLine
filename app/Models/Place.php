<?php
// app/Models/Place.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    // Asocia con la tabla "places"
    protected $table = 'places';

    // Llave primaria autoincremental
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'geoid',
        'name',
        'boundary',
        'centroid',
    ];

    // Casting básico de atributos
    protected $casts = [
        'geoid' => 'integer',
        'name'  => 'string',
    ];
}
