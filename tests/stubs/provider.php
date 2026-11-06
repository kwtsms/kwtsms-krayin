<?php

namespace KwtSms\Krayin;

// Stand-in for the service provider in tests: fixed settings, no Laravel needed.
class KwtSmsServiceProvider
{
    public static function settings(): array
    {
        return ['username' => 'krayin_username', 'password' => 'krayin_password', 'sender' => 'KWT-SMS', 'test_mode' => true];
    }
}
