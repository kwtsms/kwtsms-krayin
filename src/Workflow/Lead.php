<?php

namespace KwtSms\Krayin\Workflow;

use KwtSms\Krayin\KwtSmsServiceProvider;
use KwtSms\Krayin\SmsAction;
use Webkul\Automation\Helpers\Entity\Lead as BaseLead;

/** Krayin's lead workflow entity plus "Send SMS to person" (the lead's contact person). */
class Lead extends BaseLead
{
    public function getActions(): array
    {
        return array_merge(parent::getActions(), [SmsAction::definition()]);
    }

    public function executeActions(mixed $workflow, mixed $lead): void
    {
        foreach (SmsAction::texts($workflow) as $text) {
            SmsAction::send(SmsAction::pickNumber($lead->person?->contact_numbers),
                $this->replacePlaceholders($lead, $text, 'text'), KwtSmsServiceProvider::settings());
        }
        parent::executeActions(SmsAction::withoutOwnActions($workflow), $lead);
    }
}
