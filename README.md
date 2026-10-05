# UAMSWP News Syndication 2020

## Tests

```bash
composer install
composer test:unit
```

PHP floor is 7.4, so the harness is PHPUnit 9 (Pest needs PHP 8.2+).

## Continuous integration

Pull requests are gated by the GitHub Actions workflow in [`.github/workflows/ci.yml`](.github/workflows/ci.yml). It runs Pint, Rector (dry-run), PHPStan, and the Unit suite on every push and pull request. A red check fails the workflow.
