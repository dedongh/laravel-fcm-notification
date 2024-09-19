<?php

namespace Benwilkins\FCM;

use GuzzleHttp\Client;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

/**
 * Class FcmNotificationServiceProvider.
 */
class FcmNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register.
     */
    public function register()
    {
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('fcm', function () {
                
                // Pass the service account JSON and project ID to the TokenManager and FcmChannel
                return new FcmChannel(
                    app(Client::class),
                    new TokenManager(config('services.fcm.service_account')),
                    config('services.fcm.project_id')
                );
            });
        });
    }
}