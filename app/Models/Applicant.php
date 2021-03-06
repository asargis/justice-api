<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'inn',
        'legal_zipcode',
        'legal_address',
        'location_zipcode',
        'location_address',
        'ogrn',
        'kpp',
        'procedural_status',
        'email',
        'phone'
    ];
}
