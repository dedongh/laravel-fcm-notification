<?php

namespace Benwilkins\FCM;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Cache;

class TokenManager
{
    private $serviceAccountJson;

    /**
     * TokenManager constructor.
     * 
     * @param string $serviceAccountJson The Firebase service account JSON content.
     */
    public function __construct(string $serviceAccountJson)
    {
        $this->serviceAccountJson = $serviceAccountJson;
    }

    /**
     * Generate and cache the OAuth 2.0 access token.
     * This method will check if the token is already cached, and if not, it will generate a new one.
     *
     * @return string
     * @throws \Exception
     */
    public function getAccessToken()
    {
        // Check if token is cached (using Laravel cache)
        if (Cache::has('fcm_access_token')) {
            return Cache::get('fcm_access_token');
        }

        // Token not cached, generate a new one
        $token = $this->generateAccessToken();

        // Cache the token for 1 hour (token typically expires after 1 hour)
        Cache::put('fcm_access_token', $token,  now()->addMinutes(60));

        return $token;
    }

    /**
     * Generate a new OAuth 2.0 access token using the provided service account JSON.
     *
     * @return string
     * @throws \Exception
     */
    private function generateAccessToken()
    {
        // Create a new Google Client instance
        $client = new GoogleClient();
        $client->setAuthConfig(json_decode($this->serviceAccountJson, true));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        // Fetch and return the access token
        $token = $client->fetchAccessTokenWithAssertion();

        if (isset($token['error'])) {
            throw new \Exception('Error fetching access token: ' . $token['error_description']);
        }

        return $token['access_token'];
    }
}