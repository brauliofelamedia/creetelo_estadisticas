<?php

namespace App\Models;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id','id');
    }
}
