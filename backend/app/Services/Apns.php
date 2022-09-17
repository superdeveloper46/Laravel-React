<?php

namespace App\Services;

use App\Services\PushInterface;

class Apns implements PushInterface {

    const PASS_PHRASE = 'FreshApp';
    const APNS_URL = 'ssl://gateway.push.apple.com:2195';

    protected $pem_path;
    protected $data = [];

    public function __construct() {
        $this->pem_path = resource_path() . '/assets/cert/ck.pem';
        //$this->setData('badge', 1);
        $this->setData('sound', 'newMessage.wav');
    }

    public function setPemPath($path) {
        $this->pem_path = $path;
    }

    public function getPemPath() {
        if (!file_exists($this->pem_path)) {
            throw new Exception('.pem file does not exist');
        }

        return $this->pem_path;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     */
    public function setData($key, $value) {
        $this->data[$key] = $value;
    }

    public function getData() {
        return $this->data;
    }

    /**
     * Send a single message to multiple devices at once
     * @param array $device_ids
     * @param string $message
     */
    public function sendMultiple($device_ids, $message) {
        $this->setData('alert', $message);

        foreach ($device_ids AS $device_id) {
            $this->send($device_id);
        }

        return true;
    }

    /**
     * Send a single message to a single device
     * @param string $device_id
     * @param string $message
     */
    public function sendSingle($device_id, $message) {
        $this->setData('alert', $message);

        return $this->send($device_id);
    }

    /**
     * 
     * @param type $device_id
     * @return boolean
     */
    protected function send($device_id) {
        try {
            $ctx = stream_context_create();
            stream_context_set_option($ctx, 'ssl', 'local_cert', $this->getPemPath());
            
            if (!empty(self::PASS_PHRASE)) {
                stream_context_set_option($ctx, 'ssl', 'passphrase', self::PASS_PHRASE);
            }
            
            // Open a connection to the APNS server
            $fp = stream_socket_client(self::APNS_URL, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
            if (!$fp) {
                return false;
            }
            // Encode the payload as JSON
            $payload = json_encode(['aps' => $this->getData()]);
            // Build the binary notification
            $msg = chr(0) . pack('n', 32) . pack('H*', $device_id) . pack('n', strlen($payload)) . $payload;
            // Send it to the server

            $result = fwrite($fp, $msg, strlen($msg));
            
            // Close the connection to the server
            fclose($fp);
            
            if (!$result) {
                return false;
            } else {
                return $result;
            }
            
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
