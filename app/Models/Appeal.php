<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_CIVIL = 'civil';

    public const TYPE_ADMINISTRATIVE = 'administrative';

    public function applicant()
    {
        return $this->hasOne(Applicant::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
