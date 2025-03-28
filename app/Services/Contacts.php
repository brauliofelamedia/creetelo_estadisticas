<?php

namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\Config;
use GuzzleHttp\Client;
use Exception;

class Contacts {
    private $client;
    private $config;
    private $client_id;
    private $client_secret;

    public function __construct()
    {
        // Get Config
        $this->config = Config::first();

        // Client and Client Secret
        $this->client_id = env('GHL_CLIENT_ID');
        $this->client_secret = env('GHL_CLIENT_SECRET');

        // Create Client instance
        $this->client = new Client([
            'base_uri' => 'https://services.leadconnectorhq.com',
        ]);
    }

    public function get($contactId = null)
    {   
        if($contactId){
            $filters = [
                [
                    'field' => 'id',
                    'operator' => 'eq',
                    'value' => $contactId
                ]
            ];
    
            $data = [
                'locationId' => $this->config->location_id,
                'pageLimit' => 100,
                'filters' => $filters,
                'page' => 0,
            ];
        } else {
            $data = [
                'locationId' => $this->config->location_id,
                'pageLimit' => 100,
                'page' => 0,
            ];
        }
        

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Accept' => 'application/json',
                'Version' => '2021-07-28',
                'Authorization' => 'Bearer ' . $this->config->access_token,
            ])->post("https://services.leadconnectorhq.com/contacts/search", $data);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                return response()->json(['error' => 'Unauthorized request'], 401);
            }
            return response()->json(['error' => 'Request failed'], 500);
        }
    }
}