<?php

/**
 * PushNotification is the containing all the methods to send push notifications.
 */

namespace App\Services;

use App\Services\Gcm;
use App\Services\Apns;

class PushNotification
{

    /**
     * 
     * @param type $devices
     * @param type $message
     */
    public function sendNotification($devices, $message)
    {
        if ($devices) {
            foreach ($devices as $device) {
                if ($device->platform === 'android') {
                    $this->sendAndroidNotifications($device->device_id, $message);
                } else if ($device->platform === 'ios') {
                    $this->sendIosNotifications($device->device_id, $message);
                }
            }
        }
    }

    /**
     * Send android push notification
     * 
     * @param type $device_ids
     * @param type $message
     */
    public function sendAndroidNotifications($device_ids, $message)
    {
        $gcmPush = new Gcm();

        if (count($device_ids) === 0 || $device_ids === '') {
            return false;
        }

        if (is_array($device_ids)) {
            return $gcmPush->sendMultiple($device_ids, $message);
        } else {
            return $gcmPush->sendSingle($device_ids, $message);
        }
    }

    /**
     * Send ios notification
     * 
     * @param array $device_ids
     * @param string $message
     */
    public function sendIosNotifications($device_ids, $message)
    {
        $apns = new Apns();

        if (count($device_ids) === 0 || $device_ids === '') {
            return false;
        }

        if (is_array($device_ids)) {
            return $apns->sendMultiple($device_ids, $message);
        } else {
            return $apns->sendSingle($device_ids, $message);
        }
    }

    /**
     * Send a test push notification
     * 
     * @param type $iosDevie
     * @param type $androidDevice
     * @return type
     */
    public function sendTestNotification($iosDevie = null, $androidDevice = null)
    {
        $send['android'] = $androidDevice ? $this->sendAndroidNotifications($androidDevice, 'Test Notification') : null;
        $send['ios'] = $iosDevie ? $this->sendIosNotifications($iosDevie, 'Test Notification') : null;

        return $send;
    }

}
