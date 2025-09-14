# Filament Live Preview

A Filament plugin to add a preview screen to your pages using websockets. The screen will render your website with the data from Filament, without saving it. Heavily based on [Filament Peek](https://github.com/pboivin/filament-peek).

## Installation

You can install the package via composer:

```bash
composer require wotz/filament-live-preview
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-live-preview-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-live-preview-views"
```

## Documentation

For the full documentation, check [here](./docs/index.md).

## Testing

```bash
vendor/bin/pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Upgrading

Please see [UPGRADING](UPGRADING.md) for more information on how to upgrade to a new version.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover any security-related issues, please email info@codedor.be instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
