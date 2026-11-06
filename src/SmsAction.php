<?php

namespace KwtSms\Krayin;

/**
 * The "Send SMS to person" workflow action: picks the person's number, sends the text, logs failures. Free of
 * Krayin classes so it can be unit tested.
 */
class SmsAction
{
    public const ID = 'send_sms_to_person';

    public const VERSION = '1.0.0';

    /** @var callable|null replaces the kwtSMS client in tests: fn(string $number, string $text, array $config): array */
    public static $transport = null;

    /** @var callable|null receives failure messages (defaults to Laravel's logger when available) */
    public static $logger = null;

    /** The workflow action definition shown in Settings > Workflows > Actions. */
    public static function definition(): array
    {
        return [
            'id' => self::ID,
            'name' => 'Send SMS to person',
        ];
    }

    /**
     * Krayin keeps phone numbers as [{"value": "...", "label": "work|home|mobile|..."}]. Prefer a "mobile" label,
     * else the first number.
     */
    public static function pickNumber(mixed $contactNumbers): string
    {
        if (is_string($contactNumbers)) {
            $contactNumbers = json_decode($contactNumbers, true);
        }
        if (! is_array($contactNumbers)) {
            return '';
        }
        $first = '';
        foreach ($contactNumbers as $n) {
            $value = trim((string) (is_array($n) ? ($n['value'] ?? '') : $n));
            if ($value === '') {
                continue;
            }
            if (is_array($n) && strtolower((string) ($n['label'] ?? '')) === 'mobile') {
                return $value;
            }
            if ($first === '') {
                $first = $value;
            }
        }

        return $first;
    }

    /**
     * @param  array  $config  username, password, sender, test_mode
     * @return array the kwtSMS answer (result OK, or ERROR with code and description); never throws
     */
    public static function send(string $number, string $text, array $config): array
    {
        $text = trim(preg_replace('/[ \t]+/u', ' ', strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
        if (($config['username'] ?? '') === '' || ($config['password'] ?? '') === '') {
            return self::fail(['result' => 'ERROR', 'code' => 'NOT_CONFIGURED', 'description' => 'kwtSMS API username or password is missing']);
        }
        if ($number === '') {
            return self::fail(['result' => 'ERROR', 'code' => 'NO_NUMBER', 'description' => 'The person has no phone number']);
        }
        if ($text === '') {
            return self::fail(['result' => 'ERROR', 'code' => 'EMPTY_MESSAGE', 'description' => 'The SMS text of the workflow action is empty']);
        }
        try {
            if (self::$transport) {
                $result = call_user_func(self::$transport, $number, $text, $config);
            } else {
                $sender = trim((string) ($config['sender'] ?? '')) !== '' ? trim((string) $config['sender']) : 'KWT-SMS';
                $client = new \KwtSMS\KwtSMS($config['username'], $config['password'], $sender,
                    ! empty($config['test_mode']), '', 'kwtsms-krayin/' . self::VERSION);
                $result = $client->send($number, $text);
            }
        } catch (\Throwable $e) {
            $result = ['result' => 'ERROR', 'code' => 'EXCEPTION', 'description' => $e->getMessage()];
        }

        return ($result['result'] ?? '') === 'OK' ? $result : self::fail($result);
    }

    private static function fail(array $result): array
    {
        $line = sprintf('[kwtSMS] workflow SMS failed: %s %s', $result['code'] ?? '', $result['description'] ?? '');
        if (self::$logger) {
            call_user_func(self::$logger, $line);
        } elseif (function_exists('logger')) {
            logger()->warning($line);
        }

        return $result;
    }

    /** The workflow with this package's actions removed, for the parent class to run the rest. */
    public static function withoutOwnActions(mixed $workflow): mixed
    {
        $copy = clone $workflow;
        $copy->actions = array_values(array_filter((array) $workflow->actions, function ($a) {
            return ($a['id'] ?? '') !== self::ID;
        }));

        return $copy;
    }

    /** @return array<int, string> the SMS texts of this package's actions in the workflow */
    public static function texts(mixed $workflow): array
    {
        $texts = [];
        foreach ((array) $workflow->actions as $a) {
            if (($a['id'] ?? '') === self::ID) {
                $texts[] = (string) ($a['value'] ?? '');
            }
        }

        return $texts;
    }
}
