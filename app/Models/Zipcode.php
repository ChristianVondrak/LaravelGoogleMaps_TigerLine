<?php
// app/Models/Zipcode.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zipcode extends Model
{
    // Asocia con la tabla "zipcodes"
    protected $table = 'zipcodes';

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
