# kwtSMS for Krayin CRM

Send SMS from [Krayin CRM](https://krayincrm.com) workflows through [kwtSMS](https://www.kwtsms.com), the SMS gateway
for Kuwait and the Gulf.

## Features

- A new workflow action, **Send SMS to person**, for lead and person workflows (created, updated, and any condition
  Krayin workflows support).
- The text uses Krayin's own placeholders, for example `{%leads.title%}` or `{%persons.name%}`.
- Sends to the person's "mobile" number, or the first number on file.
- English and Arabic. Test mode: queued on kwtSMS, never delivered; their credits are held until you delete them from the Sending Queue in your kwtSMS account.
- A failed SMS never stops the workflow; it is written to the Laravel log.

## Requirements

- Krayin CRM 2.x (Laravel 12, PHP 8.3).
- A kwtSMS account with API access. [Sign up free](https://www.kwtsms.com/signup/).

## Installation

```sh
composer require kwtsms/krayin-kwtsms
php artisan optimize:clear
```

## Setup

1. Go to **Settings > Configuration > kwtSMS** and enter the API username and password of your kwtSMS account (not
   your mobile number), your Sender ID (default `KWT-SMS`) and, for a first try, turn on test mode.
2. Go to **Settings > Automation > Workflows**, create or edit a workflow for Leads or Persons, add the action
   **Send SMS to person** and type the SMS text.

Phone numbers on persons should be in international format, for example `965XXXXXXXX`.

## Arabic

Arabic messages are sent as Unicode: one SMS page holds 70 Arabic characters, against 160 English ones. Keep Arabic
texts short to save credits.

## Troubleshooting

Failed messages are logged as `[kwtSMS] workflow SMS failed` with the kwtSMS error code: `ERR003` wrong API login,
`ERR010` zero balance, `ERR008` banned Sender ID, `ERR024` the server IP is not in your kwtSMS API IP whitelist.

## Development

```sh
php tests/run.php     # unit tests with Krayin stand-ins, no network
```

## Support

Help: https://www.kwtsms.com/support.html | FAQ: https://www.kwtsms.com/faq/ | support@kwtsms.com

## Security

See [SECURITY.md](SECURITY.md).

## License

MIT, (c) kwtSMS
