# Lunar PayPal

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lancodev/lunar-paypal.svg?style=flat-square)](https://packagist.org/packages/lancodev/lunar-paypal)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/lancodev/lunar-paypal/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/lancodev/lunar-paypal/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/lancodev/lunar-paypal/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/lancodev/lunar-paypal/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/lancodev/lunar-paypal.svg?style=flat-square)](https://packagist.org/packages/lancodev/lunar-paypal)

A custom PayPal payment driver for the [Lunar](https://lunarphp.io) framework.

## Installation

You can install the package via composer:

```bash
composer require lancodev/lunar-paypal
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="lunar-paypal-views"
```

## Usage

The package ships with a checkout component that allows payment via PayPal Express checkout or via on-site credit card payment.

To add the component to your checkout page, add the following to your checkout page:

```php
<livewire:paypal.payment :cart="$cart"
                         :returnUrl="route('checkout-success.view')" />
```

where `$cart` is the cart instance and `route('checkout-success.view')` is the route to redirect to after a successful payment.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Kyle Anderson](https://github.com/lancodev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
