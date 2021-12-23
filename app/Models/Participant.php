<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'procedural_status',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'sex',
        'birthplace',
        'registration_zipcode',
        'registration_address',
        'resident_zipcode',
        'resident_address',
        'snils',
        'inn',
    ];
}
