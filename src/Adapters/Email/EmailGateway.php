<?php

namespace NotifyMeHQ\Adapters\Email;

use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\Http\Response;
use PHPMailer;

class EmailGateway implements GatewayInterface
{

    /**
     * Create a new email gateway instance.
     *
     * @param \PHPMailer $client
     * @param string[]   $config
     *
     * @return void
     */
    public function __construct(PHPMailer $client, array $config)
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
        return $this->send($to, $message);
    }

    /**
     * Send the notification over the wire.
     *
     * @param string   $to
     * @param string   $message
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function send($to, $message)
    {
        $success = false;
        $this->client->addAddress($to);
        $this->client->Subject = $this->config['subject'];
        $this->client->Body    = $message;

        $response = [];
        if($this->client->send()) {
            $success = true;
        } else {
            $response['error'] = 'Email delivery failed: '.$this->client->ErrorInfo;    
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
}
