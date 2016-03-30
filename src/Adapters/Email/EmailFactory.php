<?php

namespace NotifyMeHQ\Adapters\Email;

use NotifyMeHQ\Contracts\FactoryInterface;
use NotifyMeHQ\Support\Arr;
use PHPMailer;

class EmailFactory implements FactoryInterface
{
    /**
     * Create a new email gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Adapters\Email\EmailGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, ['smtp','user','pass','subject']);
        $client = new PHPMailer();
        $client->isSMTP();
        $client->CharSet = 'UTF-8';
        $client->Host = Arr::get($config,'smtp');
        $client->SMTPAuth = Arr::get($config,'smtp_auth',true);
        $client->Username = Arr::get($config,'user');
        $client->Password = Arr::get($config,'pass');
        $client->SMTPSecure = Arr::get($config,'smtp_secure','tls');
        $client->Port = Arr::get($config,'port',587);
        $client->isHTML(Arr::get($config,'html',true));
        $client->setFrom(Arr::get($config,'from','APP'),Arr::get($config,'from_name','APP'));

        return new EmailGateway($client, $config);
    }
}
