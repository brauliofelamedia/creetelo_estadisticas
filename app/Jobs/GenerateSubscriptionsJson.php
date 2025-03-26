<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Subscriptions;
use Carbon\Carbon;

class GenerateSubscriptionsJson implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        try {
            $filePath = storage_path('app/subscriptions.json');
            $subscriptions = new Subscriptions();
            
            $response = $subscriptions->get(0);
            $responseData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            $totalCount = $responseData['data']['original']['totalCount'];
            $numberPage = (int)ceil($totalCount / 100);
    
            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }
    
            $handle = fopen($filePath . '.temp', 'w');
            fwrite($handle, "[");
    
            $firstItem = true;
            for ($i = 0; $i < $numberPage; $i++) {
                $response = $subscriptions->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
    
                if (isset($pageData['data']['original']['data']) && !empty($pageData['data']['original']['data'])) {
                    $batch = collect($pageData['data']['original']['data'])->toArray();
    
                    foreach ($batch as $transaction) {
                        if (!$firstItem) {
                            fwrite($handle, ",\n");
                        } else {
                            $firstItem = false;
                        }
                        fwrite($handle, json_encode($transaction, JSON_PRETTY_PRINT));
                    }
    
                    fflush($handle);
                    unset($batch);
                }
            }
    
            fwrite($handle, "\n]");
            fclose($handle);
    
            // Atomic rename of the temp file to the final file
            rename($filePath . '.temp', $filePath);
    
            // Remove cache flag when finished
            cache()->forget('generating_subscriptions_json');
        } catch (\Exception $e) {
            // Remove cache flag in case of error
            cache()->forget('generating_subscriptions_json');
            throw $e;
        }
    }
}
