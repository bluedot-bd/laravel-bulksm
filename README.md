# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bluedot-bd/laravel-bulk-sms.svg?style=flat-square)](https://packagist.org/packages/bluedot-bd/bluedot-bd/laravel-bulk-sms)
[![Total Downloads](https://img.shields.io/packagist/dt/bluedot-bd/laravel-bulk-sms.svg?style=flat-square)](https://packagist.org/packages/bluedot-bd/laravel-bulk-sms)
![GitHub Actions](https://github.com/bluedot-bd/laravel-bulksms/actions/workflows/main.yml/badge.svg)

*"laravel-bulksms"* is a Laravel package for integrating with any HTTP-based SMS gateway. The package is compatible with SMS providers in Bangladesh and other countries, and allows for the sending of notifications via Laravel notifications. It also includes a feature for checking the balance of an SMS account with a supported provider. This package can be useful for integrating SMS functionality into a Laravel-based application, sending sms messages to people, and keeping track of SMS usage and account balances.

## Installation

You can install the package via composer:

```bash
composer require bluedot-bd/laravel-bulksms
```

## Usage

Check and Save Config
```php
use LaravelBulksms;
$sms = new LaravelBulksms(); // config name not needed
$params = [
    'api_mode'         => 'dry', // dry/live
    'send_method'      => 'GET', // GET/POST
    'send_url'         => '',
    'send_header'      => '', // Comma separated header
    'send_success'     => '', // valid regex or empty (without delimiter)
    'send_error'       => '', // valid regex or empty (without delimiter)
    'balance_url'      => '',
    'balance_method'   => '', // GET/POST
    'balance_header'   => '', // Comma separated header
    'balance_key'      => '', // json object key
];
$config = 'smsdone'; // any name you want, this will be your config file name
$url = ''; // your api url with all params
try {
    $sms->checkAndSave($params, $url, $config);
} catch (Exception $e) {
    // Get Error from Exception
    // If you get this error, create a issue with your api url (please remove any api key or password)
}

```

You can use it in Notification (for sending sms):
```php
use LaravelBulksms;
use BluedotBd\LaravelBulksms\SmsChannel;

public function via($notifiable)
{
    return [SmsChannel::class];
}

/**
 * Get the sms representation of the notification.
 *
 * @param  mixed  $notifiable
 */
public function toSms($notifiable)
{
    return (new LaravelBulksms("config_file_name"))
        ->to()
        ->line();
}
```

or you can use it directly:
```php
use LaravelBulksms;
$sms = new LaravelBulksms("config_file_name");
try {
    $sms->to('01xxxx')->message('Your SMS Text')->send();   
} catch (Exception $e) {
    // SMS Sending Error
}
```

or Send SMS useing Laravel Queued Jobs
```php
dispatch(new BluedotBd\LaravelBulksms\Jobs\SendSMS($config,$number, $message));
// or
dispatch((new BluedotBd\LaravelBulksms\Jobs\SendSMS($config,$number, $message))->onQueue('high'));
// or
dispatch((new BluedotBd\LaravelBulksms\Jobs\SendSMS($config,$number, $message))->delay(60));
```

Get Balance if Supported
```php
use LaravelBulksms;
$sms = new LaravelBulksms("config_file_name");
$sms->balance(); // returns float
```



### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email saiful@bluedot.ltd instead of using the issue tracker.

## Credits

-   [Shaiful Islam](https://github.com/bluedot-bd)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
