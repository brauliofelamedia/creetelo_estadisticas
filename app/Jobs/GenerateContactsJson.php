<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Contacts;
use Nnjeim\World\Models\Country;

class GenerateContactsJson implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $filePath = storage_path('app/contacts.json');
            $contactsService = new Contacts();
            $response = $contactsService->get(0);
            $responseData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            $totalCount = $responseData['data']['total'];
            $numberPage = (int)ceil($totalCount / 100);
            $countriesData = Country::all();

            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }

            $handle = fopen($filePath . '.temp', 'w');
            fwrite($handle, "[");

            $firstItem = true;
            for ($i = 0; $i < $numberPage; $i++) {
                $response = $contactsService->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);

                if (isset($pageData['data']['contacts']) && !empty($pageData['data']['contacts'])) {
                    $contactsCollect = collect($pageData['data']['contacts']);
                    $batch = $contactsCollect->map(function($contact) use ($countriesData) {
                        $country = collect($countriesData)->firstWhere('iso2', $contact['country']);
                        $contact['countryName'] = isset($country['name']) ? $country['name'] : $contact['country'];
                        return $contact;
                    })->toArray();

                    foreach ($batch as $contact) {
                        if (!$firstItem) {
                            fwrite($handle, ",\n");
                        } else {
                            $firstItem = false;
                        }
                        fwrite($handle, json_encode($contact, JSON_PRETTY_PRINT));
                    }

                    fflush($handle);
                    unset($batch);
                }
            }

            fwrite($handle, "\n]");
            fclose($handle);

            // Atomic rename of the temp file to the final file
            rename($filePath . '.temp', $filePath);

            // Al finalizar, eliminar la marca de cache
            cache()->forget('generating_contacts_json');
        } catch (\Exception $e) {
            // En caso de error, también eliminar la marca
            cache()->forget('generating_contacts_json');
            throw $e;
        }
    }
}
