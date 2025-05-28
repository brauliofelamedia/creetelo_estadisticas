<?php

namespace App\Models;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        '_id',
        'contactId',
        'email',
        'currency',
        'amount',
        'status',
        'livemode',
        'entity_type',
        'entity_id',
        'provider_type',
        'source_type',
        'entity_resource_name',
        'subscription_id',
        'start_date',
        'end_date',
        'cancelled_at',
        'create_time',
        'contact_id'
    ];
    
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
