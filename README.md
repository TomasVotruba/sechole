# SecHole

Find known vulnerabilities in your `composer.lock` and get the closest safe version to upgrade to.

Scoped to the packages that matter most in a typical PHP project: **symfony**, **twig**, **doctrine** and **illuminate**.

Requires PHP 8.4+.

<br>

## Install

```bash
composer install
```

<br>

## Usage

```bash
bin/sechole audit /path/to/composer.lock
```

```
Checking 6 packages
===================

 ------------------------- --------- ------------ ---------------------
  Package                   Current   Known CVEs   Recommended upgrade
 ------------------------- --------- ------------ ---------------------
  illuminate/database       8.0.0     3            8.40.0 (clean)
  symfony/http-foundation   4.4.0     3            5.4.50 (clean)
  symfony/http-kernel       4.4.0     2            4.4.50 (clean)
  symfony/security-http     4.4.0     3            5.4.53 (clean)
  twig/twig                 2.14.0    17           3.27.0 (clean)
 ------------------------- --------- ------------ ---------------------

 [ERROR] 5 vulnerable packages found
```

<br>

Want the actual CVEs? Add `--details`:

```bash
bin/sechole audit composer.lock --details
```

```
symfony/http-kernel 4.4.0
-------------------------
* [medium] CVE-2022-24894: Prevent storing cookie headers in HttpCache (CVE-2022-24894) https://symfony.com/cve-2022-24894
* [high] CVE-2020-15094: Prevent RCE when calling untrusted remote with CachingHttpClient (CVE-2020-15094) https://symfony.com/cve-2020-15094
```

<br>

The path argument defaults to `composer.lock` in the current directory:

```bash
bin/sechole audit
```

<br>

## Exit codes

| Code | Meaning |
| --- | --- |
| `0` | No known vulnerability found |
| `1` | At least one vulnerable package found |

Use it in CI to fail a build on a known CVE.

<br>

## How the recommendation works

1. Every advisory for the package is pulled from the [Packagist security advisories API](https://packagist.org/apidoc) - no API token needed.
2. Advisories are matched against the installed version through `composer/semver`, so the same constraint syntax Composer uses (`>=4.0.0,<4.4.50|>=5.0.0,<5.4.20`) is respected.
3. Stable versions above the installed one are walked from the lowest up. The first version carrying **fewer** advisories wins, and the walk stops as soon as one is completely clean.

So you get the smallest upgrade that actually buys you something, not "just go to latest".

<br>

Two things worth knowing:

- A recommendation may cross a major version (`twig/twig` 2.x to 3.x) when no release in the current branch is clean. That is a real upgrade, not a patch.
- `none available` means every published version above the installed one is still affected.

<br>

## Tests

```bash
vendor/bin/phpunit
```

<br>

Built on [entropy](https://github.com/TomasVotruba/entropy) - DI container and console runner, no config files.
