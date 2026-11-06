<?php

namespace KwtSms\Krayin\Workflow;

use KwtSms\Krayin\KwtSmsServiceProvider;
use KwtSms\Krayin\SmsAction;
use Webkul\Automation\Helpers\Entity\Person as BasePerson;

/** Krayin's person workflow entity plus "Send SMS to person". */
class Person extends BasePerson
{
    public function getActions(): array
    {
        return array_merge(parent::getActions(), [SmsAction::definition()]);
    }

    public function executeActions(mixed $workflow, mixed $person): void
    {
        foreach (SmsAction::texts($workflow) as $text) {
            SmsAction::send(SmsAction::pickNumber($person->contact_numbers),
                $this->replacePlaceholders($person, $text, 'text'), KwtSmsServiceProvider::settings());
        }
        parent::executeActions(SmsAction::withoutOwnActions($workflow), $person);
    }
}
