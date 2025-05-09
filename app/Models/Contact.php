<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nnjeim\World\Models\Country;

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

    public function getCountryNameAttribute()
    {
        $country = Country::where('iso2', $this->country)->first();
        return $country ? $country->name : null;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'contact_id', 'contactId');
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'contactId', 'id');
    }

}
