# Laravel Session Migrator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/krisell/laravel-session-migrator.svg?style=flat-square)](https://packagist.org/packages/krisell/laravel-session-migrator)
[![Total Downloads](https://img.shields.io/packagist/dt/krisell/laravel-session-migrator.svg?style=flat-square)](https://packagist.org/packages/krisell/laravel-session-migrator)
![GitHub Actions](https://github.com/krisell/laravel-session-migrator/actions/workflows/main.yml/badge.svg)

Migration Laravel session driver or serialization method in production without dropping any sessions.

## Installation

```bash
composer require krisell/laravel-session-migrator
```

## Features

This package allows production applications to update some of the session configuration without dropping
any active sessions and signing users out. More specifically, two session configuration options can be migrated:

1.  Serialization method (`php` or `json`), regardless of driver

    Laravel 9 introduced the option to serialize session data using `json` rather than php's `serialize`, and might
    be preferred both from a security perspective and performance-wise. The method is changed by specifying
    `'serialization' => 'json'` in `config/session.php`, but changing that in a production app would normally cause all
    active sessions to be invalidated and users to be signed out. With this package, this setting can be migrated
    transparently to users.

2.  Driver migration (from `file` or `cookie`)

    This package also allows to define a driver being migrated from, such that a new driver can be used without dropping
    any session data (technically, the old driver is used as a fallback `read` source, but all `writes` are performed
    against the new driver).

    Note that currently, only the `file` and `cookie` drivers are supported as being migrated _from_.

## Usage

Installing the package intentionally doesn't activate any of the migration features. In order to perform a migration,
update the session configuration to the new wanted settings, and then also perform one of the following:

- Add the following entry to your `config/session.php` file:

```php
'migrate' => [
    'serialization' => true, // Enables transparent serialization method migration
    'driver' => 'file' // Session driver you are migrating from
],
```

- Alternatively, you can set the following two environment variables:

```
SESSION_MIGRATE_SERIALIZATION=true
SESSION_MIGRATE_DRIVER=file
```

You can of course choose to only use the serialization migration, or only the driver migration. It should be very
rare that one need to migrate both these at the same time, although that is also supported.

Note specifically that `migrate.driver` is the driver you are migrating _from_, i.e. the previously used driver. The
new driver or serialization method is configured just as normal, in the config file or environment variables.

You only need this package during a short transition period where your application might still have session data using
the old format. The length of this depends on your session lifetime. As an example, if your session expires after 2 hours,
you only need to run this package in production for those two hours, although keeping it around longer does no harm. For performance reasons, you should certainly disable and preferable remove this package eventually after the transition has been completed.

## Pre-production applications
There is no reason to use this package in pre-production applications or local environments, execept of course for testing before using live. If dropping sessions doesn't matter in your use case, simply update the session configuration as normal and skip this package.

### Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email martin.krisell@gmail.com instead of using the issue tracker.

## Credits

- [Martin Krisell](https://github.com/krisell)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
