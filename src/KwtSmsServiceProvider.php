<?php

namespace KwtSms\Krayin;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class KwtSmsServiceProvider extends ServiceProvider
{
    // 2026-11-07 01:25 +03: Krayin stores config values in plain text and writes a password field's saved value back into
    // the settings page HTML. The API password is therefore moved to an encrypted row after every save and the visible
    // field is blanked. Found in the Krayin 2.2.0 Docker QA, task krayin-new.
    public const PLAIN = 'kwtsms.settings.account.password';
    public const ENCRYPTED = 'kwtsms.settings.account.password_encrypted';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/core_config.php', 'core_config');
    }

    public function boot(): void
    {
        // Krayin picks the workflow entity class from config at run time: ours adds the SMS action.
        config([
            'workflows.trigger_entities.leads.class' => Workflow\Lead::class,
            'workflows.trigger_entities.persons.class' => Workflow\Person::class,
        ]);

        // 2026-11-07 01:25 +03: see PLAIN / ENCRYPTED above. Task krayin-new.
        // A closure, not [class, method]: Laravel would try to build this provider through the container and fail.
        Event::listen('core.configuration.save.after', static function (): void {
            self::securePassword();
        });
    }

    /** Moves a newly entered API password into the encrypted row and blanks the plain one. */
    public static function securePassword(): void
    {
        $model = \Webkul\Core\Models\CoreConfig::class;
        $plain = (string) $model::where('code', self::PLAIN)->value('value');
        if ($plain === '') {
            return; // field left empty: keep the saved encrypted password
        }
        $model::updateOrCreate(['code' => self::ENCRYPTED], ['value' => Crypt::encryptString($plain)]);
        $model::where('code', self::PLAIN)->update(['value' => '']);
    }

    /** @return array{username: string, password: string, sender: string, test_mode: bool} */
    public static function settings(): array
    {
        $get = fn (string $f) => (string) core()->getConfigData('kwtsms.settings.account.' . $f);

        return [
            'username' => $get('username'),
            // 2026-11-07 01:25 +03: encrypted copy first (see securePassword). Task krayin-new.
            'password' => Secret::resolvePassword($get('password_encrypted'), $get('password'), fn ($v) => Crypt::decryptString($v)),
            'sender' => $get('sender') ?: 'KWT-SMS',
            'test_mode' => $get('test_mode') === '1',
        ];
    }
}
