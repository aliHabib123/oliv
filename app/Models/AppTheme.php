<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppTheme extends Model
{
    protected $fillable = [
        'name',
        'default_config',
    ];

    protected $casts = [
        'default_config' => 'array',
    ];
}
