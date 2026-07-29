# SecHole

A CLI tool that reads a `composer.lock`, reports known vulnerabilities for **symfony**, **twig**, **doctrine** and **illuminate** packages, and recommends the closest version with fewer advisories.

Built on [entropy](https://github.com/TomasVotruba/entropy): reflection-based DI container plus a console runner where a command's `run()` signature *is* its CLI contract.

## Layout

- `bin/sechole` — entry point; boots the container, autodiscovers `src/`, runs `ConsoleApplication`.
- `src/Command/AuditCommand.php` — the only command, `audit`.
- `src/ComposerLockParser.php` — reads the lock file, keeps watched vendors from both `packages` and `packages-dev`.
- `src/PackagistClient.php` — the only class touching the network: advisories API (one batched POST) and the p2 version list.
- `src/AdvisoryMatcher.php` — pure version/constraint logic, no I/O. All recommendation rules live here.
- `src/VulnerabilityAnalyser.php` — wires client and matcher into `PackageReport[]`.
- `src/ValueObject/` — `InstalledPackage`, `Advisory`, `PackageReport`.
- `tests/` — mirrors `src/`; fixtures under `tests/Fixture/`.

## Commands

```bash
bin/sechole audit composer.lock             # run the audit
bin/sechole audit composer.lock --details   # with advisory titles, CVEs, links
vendor/bin/phpunit                          # tests
```

## Conventions

- Watched vendors are the `WATCHED_VENDORS` constant in `ComposerLockParser`. Change the scope there, nowhere else.
- Keep I/O in `PackagistClient`. Anything with version or constraint logic belongs in `AdvisoryMatcher`, which stays pure so it can be unit tested without mocks.
- Versions are stored without the `v` prefix; strip it at parse time, not at compare time.
- Constraint matching goes through `composer/semver` (`Semver::satisfies`, `Comparator::greaterThan`). Never hand-roll version comparison.
- Recommendation rule: walk stable versions above the installed one from lowest up, take the first with fewer advisories, stop at the first clean one.
- Exit `0` when clean, `1` when any package is vulnerable, so CI can gate on it.
- Exceptions throw `SecHoleException`; `ConsoleApplication` already renders them, so no try/catch in `bin/sechole`.
- Command arguments and options come from the `run()` signature and its docblock. No attributes, no input objects.
