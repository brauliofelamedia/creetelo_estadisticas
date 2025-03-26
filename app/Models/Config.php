<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Config extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'site_name',
        'primary_color',
        'secondary_color',
        'logo',
        'favicon',
        'code',
        'company_id',
        'location_id',
        'refresh_token',
        'access_token'
    ];
}
