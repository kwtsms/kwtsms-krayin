<?php

// Settings > Configuration > kwtSMS. Read with core()->getConfigData('kwtsms.settings.account.<field>').
return [
    [
        'key' => 'kwtsms',
        'name' => 'kwtSMS',
        'info' => 'Send SMS from workflows through kwtSMS.',
        'sort' => 90,
    ], [
        'key' => 'kwtsms.settings',
        'name' => 'kwtSMS',
        'info' => 'API login, Sender ID and test mode.',
        'icon' => 'icon-setting',
        'sort' => 1,
    ], [
        'key' => 'kwtsms.settings.account',
        'name' => 'kwtSMS account',
        'info' => 'Use the API username and password of your kwtSMS account, not your mobile number.',
        'sort' => 1,
        'fields' => [
            [
                'name' => 'username',
                'title' => 'API username',
                'type' => 'text',
            ], [
                'name' => 'password',
                'title' => 'API password',
                'type' => 'password',
            ], [
                'name' => 'sender',
                'title' => 'Sender ID',
                'type' => 'text',
                'default' => 'KWT-SMS',
                'validation' => 'max:11',
            ], [
                'name' => 'test_mode',
                'title' => 'Test mode (queued on kwtSMS, not delivered; credits are held until you delete the message from the Sending Queue)',
                'type' => 'boolean',
                'default' => '0',
            ],
        ],
    ],
];
