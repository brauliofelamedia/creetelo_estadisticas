<?php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Database\Eloquent\Builder;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?? Contact::query();
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Apellidos',
            'Correo',
            'Teléfono',
            'País',
            'Estado',
            'Ciudad',
            'Fecha registro',
            'Valor del lead',
            'Tags'
        ];
    }

    public function map($contact): array
    {
        $totalAmount = $contact->transactions->where('status', 'succeeded')->sum('amount');
        
        return [
            ucfirst($contact->first_name),
            ucfirst($contact->last_name),
            $contact->email,
            $contact->phone,
            $contact->country,
            $contact->state,
            $contact->city,
            \Carbon\Carbon::parse($contact->date_added)->format('Y-m-d H:i:s'),
            $totalAmount,
            is_array($contact->tags) ? implode(', ', $contact->tags) : ''
        ];
    }
}
