<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{   
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
