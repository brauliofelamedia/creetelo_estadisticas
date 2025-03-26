<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\Config;
use GuzzleHttp\Client;
use Exception;
use Carbon\Carbon;

class Subscriptions
{
    private $client;
    private $config;
    private $client_id;
    private $client_secret;

    public function __construct()
    {
        //Get Config
        $this->config = Config::first();

        //Client and Client Secret
        $this->client_id = env('GHL_CLIENT_ID');
        $this->client_secret = env('GHL_CLIENT_SECRET');

        $this->client = new Client([
            'base_uri' => 'https://services.leadconnectorhq.com',
        ]);
    }

    public function get($contactId)
    {
        try {
            // Realizar la solicitud GET
            $response = $this->client->get('payments/subscriptions', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Version' => '2021-07-28',
                    'Authorization' => 'Bearer ' . $this->config->access_token,
                ],
                'query' => [
                    'altId' => $this->config->location_id,
                    'altType' => 'location',
                    //'limit' => 100,
                    'paymentMode' => 'live',
                    //'offset' => $offset,
                    'contactId' => $contactId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return response()->json($data, 200);

        } catch (Exception $e) {
            // Manejo de errores
            if ($e->getCode() == 401) {
                return response()->json(['error' => 'Unauthorized request'], 401);
            }

            return response()->json(['error' => 'Request failed'], 500);
        }
    }
}