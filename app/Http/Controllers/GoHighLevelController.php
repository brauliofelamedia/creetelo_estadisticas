<?php

namespace App\Http\Controllers;

use App\Models\GoHighLevel as ModelGoHighLevel;
use Illuminate\Http\Request;
use App\Models\Config;
use Illuminate\Support\Facades\Http;
use App\Services\GoHighLevel;

class GoHighLevelController extends Controller
{
    private $config;
    private $client_id;
    private $client_secret;
    private $url;

    public function __construct()
    {
        $this->config = Config::first();
        $this->client_id = env('GHL_CLIENT_ID');
        $this->client_secret = env('GHL_CLIENT_SECRET');
        $this->url = route('authorization');
    }

    //Token, Refresh Toke and Access Token
    public function token()
    {
        $goHighLevel = new GoHighLevel();
        return $goHighLevel->getToken();
    }

    public function renewToken()
    {
        $goHighLevel = new GoHighLevel();
        return $goHighLevel->renewToken();
    }

    public function authorization(Request $request)
    {
        $config = Config::first();

        $config->code = $request['code'];
        $config->save();

        try {
            $response = Http::asForm()->withOptions([
                'verify' => false,
            ])->post('https://services.leadconnectorhq.com/oauth/token', [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $config->code,
                'user_type' => 'Company'
            ]);

            $response->throw();
            $data = $response->json();

            // Handle successful response and return access token
            $config->access_token = $data['access_token'];
            $config->refresh_token = $data['refresh_token'];
            $config->company_id = $data['companyId'];
            $config->location_id = $data['locationId'];
            $config->save();

            return redirect()->route('config.edit', $config->id);

        } catch (\Throwable $exception) {
            return $exception->getMessage();
            //return response()->json(['error' => 'Error exchanging code'], 500);
        }
    }

    public function connect()
    {
        $client_id = $this->client_id;
        $scopes = [
            'contacts.readonly',
            'opportunities.readonly',
            'payments/transactions.readonly',
            'payments/subscriptions.readonly',
        ];

        if ($client_id) {
            $url = "https://marketplace.leadconnectorhq.com/oauth/chooselocation?response_type=code&redirect_uri=" . $this->url . "&client_id=" . $client_id . "&scope=".implode(' ',$scopes)."&loginWindowOpenMode=self";
            return redirect()->away($url);
        } else {
            return 'No se ha asignado el client_id';
        }
    }
}
