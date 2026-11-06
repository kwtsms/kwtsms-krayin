<?php
/**
 * Unit tests with small stand-ins for the Krayin classes (no Krayin, no network). Numbers use country code 999
 * (unassigned), so nobody can receive them.
 *
 * Usage: php tests/run.php
 */

namespace Webkul\Automation\Helpers\Entity {
    class Lead
    {
        public array $ran = [];

        public function getActions(): array
        {
            return [['id' => 'add_tag', 'name' => 'Add tag']];
        }

        public function executeActions(mixed $workflow, mixed $lead): void
        {
            $this->ran = array_column($workflow->actions, 'id');
        }

        public function replacePlaceholders(mixed $entity, string $content, string $context = 'html'): string
        {
            return str_replace('{%leads.title%}', $entity->title ?? '', $content);
        }
    }

    class Person extends Lead
    {
    }
}

namespace {
    require __DIR__ . '/../src/SmsAction.php';
    require __DIR__ . '/stubs/provider.php';
    require __DIR__ . '/../src/Workflow/Lead.php';
    require __DIR__ . '/../src/Workflow/Person.php';

    use KwtSms\Krayin\SmsAction;

    $fails = 0;
    $count = 0;
    function check($name, $cond)
    {
        global $fails, $count;
        $count++;
        if (! $cond) {
            $fails++;
            echo "FAIL $name\n";
        }
    }

    $calls = [];
    $logs = [];
    SmsAction::$transport = function ($number, $text, $config) use (&$calls) {
        $calls[] = compact('number', 'text', 'config');

        return ['result' => 'OK'];
    };
    SmsAction::$logger = function ($line) use (&$logs) {
        $logs[] = $line;
    };

    // number picking
    check('mobile label preferred', SmsAction::pickNumber([['value' => '99912345670', 'label' => 'work'], ['value' => '99912345671', 'label' => 'mobile']]) === '99912345671');
    check('first number', SmsAction::pickNumber([['value' => ' ', 'label' => 'home'], ['value' => '99912345672', 'label' => 'work']]) === '99912345672');
    check('json string', SmsAction::pickNumber('[{"value":"99912345673","label":"home"}]') === '99912345673');
    check('none', SmsAction::pickNumber(null) === '' && SmsAction::pickNumber([]) === '');

    $cfg = ['username' => 'krayin_username', 'password' => 'krayin_password', 'sender' => 'KWT-SMS', 'test_mode' => true];

    // send guards
    check('not configured', SmsAction::send('99912345678', 'x', ['username' => '', 'password' => ''])['code'] === 'NOT_CONFIGURED');
    check('no number', SmsAction::send('', 'x', $cfg)['code'] === 'NO_NUMBER');
    check('empty text', SmsAction::send('99912345678', ' <p></p> ', $cfg)['code'] === 'EMPTY_MESSAGE');
    check('failures logged', count($logs) === 3);
    check('html stripped', SmsAction::send('99912345678', '<p>Hello <b>Sara</b> &amp; team</p>', $cfg)['result'] === 'OK'
        && $calls[0]['text'] === 'Hello Sara & team');

    // lead workflow: SMS action runs, the rest goes to Krayin's own handler
    $calls = [];
    $lead = new KwtSms\Krayin\Workflow\Lead();
    check('action listed', in_array('send_sms_to_person', array_column($lead->getActions(), 'id'), true)
        && in_array('add_tag', array_column($lead->getActions(), 'id'), true));
    $workflow = (object) ['actions' => [
        ['id' => 'add_tag', 'value' => 'vip'],
        ['id' => 'send_sms_to_person', 'value' => 'Thanks for your interest in {%leads.title%}'],
    ]];
    $entity = (object) ['title' => 'Bulk SMS plan', 'person' => (object) ['contact_numbers' => [['value' => '99912345678', 'label' => 'mobile']]]];
    $lead->executeActions($workflow, $entity);
    check('lead sms sent', count($calls) === 1 && $calls[0]['number'] === '99912345678');
    check('placeholder filled', $calls[0]['text'] === 'Thanks for your interest in Bulk SMS plan');
    check('other actions passed on', $lead->ran === ['add_tag']);
    check('original workflow untouched', count($workflow->actions) === 2);

    // lead without a person
    $calls = [];
    $lead->executeActions($workflow, (object) ['title' => 'x', 'person' => null]);
    check('no person, no sms', count($calls) === 0);

    // person workflow
    $calls = [];
    $person = new KwtSms\Krayin\Workflow\Person();
    $person->executeActions((object) ['actions' => [['id' => 'send_sms_to_person', 'value' => 'Hi']]],
        (object) ['contact_numbers' => [['value' => '99912345679', 'label' => 'work']]]);
    check('person sms sent', count($calls) === 1 && $calls[0]['number'] === '99912345679' && $person->ran === []);

    // a throwing client never breaks the workflow
    SmsAction::$transport = function () {
        throw new RuntimeException('boom');
    };
    check('exception caught', SmsAction::send('99912345678', 'x', $cfg)['code'] === 'EXCEPTION');

    // 2026-11-07 01:25 +03: password lookup (encrypted copy first, plain fallback). Task krayin-new.
    require __DIR__ . '/../src/Secret.php';
    $dec = fn ($v) => str_replace('enc:', '', $v);
    check('encrypted copy wins', \KwtSms\Krayin\Secret::resolvePassword('enc:new', 'old', $dec) === 'new');
    check('plain fallback when no encrypted copy', \KwtSms\Krayin\Secret::resolvePassword('', 'old', $dec) === 'old');
    check('plain fallback when decrypt fails', \KwtSms\Krayin\Secret::resolvePassword('bad', 'old', function () { throw new \RuntimeException('x'); }) === 'old');

    echo $fails ? "FAILED $fails of $count\n" : "OK $count tests\n";
    exit($fails ? 1 : 0);
}
