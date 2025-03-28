<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Transactions;
use App\Models\Transaction;
use Carbon\Carbon;

class UpdateTransactionsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 60;
    public $tries = 3;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        try {
            $transactions = new Transactions();
            $transactions = $transactions->get();
            $data = collect($transactions->getData());
            $total = $data['totalCount'];
        
            $limit = 100;
            $offset = ceil($total / 100);
            
            for ($offset = 0; $offset < $total; $offset += 100) {
            $currentBatch = floor($offset / $limit) + 1;
        
            $transactions = new Transactions();
            $transactions = $transactions->get($offset);
            $data = collect($transactions->getData());
        
            foreach($data['data'] as $item){
        
                if ($item->entitySourceType != 'membership') {
                    continue;
                }
        
                if (!in_array($item->amount, [39, 390])) {
                    continue;
                }
        
                // Skip if transaction already exists
                if (Transaction::where('_id', $item->_id)->exists()) {
                    continue;
                }
                
                $trans = new Transaction();
                $trans->_id = $item->_id ?? '';
                $trans->contactId = $item->contactId ?? '';
                $trans->name = $item->contactName ?? '';
                $trans->email = $item->contactEmail ?? '';
                $trans->currency = $item->currency ?? '';
                $trans->amount = $item->amount ?? 0;
                $trans->status = $item->status ?? '';
                $trans->livemode = $item->liveMode ?? false;
                $trans->entity_resource_name = $item->entitySourceName ?? '';
                $trans->entity_type = $item->entityType ?? '';
                $trans->entity_source_type = $item->entitySourceType ?? '';
                $trans->entity_id = $item->entityId ?? '';
                $trans->subscription_id = $item->subscriptionId ?? '';
                $trans->charge_id = $item->subscriptionId ?? '';
                $trans->source_type = $item->entitySourceType ?? '';
                $trans->payment_provider = $item->paymentProviderType ?? '';
                $trans->create_time = Carbon::parse($item->createdAt)->format('Y-m-d');
                $trans->contact_id = $contact->id ?? null;
                $trans->save();
            }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Error al actualizar transacciones'
        ], 500);
    }
}
