<?php

namespace App\Services;

interface PushInterface
{
    public function sendMultiple($to, $message);
    public function sendSingle($to, $message);
}