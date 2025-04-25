<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SubscriptionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?? Subscription::query();
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Correo',
            'Monto',
            'Estado',
            'Método de pago',
            'ID de subscripción',
            'Tipo de fuente',
            'Nombre de fuente',
            'Fecha de inicio',
            'Fecha de vencimiento',
            'Fecha de creación',
        ];
    }

    public function map($subscription): array
    {
        return [
            ucfirst($subscription->contact->first_name). ' ' . ucfirst($subscription->contact->last_name),
            $subscription->contact->email ?? 'N/A',
            $subscription->amount ? $subscription->amount : 'N/A',
            $subscription->status,
            $subscription->provider_type,
            $subscription->subscription_id,
            $subscription->source_type,
            $subscription->entity_resource_name,
            \Carbon\Carbon::parse($subscription->start_date)->format('Y-m-d'),
            \Carbon\Carbon::parse($subscription->end_date)->format('Y-m-d'),
            \Carbon\Carbon::parse($subscription->create_time)->format('Y-m-d'),
        ];
    }
}
