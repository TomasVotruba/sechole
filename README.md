# SecHole

Find known vulnerabilities in your `composer.lock`, and see exactly which upgrade gets rid of them.

Scoped to the packages that matter most in a typical PHP project: **symfony**, **twig**, **doctrine** and **illuminate**.

Runs on PHP 7.2 and up, so you can point it at the old projects that need it most.

<br>

## Install

```bash
composer install
```

<br>

## Usage

Point it at a `composer.lock`, or just at the project directory holding one:

```bash
bin/sechole /path/to/composer.lock
bin/sechole /path/to/project
```

<br>

Leave the path out and `composer.lock` in the current directory is used:

```bash
bin/sechole
```

<br>

## What you get

Every vulnerable package gets its own table: one row per minor branch published above the version you have, showing how many known CVEs still hit it and when it came out.

```
Checking 6 packages
===================

symfony/http-kernel 4.4.0 - 2 known CVEs
----------------------------------------

 --------------- --------------- -----------------
        Version      Known CVEs          Released
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

Reading it:

- `-` in the CVE column means no known vulnerability is left on that branch. Here `4.4.51` is a patch away and already clean - no major upgrade needed.
- A count above 20 is printed in bold, because that branch is beyond saving.
- The minor branch is bold and the patch part dimmed, so `4.4`.51 and `5.4`.53 line up as the real decision.
- `Released` tells you whether a branch is still alive. A clean branch last touched in 2021 is clean because nobody looks at it anymore.

No single "recommended" version is picked for you. How far you are willing to jump is your call.

<br>

## Use it in CI

The exit code is `0` when nothing was found and `1` when at least one package is vulnerable, so a build fails on a known CVE:

```bash
bin/sechole composer.lock
```

<br>

## Where the data comes from

The [Packagist security advisories API](https://packagist.org/apidoc) - no account, no API token, no local database to keep fresh.

Each minor branch is represented by its **latest stable release**, because that is the version you would actually land on. Advisory constraints are matched with `composer/semver`, the same resolver Composer itself uses.

New advisories get published all the time, so a clean run today is not a clean run next month. Put it in CI.
