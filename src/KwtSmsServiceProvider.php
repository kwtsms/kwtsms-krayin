<?php

namespace KwtSms\Krayin;

use Illuminate\Support\ServiceProvider;

class KwtSmsServiceProvider extends ServiceProvider
{
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
    }

    /** @return array{username: string, password: string, sender: string, test_mode: bool} */
    public static function settings(): array
    {
        $get = fn (string $f) => (string) core()->getConfigData('kwtsms.settings.account.' . $f);

        return [
            'username' => $get('username'),
            'password' => $get('password'),
            'sender' => $get('sender') ?: 'KWT-SMS',
            'test_mode' => $get('test_mode') === '1',
        ];
    }
}
