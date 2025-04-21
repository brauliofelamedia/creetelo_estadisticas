<?php

namespace App\Models;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
        
    public function scopeFilterByTag($query, $tagId)
    {
        if ($tagId) {
            return $query->whereHas('tags', function ($q) use ($tagId) {
                $q->where('tags.id', $tagId);
            });
        }
        
        return $query;
    }
}
