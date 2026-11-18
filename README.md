# UAMSWP News Syndication 2020

A WordPress plugin that shows news posts from another WordPress site, from this site, or from both, through the `[uamswp_news]` shortcode, as headlines, excerpts, cards, a grid, side-by-side items or full articles.

The documentation (a walkthrough, guides, every shortcode attribute, the developer hooks and troubleshooting) is in the [wiki](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/wiki).

## Requirements

- WordPress 6.8 or later
- PHP 8.2 or later

## Installation

1. Download the source code zip of the latest release from [Releases](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/releases).
2. In WordPress, go to **Plugins**, **Add New Plugin**, **Upload Plugin**, choose the zip and install it.
3. Activate **UAMSWP News Syndication (2020)**.
4. Add a shortcode to a page: see [Your first news list](https://github.com/UAMS-Web/UAMSWP-News-Syndication-2020/wiki/Your-first-news-list).

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

## Credits

Developed and maintained by UAMS Web Services.

Developers:

- Josh Daugherty
- Todd McKee
- Brent Passmore
