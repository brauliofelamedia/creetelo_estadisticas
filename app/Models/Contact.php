<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'contact_id',
        'phone',
        'email',
        'country',
        'address',
        'source',
        'type',
        'location_id',
        'website',
        'dnd',
        'state',
        'business_name',
        'custom_fields',
        'tags',
        'date_added',
        'additional_emails',
        'company_name',
        'additional_phones',
        'date_update',
        'city',
        'date_of_birth',
        'first_name',
        'last_name',
        'assigned_to',
        'followers',
        'valid_email',
        'postal_code',
        'business_id'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function getFullNameAttribute()
    {
        return (!empty($this->first_name) && !empty($this->last_name)) 
            ? ucwords($this->first_name . ' ' . $this->last_name)
            : '-';
    }
}
