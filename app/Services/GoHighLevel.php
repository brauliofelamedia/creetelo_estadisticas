<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use App\Models\Config;
use GuzzleHttp\Client;
use Exception;
class GoHighLevel
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

    //Token, Refresh Toke and Access Token
    public function checkToken()
    {
        try {
            $response = $this->client->get('contacts', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Version' => '2021-07-28',
                    'Authorization' => 'Bearer ' . $this->config->access_token,
                ],
                'query' => [
                    'locationId' => $this->config->location_id
                ]
            ]);

            $response = response()->json($response->getBody());
            $response->setStatusCode(200);
            return $response;

        } catch (Exception $e) {
            if ($e->getCode() == 401) {
                return response()->json(['error' => 'Unauthorized request'], 401);
            }

            return response()->json(['error' => 'Request failed'], 500);
        }
    }

    public function renewToken()
    {
        try {
            $response = $this->client->post('https://services.leadconnectorhq.com/oauth/token', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->config->refresh_token,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            if ($statusCode === 200) {
                $responseData = json_decode($responseBody, true);
                $this->config->access_token = $responseData['access_token'];
                $this->config->refresh_token = $responseData['refresh_token'];
                $this->config->save();

            } else {
                return response()->json(['error' => 'Token exchange failed'], $statusCode);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Request failed'], 500);
        }
    }
}
