<?php
/*
FILE:      src/Secret.php
CREATED:   2026-11-07 01:25 +03
TASK:      krayin-new
AUTHOR:    M Alhassoun
WHAT:      Picks the kwtSMS API password: the encrypted copy when there is one, else a plain value saved earlier.
WHY:       Krayin stores config in plain text and echoes a saved password into the settings page; the provider now keeps
           the password encrypted (see KwtSmsServiceProvider::securePassword). Kept free of Laravel so it is unit-tested.
USAGE:     Secret::resolvePassword($encrypted, $plain, fn ($v) => Crypt::decryptString($v))
DEPENDS:   nothing
*/

namespace KwtSms\Krayin;

class Secret
{
    public static function resolvePassword(string $encrypted, string $plain, callable $decrypt): string
    {
        if ($encrypted !== '') {
            try {
                return (string) $decrypt($encrypted);
            } catch (\Throwable $e) {
                return $plain; // APP_KEY changed or value damaged: fall back; a send then fails visibly with ERR003
            }
        }

        return $plain;
    }
}
