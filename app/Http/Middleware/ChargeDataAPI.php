<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Subscriptions;
use App\Services\Transactions;
use App\Services\Contacts;
use Carbon\Carbon;
use Nnjeim\World\Models\Country;
use App\Models\Config;
use Exception;
use GuzzleHttp\Client;

class ChargeDataAPI
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://services.leadconnectorhq.com',
        ]);
    }

    public function handle(Request $request, Closure $next): Response
    {
        //Charge JSON
        $contactsData = $this->getContactsFromFileNew();
        $transactionsData = $this->getTransactionsFromFileNew();
        $subscriptionsData = $this->getSubscriptionsFromFileNew();
        
        $countriesData = Country::all();

        config([
            'app.subscriptions.data' => $subscriptionsData,
            'app.transactions.data' => $transactionsData,
            'app.contacts.data' => $contactsData,
            'app.countries.data' => collect($countriesData) 
        ]);
        
        return $next($request);
    }

    private function getSubscriptionsFromFile()
    {
        $filePath = storage_path('app/subscriptions.json');
        $cacheExpiration = 10800; // 3 hours in seconds
        
        // Ensure storage directory exists
        if (!file_exists(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        // Get cached contacts first
        $contactsPath = storage_path('app/contacts.json');
        if (!file_exists($contactsPath)) {
            throw new Exception('Contacts file not found. Please refresh contacts first.');
        }

        $contacts = collect(json_decode(file_get_contents($contactsPath), true));

        if ($this->shouldUpdateSubscriptions($filePath, $cacheExpiration) || !file_exists($filePath)) {
            $allSubscriptions = collect();
            
            // Read all subscriptions from a master file
            $subscriptions = new Subscriptions();
            $response = $subscriptions->get('', '2000', Carbon::now()->year);
            $subscriptionsData = collect(json_decode(json_encode($response->getData()), true)['data']);

            foreach ($contacts as $contact) {
                // Filter subscriptions for current contact
                $contactSubscriptions = $subscriptionsData->filter(function($subscription) use ($contact) {
                    return $subscription['contactId'] === $contact['id'];
                });
                
                if ($contactSubscriptions->isNotEmpty()) {
                    $allSubscriptions = $allSubscriptions->concat($contactSubscriptions);
                }
            }

            file_put_contents($filePath, json_encode($allSubscriptions->toArray(), JSON_PRETTY_PRINT));
            return $allSubscriptions;
        }

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function shouldUpdateSubscriptions($filePath, $expiration): bool
    {
        return !file_exists($filePath) || (time() - filemtime($filePath) > $expiration);
    }

    private function getTransactionsFromFileNew()
    {
        $filePath = storage_path('app/transactions.json');

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function getSubscriptionsFromFileNew()
    {
        $filePath = storage_path('app/subscriptions.json');

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function getContactsFromFileNew()
    {
        $filePath = storage_path('app/contacts.json');

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function getTransactionsFromFile()
    {
        $filePath = storage_path('app/transactions.json');
        $cacheExpiration = 10800;
        
        // Ensure storage directory exists
        if (!file_exists(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        if ($this->shouldUpdateTransactions($filePath, $cacheExpiration) || !file_exists($filePath)) {
            $transactions = new Transactions();
            $allTransactions = collect();
            
            // Get cached contacts first
            $contactsPath = storage_path('app/contacts.json');
            if (!file_exists($contactsPath)) {
                throw new Exception('Contacts file not found. Please refresh contacts first.');
            }
            
            $contacts = collect(json_decode(file_get_contents($contactsPath), true));
            
            foreach ($contacts as $contact) {
            $response = $transactions->get(0, $contact['id']);
            $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);
            
            if (isset($pageData['data']['original']['data'])) {
                $contactTransactions = collect($pageData['data']['original']['data']);
                
                // Filter out duplicates based on _id
                $newTransactions = $contactTransactions->filter(function($transaction) use ($allTransactions) {                    
                    $isValidSourceType = isset($transaction['entitySourceType']) && 
                        $transaction['entitySourceType'] === 'membership';
                    
                    return !$allTransactions->contains('_id', $transaction['_id']) && $isValidSourceType;
                });
                
                $allTransactions = $allTransactions->concat($newTransactions);
            }
            }

            file_put_contents($filePath, json_encode($allTransactions->toArray(), JSON_PRETTY_PRINT));
            return $allTransactions;
        }

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function shouldUpdateTransactions($filePath, $expiration): bool
    {
        return !file_exists($filePath) || (time() - filemtime($filePath) > $expiration);
    }

    private function getContactsFromFile()
    {
        $filePath = storage_path('app/contacts.json');
        $cacheExpiration = 10800;

        // If file doesn't exist, force creation regardless of cache expiration
        if (!file_exists($filePath) || $this->shouldUpdateContacts($filePath, $cacheExpiration)) {
            $contacts = new Contacts();
            $response = $contacts->get(0);
            $totalCount = json_decode(json_encode(response()->json(['data' => $response])->getData()), true)['data']['total'];
            $numberPage = (int)ceil($totalCount / 100);
            $countriesData = Country::all();

            $allContacts = collect();
            for ($i = 0; $i < $numberPage; $i++) {
                $contacts = new Contacts();
                $response = $contacts->get($i);
                $pageData = json_decode(json_encode(response()->json(['data' => $response])->getData()), true);

                if (isset($pageData['data']['contacts']) && !empty($pageData['data']['contacts'])) {
                    $contactsCollect = collect($pageData['data']['contacts']);
                    $allContacts = $allContacts->concat($contacts);
                    $countriesCollect = collect($countriesData);

                    $contacts = $contactsCollect->map(function($contact) use ($countriesCollect) {
                        $contact['countryName'] = $countriesCollect->where('iso2', $contact['country'])->first()['name'] ?? $contact['country'];
                        return $contact;
                    });
                    $allContacts = $allContacts->concat($contacts);
                }
            }

            // Ensure storage directory exists
            if (!file_exists(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }

            file_put_contents($filePath, json_encode($allContacts->toArray(), JSON_PRETTY_PRINT));
            return $allContacts;
        }

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function shouldUpdateContacts($filePath, $expiration): bool
    {
        return !file_exists($filePath) || (time() - filemtime($filePath) > $expiration);
    }

    private function renewToken()
    {
        $config = Config::first();
        try {
            $response = $this->client->post('https://services.leadconnectorhq.com/oauth/token', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'client_id' => $config->client_id,
                    'client_secret' => $config->client_secret_id,
                    'grant_type' => 'refresh_token',
                    'code' => $config->code,
                    'refresh_token' => $config->refresh_token,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode === 200) {
                $responseData = json_decode($responseBody, true);

                $config->access_token = $responseData['access_token'];
                $config->refresh_token = $responseData['refresh_token'];
                $config->save();

            } else {
                return response()->json(['error' => 'Token exchange failed'], $statusCode);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Request failed'], 500);
        }
    }
}
