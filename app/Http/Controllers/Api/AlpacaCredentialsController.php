<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AlpacaCredentialsController extends Controller
{
    /**
     * Test Alpaca API connection with provided credentials
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'key_id' => 'required|string',
            'secret' => 'required|string',
            'is_paper' => 'sometimes|boolean',
        ]);

        $isPaper = $validated['is_paper'] ?? true;

        $baseUrl = $isPaper
            ? 'https://paper-api.alpaca.markets'
            : 'https://api.alpaca.markets';

        try {
            $client = new Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'APCA-API-KEY-ID' => $validated['key_id'],
                    'APCA-API-SECRET-KEY' => $validated['secret'],
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $response = $client->get('/v2/account');
            $account = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'account_status' => $account['status'] ?? null,
            ]);
        } catch (GuzzleException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Check current user's Alpaca connection status
     */
    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user->hasAlpacaCredentials()) {
            return response()->json([
                'has_credentials' => false,
                'is_connected' => false,
            ]);
        }

        try {
            $baseUrl = $user->alpaca_is_paper
                ? 'https://paper-api.alpaca.markets'
                : 'https://api.alpaca.markets';

            $client = new Client([
                'base_uri' => $baseUrl,
                'headers' => [
                    'APCA-API-KEY-ID' => $user->alpaca_key_id,
                    'APCA-API-SECRET-KEY' => $user->alpaca_secret,
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $response = $client->get('/v2/account');
            $account = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'has_credentials' => true,
                'is_connected' => true,
                'account_status' => $account['status'] ?? null,
            ]);
        } catch (GuzzleException $e) {
            return response()->json([
                'has_credentials' => true,
                'is_connected' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
