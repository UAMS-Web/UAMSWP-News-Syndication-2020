Closes #95

Part of #33.

Characterization tests for the three functions in `includes/news-syndicate.php`, in `tests/Unit/NewsBootstrapTest.php`: which hooks `bootstrap()` registers and on which callbacks, what `activate_shortcodes()` registers and fires, and what `clear_local_news_cache()` writes. The file registers its `plugins_loaded` callback as it loads, and `tests/bootstrap.php` has already loaded it in the Pest process, so that one registration is checked in a child process (`tests/Support/load-news-syndicate.php`). No source file changes.

Mutation run (`pest --mutate --path=includes/news-syndicate.php`, Windows, PHP 8.4, Pest 5): 11 mutants tested, 11 killed, no survivors. One further mutant is reported uncovered:

- Line 7 (`RemoveFunctionCall` on the top-level `add_action('plugins_loaded', ...)`): the call runs at load, before Pest can attribute coverage to a test. Deleting the line by hand fails the child-process test, so it is killed. `@pest-mutate-ignore` did not suppress it, so the file is left unannotated.

## Test plan

- [x] Every function in `includes/news-syndicate.php` has a test that fails when its observable behavior changes
- [x] The mutation run reports no unexplained survivor and no untested mutant, with the line 7 exception listed above
- [x] The platform and mutant counts are recorded on #33
- [x] The Unit suite is green on the PR's head
