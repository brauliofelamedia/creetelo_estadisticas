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
use App\Jobs\GenerateContactsJson;
use App\Jobs\GenerateSubscriptionsJson;
use App\Jobs\GenerateTransactionsJson;

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
        $contactsData = $this->getContactsFromFile();
        $transactionsData = $this->getTransactionsFromFile();
        $subscriptionsData = $this->getSubscriptionsFromFile();
        
        $countriesData = Country::all();

        config([
            'app.subscriptions.data' => $subscriptionsData,
            'app.transactions.data' => $transactionsData,
            'app.contacts.data' => $contactsData,
            'app.countries.data' => collect($countriesData) 
        ]);
        
        return $next($request);
    }

    private function getContactsFromFile()
    {
        $filePath = storage_path('app/contacts.json');
        $cacheExpiration = 10800;

        // If file doesn't exist, create an empty contacts.json file
        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }

        $fileContent = file_get_contents($filePath);
        $jsonData = json_decode($fileContent, true);

        // Verificar si hay un job en proceso consultando el cache
        $jobInProgress = cache()->get('generating_contacts_json');

        if (!$jobInProgress && (empty($jsonData) || $this->shouldUpdateContacts($filePath, $cacheExpiration))) {
            cache()->put('generating_contacts_json', true, 3600); // Cache por 1 hora
            GenerateContactsJson::dispatch();
        }

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function getSubscriptionsFromFile()
    {
        $filePath = storage_path('app/subscriptions.json');
        $cacheExpiration = 10800;
        
        if (!file_exists(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }

        $fileContent = file_get_contents($filePath);
        $jsonData = json_decode($fileContent, true);

        // Verificar si hay un job en proceso consultando el cache
        $jobInProgress = cache()->get('generating_subscriptions_json');

        if (!$jobInProgress && (empty($jsonData) || $this->shouldUpdateSubscriptions($filePath, $cacheExpiration))) {
            GenerateSubscriptionsJson::dispatch();
        }

        return collect(json_decode(file_get_contents($filePath), true))
            ->map(fn($item) => (object) $item);
    }

    private function getTransactionsFromFile()
    {
        $filePath = storage_path('app/transactions.json');
        $cacheExpiration = 10800;
        
        if (!file_exists(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        if (!file_exists($filePath)) {
            file_put_contents($filePath, json_encode([]));
        }

        // Check if file is empty, smaller than 1MB or cache expired
        $fileContent = file_get_contents($filePath);
        $jsonData = json_decode($fileContent, true);

        // Verificar si hay un job en proceso consultando el cache
        $jobInProgress = cache()->get('generating_transactions_json');
        
        if (!$jobInProgress && (empty($jsonData) || $this->shouldUpdateTransactions($filePath, $cacheExpiration))) {
            GenerateTransactionsJson::dispatch();
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

    private function shouldUpdateTransactions($filePath, $expiration): bool
    {
        return !file_exists($filePath) || (time() - filemtime($filePath) > $expiration);
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
