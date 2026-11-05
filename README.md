# UAMSWP News Syndication 2020

## Tests

```bash
composer install
composer test:unit
```

The PHP floor is 8.2 and the development PHP is 8.4 (`config.platform.php` in `composer.json`). The harness is still PHPUnit 9; the move to Pest is its own ticket.

## Code quality

```bash
composer test:refactor
composer stan
composer test:pint
```

Rector, PHPStan and Pint come from the shared `code-quality` standard. `composer refactor` and `composer pint` apply the changes the two checks report.

## Continuous integration

Pull requests are gated by the GitHub Actions workflow in [`.github/workflows/ci.yml`](.github/workflows/ci.yml). It runs Pint, Rector (dry run), PHPStan and the Unit suite on PHP 8.4 on every pull request and on pushes to `dev`, `staging` and `master`. A failing job fails the `CI` check.
