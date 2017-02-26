<?php

namespace NotifyMeHQ\Adapters\PushNotification;

use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\Http\Response;

use Sly\NotificationPusher\PushManager,
    Sly\NotificationPusher\Adapter\Apns as ApnsAdapter,
    Sly\NotificationPusher\Collection\DeviceCollection,
    Sly\NotificationPusher\Model\Device,
    Sly\NotificationPusher\Model\Message,
    Sly\NotificationPusher\Model\Push;

class PushNotificationGateway implements GatewayInterface
{

    /**
     * The api endpoint.
     *
     * @var string
     */
    protected $androidEndpoint = 'https://android.googleapis.com/gcm/send';


    /**
     * Create a new email gateway instance.
     *
     * @param \PHPMailer $client
     * @param string[]   $config
     *
     * @return void
     */
    public function __construct(\GuzzleHttp\Client $client, array $config)
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
        switch (strtoupper($this->config['platform'])) {
            case 'ANDROID':
                $title = $this->config['title'];
                return $this->sendAndroid($this->config['android_api_access_token'],$to,$title,$message);
                break;
            case 'IOS':
                return $this->sendIOS($this->config['ios_cert_path'],$this->config['ios_cert_pass'],$to,$message);
                break;
            default:
                throw new \Exception('Invalid Platform '.$this->config['platform']);
        }
    }


    protected function sendIOS($cert,$certPass,$to,$message)
    {
        $error = '';
        $success = false;
        try{
            $pushManager = new PushManager(PushManager::ENVIRONMENT_PROD);
            $apnsAdapter = new ApnsAdapter(array(
                'certificate' => $cert,
                'passPhrase' => $certPass,
            ));

            $devices = array();
            $devices[] = new Device($to);
            $devicesCollection = new DeviceCollection($devices);
            $messageObj = new Message($message);
            $push = new Push($apnsAdapter, $devicesCollection, $messageObj);
            $pushManager->add($push);
            $pushManager->push();
            $iterator = $pushManager->getIterator();
            $iterator->seek(0);
            $push = $iterator->current();
            $success = $push->isPushed();
        }catch(\Exception $e){
            $error = $e->getMessage();
        }

        $response = $success ? array('pushed_at' => $push->getPushedAt()) : ['error' => 'Push Notification delivery failed: '.$error];
        return $this->mapResponse($success, $response);
    }

    protected function sendAndroid($apiKey,$to,$title,$message)
    {
        $error = '';
        $success = false;
        try{
            $registrationIds = array();
            $registrationIds[] = $to;
            $fields = array(
                'registration_ids'  => $registrationIds,
                'data' => array(
                    'title'     => $title,
                    'message'   => $message
                )
            );
            $headers = array();
            $headers['Content-Type'] = 'application/json';
            $headers['Authorization'] = 'key=' . $apiKey;

            $rawResponse = $this->client->post($this->androidEndpoint, [
                'exceptions'      => false,
                'timeout'         => '30',
                'connect_timeout' => '30',
                'headers'         => $headers,
                'json'            => $fields,
            ]);
            
            $res = $rawResponse->getStatusCode();
            $success = $res == 200 ? true : false;
            $message = $rawResponse->getReasonPhrase();
        }catch(\Exception $e){
            $error = $e->getMessage();
        }
        $response = $success ? array('response' => $message) : ['error' => 'Push Notification delivery failed: '.$message.', '.$error];
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