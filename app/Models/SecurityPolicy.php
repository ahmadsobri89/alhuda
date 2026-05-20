<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityPolicy extends Model
{
    protected $fillable = ['key', 'label', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
