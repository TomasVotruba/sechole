# SecHole

A CLI tool that reads a `composer.lock`, reports known vulnerabilities for **symfony**, **twig**, **doctrine** and **illuminate** packages, and recommends the closest version with fewer advisories.

## PHP 7.2 baseline

The tool must run on **PHP 7.2**, because it is meant to be pointed at legacy projects that are stuck there. That is the whole point of the constraint - do not raise it for convenience.

Not available, do not use:

- constructor property promotion, `readonly`, attributes, `match`, enums, named arguments, `str_contains` (PHP 8.x)
- typed properties, arrow functions, spread in array literals, `??=` (PHP 7.4)
- trailing comma in calls, `JSON_THROW_ON_ERROR` (PHP 7.3)

Use `@var` docblocks for properties, `function () use (...)` closures, `array_merge()` instead of spread, `list()` destructuring, and a manual `json_last_error()` check after `json_decode()`.

CI runs the suite on 7.2, 8.0 and 8.4, so anything newer breaks the build.

## Layout

- `bin/sechole` — entry point; parses `$argv`, wires services by hand, catches `SecHoleException`.
- `src/Command/AuditCommand.php` — the audit run and all rendering decisions.
- `src/Console/ConsolePrinter.php` — output: table, sections, listings, ANSI status lines.
- `src/ComposerLockParser.php` — reads the lock file, keeps watched vendors from both `packages` and `packages-dev`.
- `src/PackagistClient.php` — the only class touching the network: advisories API (one batched POST) and the p2 version list.
- `src/AdvisoryMatcher.php` — pure version/constraint logic, no I/O. All recommendation rules live here.
- `src/VulnerabilityAnalyser.php` — wires client and matcher into `PackageReport[]`.
- `src/ValueObject/` — `InstalledPackage`, `Advisory`, `PackageReport`.
- `tests/` — mirrors `src/`; fixtures under `tests/Fixture/`.

## Commands

```bash
bin/sechole composer.lock             # run the audit
bin/sechole composer.lock --details   # with advisory titles, CVEs, links
vendor/bin/phpunit                    # tests
php7.2 -l src/SomeFile.php            # verify the baseline still holds
```

## Conventions

- No framework. Services are plain classes wired in `bin/sechole`; keep constructors explicit rather than adding a container.
- Watched vendors are the `WATCHED_VENDORS` constant in `ComposerLockParser`. Change the scope there, nowhere else.
- Keep I/O in `PackagistClient`. Anything with version or constraint logic belongs in `AdvisoryMatcher`, which stays pure so it can be unit tested without mocks.
- Versions are stored without the `v` prefix; strip it at parse time, not at compare time.
- Constraint matching goes through `composer/semver` (`Semver::satisfies`, `Comparator::greaterThan`). Never hand-roll version comparison.
- Recommendation rule: walk stable versions above the installed one from lowest up, take the first with fewer advisories, stop at the first clean one.
- Exit `0` when clean, `1` when any package is vulnerable, so CI can gate on it.
- `composer.lock` is not committed, so CI runs `composer update` per PHP version.
