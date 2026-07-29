# SecHole

Find known vulnerabilities in a `composer.lock`, and see exactly which upgrade gets rid of them.

Most audit tools tell you that you are vulnerable. This one tells you where to go: every branch you could upgrade to, and how many CVEs are waiting for you there.

Scoped to the packages that carry the weight in a typical PHP project - **symfony**, **twig**, **doctrine** and **illuminate**.

Runs on PHP 7.2 and up, so you can point it at the legacy projects that need it most.

<br>

## Install

```bash
composer install
```

<br>

## Usage

Point it at a `composer.lock`, or at the project directory holding one:

```bash
bin/sechole /path/to/composer.lock
bin/sechole /path/to/project
```

Leave the path out and `composer.lock` in the current directory is used:

```bash
bin/sechole
```

<br>

## What you get

One table per vulnerable package, one row per minor branch published above the version you have:

```
Checking 6 packages
===================

symfony/http-kernel 4.4.0 - 2 known CVEs
----------------------------------------

 --------------- --------------- -----------------
     Version       Known CVEs        Released
 --------------- --------------- -----------------
         4.4.51               -        2023-11-10
         5.0.11               2        2020-07-24
         5.1.11               1        2021-01-27
         5.2.14               2        2021-07-29
         5.3.16               1        2022-03-01
         5.4.53               -        2026-05-27
         6.0.20               -        2023-02-01
         6.1.12               -        2023-02-01
 --------------- --------------- -----------------

 [ERROR] 5 vulnerable packages found
```

<br>

Counts do not only go down as you move up: `5.1` has one CVE, `5.2` has two. Newer is not automatically safer, which is why you get the whole list instead of one suggestion. How far you are willing to jump is your call.

<br>

## Use it in CI

Exit code is `0` when clean and `1` when any package is vulnerable, so a build fails on a known CVE:

```bash
bin/sechole composer.lock
```

New advisories land all the time, so a clean run today is not a clean run next month. This belongs in CI, not in your notes.

<br>

## Where the data comes from

The [Packagist security advisories API](https://packagist.org/apidoc) - no account, no API token, no local database to keep fresh.

Each minor branch is represented by its **latest stable release**, because that is the version you would actually land on. Advisory constraints are matched with `composer/semver`, the same resolver Composer itself uses, so a range like `>=4.0.0,<4.4.50|>=5.0.0,<5.4.20` is read exactly as Composer reads it.
