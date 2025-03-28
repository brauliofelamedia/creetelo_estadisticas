<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'lead_id',
        'phoneLabel',
        'country',
        'address',
        'source',
        'type',
        'locationId',
        'website',
        'dnd',
        'state',
        'businessName',
        'customFields',
        'tags',
        'dateAdded',
        'additionalEmails',
        'phone',
        'companyName',
        'additionalPhones',
        'dateUpdated',
        'city',
        'dateOfBirth',
        'firstNameLowerCase',
        'lastNameLowerCase',
        'email',
        'assignedTo',
        'followers',
        'validEmail',
        'postalCode',
        'businessId'
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
