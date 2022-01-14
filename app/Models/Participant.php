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
        'identity_type',
        'passport_series',
        'passport_number',
        'passport_issued_date',
        'passport_issued_by',
        'drivers_license_series',
        'drivers_license_number',
        'vehicle_series',
        'vehicle_number',
        'email',
        'phone',
    ];
}
