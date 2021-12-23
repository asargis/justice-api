<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    public const TYPE_PROXY = 'proxy';

    public const TYPE_ESSENCE = 'essence';

    public const TYPE_ATTACHMENT = 'attachment';

    public const TYPE_PAYMENT = 'payment';

    private $name;

    private $path;

    private $type;

    private $appeal_id;

    private $page_count;


}
