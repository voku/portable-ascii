# AGENTS.md

## Setup

- Install project dependencies with `composer install` from the repository root.
- Install documentation generator dependencies with `composer install` from `build/`.

## Validation

- Run the test suite with `php vendor/bin/phpunit -c phpunit.xml`.
- Run static analysis with `php vendor/bin/phpstan analyse -c phpstan.neon`.
- Also run the PHP 7-targeted static analysis with `php vendor/bin/phpstan analyse -c phpstan-php7.neon`.
- After changing PHP code, run PHPUnit and both PHPStan commands before wrapping up.

## Performance Comparison

- Use `PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 ./vendor/bin/phpunit --filter PerformanceRegressionTest` to collect the benchmark profile for the current worktree.
- When comparing the current `ASCII.php` against `master`, back up the current file first, e.g. `cp src/voku/helper/ASCII.php /tmp/portable-ascii-ASCII.php.backup`.
- Then run the benchmark twice:
  1. once with the current `ASCII.php`
  2. once after temporarily restoring only `src/voku/helper/ASCII.php` from `master`
- Restore the file from the backup copy after the `master` run instead of restoring from `HEAD`.
- Do it this way because `ASCII.php` may contain important unstaged local edits. A temporary `git restore --source master --worktree -- src/voku/helper/ASCII.php` is fine for the comparison run, but restoring from `HEAD` afterwards can silently discard local uncommitted performance fixes. Restoring from the backup gives a byte-for-byte return to the original local file.
- Use the `portable-ascii performance profile` block from PHPUnit for the actual comparison.
- Do not assume `master` will pass the current benchmark thresholds. The comparison may still be valid even if the `master` run fails an assertion, as long as the benchmark profile was printed.

## Documentation

- `README.md` is generated. Do not edit the generated API section by hand.
- Regenerate it from the repository root with `php build/generate_docs.php`.
- The generator reads `build/docs/base.md` and `src/voku/helper/ASCII.php`.

## Notes

- Keep changes focused and minimal.
- When updating public ASCII APIs, regenerate `README.md` if the API documentation changes.
