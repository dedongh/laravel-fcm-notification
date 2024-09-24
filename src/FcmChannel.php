<?php

namespace Benwilkins\FCM;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Benwilkins\FCM\TokenManager;

/**
 * Class FcmChannel.
 */
class FcmChannel
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var string
     */
    private $projectId;

    /**
     * FcmChannel constructor.
     *
     * @param Client $client
     * @param TokenManager $tokenManager
     * @param string $projectId The Firebase project ID.
     */
    public function __construct(Client $client, TokenManager $tokenManager, string $projectId)
    {
        $this->client = $client;
        $this->tokenManager = $tokenManager;
        $this->projectId = $projectId;
    }

    /**
     * Send the notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return mixed
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var FcmMessage $message */
        $message = $notification->toFcm($notifiable);

        if (is_null($message->getTo()) && is_null($message->getCondition())) {
            if (! $to = $notifiable->routeNotificationFor('fcm', $notification)) {
                return;
            }

            $message->to($to);
        }

        $responseArray = [];

        // Get the OAuth 2.0 token from the TokenManager
        $accessToken = $this->tokenManager->getAccessToken();

        // Build the FCM URL dynamically with the provided project ID
        $apiUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        try {
            if (is_array($message->getTo())) {
                $chunks = array_chunk($message->getTo(), 1000);

                foreach ($chunks as $chunk) {
                    $message->to($chunk);

                    $response = $this->client->post($apiUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type'  => 'application/json',
                        ],
                        'body' => $message->formatData(),
                    ]);

                    array_push($responseArray, json_decode($response->getBody(), true));
                }
            } else {
                $response = $this->client->post($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type'  => 'application/json',
                    ],
                    'body' => $message->formatData(),
                ]);

                array_push($responseArray, json_decode($response->getBody(), true));
            }
        } catch (\Exception $e) {
            array_push($responseArray, ['error' => $e->getMessage()]);
        }

        return $responseArray;
    }
}
