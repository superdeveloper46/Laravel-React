<?php

namespace App\Services;

use App\Services\PushInterface;

class Gcm implements PushInterface {

    const TITLE = 'FreshApp';
    const GCM_URL = 'https://android.googleapis.com/gcm/send';
    const GCM_KEY = 'AIzaSyCNtOebwUMiaD69EDHrvmMyQKP_-ei46G0';

    protected $message_headers = [];
    protected $device_ids = [];
    protected $title;
    protected $data = [];

    public function __construct() {
        // set the required headers
        $this->setMessageHeaders('Authorization', 'key=' . self::GCM_KEY);
        $this->setMessageHeaders('Content-Type', 'application/json');

        // set default title
        $this->setTitle(self::TITLE);

        //  set the push notification settings
        $this->setData('title', $this->getTitle());
        $this->setData('ledColor', [0, 0, 255, 0]);
        $this->setData('vibrationPattern', [100, 1000, 100, 1000]);
    }

    public function setMessageHeaders($key, $item) {
        $this->message_headers[$key] = $item;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    public function setData($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * 
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Get the headers formatted as a array
     * @return array
     */
    public function getMessageHeaders() {
        $return = [];
        foreach ($this->message_headers as $key => $value) {
            $return[] = $key . ':' . $value;
        }

        return $return;
    }

    /**
     * Send a single message to multiple devices at once
     * @param array $device_ids
     * @param string $message
     */
    public function sendMultiple($device_ids, $message) {
        $this->setData('message', $message);

        $data = [
            'registration_ids' => $device_ids,
            'data' => $this->getData()
        ];

        return $this->send($data);
    }

    /**
     * Send a single message to a single device
     * @param string $device_id
     * @param string $message
     */
    public function sendSingle($device_id, $message) {
        $this->setData('message', $message);

        $data = [
            'to' => $device_id,
            'data' => $this->getData()
        ];

        return $this->send($data);
    }

    /**
     * 
     * @param array $data
     */
    protected function send($data) {
        try {
            // Open connection
            $ch = curl_init();
            // Set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, self::GCM_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getMessageHeaders());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Disabling SSL Certificate support temporarly
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            // Execute post
            $result = curl_exec($ch);
            if ($result === FALSE) {
                return false;
            }
            // Close connection
            curl_close($ch);

            return $result;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}
