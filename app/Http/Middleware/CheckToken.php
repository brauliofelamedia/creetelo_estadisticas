<?php

namespace App\Http\Middleware;

use App\Services\GoHighLevel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Config;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CheckToken
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
    
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = new GoHighLevel();
            $check = $token->checkToken();
    
            if ($check->getStatusCode() === 401) {
                $this->refreshToken();
            }
        } catch (Exception $e) {
            // Log error but continue with the request
            Log::error('Token check failed: ' . $e->getMessage());
        }

        return $next($request);
    }
    
    /**
     * Attempt to refresh the token
     */
    private function refreshToken(): void
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
                Log::info('Token refreshed successfully');
            } else {
                Log::error('Token exchange failed with status: ' . $statusCode);
            }
        } catch (Exception $e) {
            Log::error('Token refresh failed: ' . $e->getMessage());
        }
    }
}
