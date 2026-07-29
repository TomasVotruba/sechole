# SecHole

Find known vulnerabilities in your `composer.lock` and get the closest safe version to upgrade to.

Scoped to the packages that matter most in a typical PHP project: **symfony**, **twig**, **doctrine** and **illuminate**.

Runs on PHP 7.2 and up.

<br>

## Install

```bash
composer install
```

<br>

## Usage

```bash
bin/sechole /path/to/composer.lock
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
bin/sechole composer.lock --details
```

```
symfony/http-kernel 4.4.0
-------------------------
* [medium] CVE-2022-24894: Prevent storing cookie headers in HttpCache (CVE-2022-24894) https://symfony.com/cve-2022-24894
* [high] CVE-2020-15094: Prevent RCE when calling untrusted remote with CachingHttpClient (CVE-2020-15094) https://symfony.com/cve-2020-15094
```

<br>

Leave the path out and `composer.lock` in the current directory is used:

```bash
bin/sechole
```

<br>

## Use it in CI

The exit code is `0` when nothing was found and `1` when at least one package is vulnerable, so a build fails on a known CVE:

```bash
bin/sechole composer.lock
```

<br>

## How the recommended version is picked

Advisories come from the [Packagist security advisories API](https://packagist.org/apidoc) - no account, no API token.

Every published version above the one you have is checked from the lowest up, and the first one carrying fewer known vulnerabilities wins. The search stops as soon as a completely clean version is found.

So you get the smallest upgrade that actually helps, not "just go to latest".

<br>

Two things worth knowing:

- A recommendation may cross a major version (`twig/twig` 2.x to 3.x) when no release in your current branch is clean. That is a real upgrade, not a patch.
- `none available` means every published version above yours is still affected.
