<?php
namespace NotifyMeHQ\Adapters\Nexmo;

use GuzzleHttp\Client;
use NotifyMeHQ\Contracts\FactoryInterface;
use NotifyMeHQ\Support\Arr;

class NexmoFactory implements FactoryInterface
{
    /**
     * Create a new Nexmo gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Adapters\Nexmo\NexmoGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, ['api_key','api_secret','from']);

        $client = new Client();

        return new NexmoGateway($client, $config);
    }
}
