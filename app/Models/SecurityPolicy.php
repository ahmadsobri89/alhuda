<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];
}
