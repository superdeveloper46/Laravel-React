<?php

namespace App\Services;

use App\Services\PushNotification;
use App\Services\MailService;
use App\Models\User;

class Notifications
{
    const NEW_LEAD_RECEIVED = 'You have received a new lead.';
    
    protected $pushService;

    public function __construct()
    {
        $this->pushService = new PushNotification();
    }

    public function sendLeadNewLeadNotification(User $customer)
    {
        $this->pushService->sendNotification($customer->devices, self::NEW_LEAD_RECEIVED);
    }
}
