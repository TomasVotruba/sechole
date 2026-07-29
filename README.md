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

symfony/http-kernel 4.4.0 - 2 known CVEs
----------------------------------------
 -------- ---------------- ------------
  Branch   Latest release   Known CVEs
 -------- ---------------- ------------
  4.4      4.4.51           none
  5.0      5.0.11           2
  5.1      5.1.11           1
  5.2      5.2.14           2
  5.3      5.3.16           1
  5.4      5.4.53           none
  6.0      6.0.20           none
 -------- ---------------- ------------

 [ERROR] 5 vulnerable packages found
```

Every minor branch published above the version you have is listed with the number of known CVEs still affecting it, so you can pick the upgrade you are willing to do - the nearest clean patch, or a bigger jump.

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

## How the numbers are worked out

Advisories come from the [Packagist security advisories API](https://packagist.org/apidoc) - no account, no API token.

Each minor branch is represented by its **latest stable release**, because that is the version you would actually land on. Its constraint is matched against every advisory for that package, and the remaining CVEs are counted.

<br>

Two things worth knowing:

- `none` means no known CVE affects the latest release of that branch today. New advisories get published all the time, so re-run it.
- Branches below the version you have are skipped, and so is your own version - only real upgrades are listed.
