<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Adapters\Webhook;

use GuzzleHttp\Client;
use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\Http\GatewayTrait;
use NotifyMeHQ\Http\Response;

class WebhookGateway implements GatewayInterface
{
    use GatewayTrait;

    /**
     * Create a new webhook gateway instance.
     *
     * @param \GuzzleHttp\Client $client
     * @param string[]           $config
     *
     * @return void
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Send a notification.
     *
     * @param string $to
     * @param string $message
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    public function notify($to, $message)
    {
        $params = $this->config['params'];
        return $this->send($this->buildUrlFromString(),$params);
    }

    /**
     * Send the notification over the wire.
     *
     * @param string   $url
     * @param string[] $params
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function send($url, array $params)
    {
        $success = false;

        $format =  'form_params';
        $headers['Accept'] = 'application/json';
        if ($this->config['format'] == 'json'){
            $format = 'json';
            $headers['Content-Type'] = 'application/json';
        }

        $rawResponse = $this->client->post($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => $headers,
            $format => $params,
        ]);

        $response = [];

        $hitIsSent = isset($this->config['hitIsSent']) ? $this->config['hitIsSent'] : false;

        $ok = substr((string) $rawResponse->getStatusCode(), 0, 1) === '2';

        $success = $hitIsSent || $ok;

        if($ok) {
            $response['status'] = (string) $rawResponse->getStatusCode();
        } else {
            $response['error'] = 'Webhook delivery failed with status code '.$rawResponse->getStatusCode();
        }

        return $this->mapResponse($success, $response);
    }

    /**
     * Map the raw response to our response object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function mapResponse($success, array $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : $response['error'],
        ]);
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->config['endpoint'];
    }
}
