<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query = null)
    {
        $this->query = $query ?? Transaction::query();
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
            'Nombre de fuente',
            'Tipo de fuente',
            'Pago',
            'Fecha',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->name,
            $transaction->email ?? 'N/A',
            $transaction->amount,
            $transaction->status,
            $transaction->entity_resource_name,
            $transaction->source_type,
            $transaction->payment_provider,
            \Carbon\Carbon::parse($transaction->create_time)->format('Y-m-d H:i:s'),
        ];
    }
}
