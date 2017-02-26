<?php

namespace NotifyMeHQ\Adapters\PushNotification;

use NotifyMeHQ\Contracts\FactoryInterface;
use NotifyMeHQ\Support\Arr;
use GuzzleHttp\Client;


class PushNotificationFactory implements FactoryInterface
{
    /**
     * Create a new PushNotification gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Adapters\PushNotification\PushNotificationGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, ['platform','ios_cert_path', 'ios_cert_pass','android_api_access_token']);
        $client = new Client();
        return new PushNotificationGateway($client, $config);
    }
}
