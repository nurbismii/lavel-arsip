# Task 8 Report — Laravel Mix frontend hardening

## Scope and result

- Updated the nine direct frontend constraints in `package.json` exactly as specified.
- Kept the existing npm scripts, CommonJS entries, `resources/js/bootstrap.js`, and `webpack.mix.js` unchanged.
- Re-resolved `package-lock.json`, applied the non-major transitive updates offered by `npm audit fix`, and verified a clean install with `npm ci`.
- Regenerated the production bundle with Laravel Mix 6.0.49. `public/js/app.js`, its source map, and its license metadata changed; the generated CSS and Mix manifest were byte-identical to HEAD.

## Direct resolved versions

| Package | Constraint | Resolved |
| --- | --- | --- |
| `@popperjs/core` | `^2.11.8` | `2.11.8` |
| `axios` | `^1.18.1` | `1.19.0` |
| `bootstrap` | `^5.3.8` | `5.3.8` |
| `laravel-mix` | `^6.0.49` | `6.0.49` |
| `lodash` | `^4.17.21` | `4.18.1` |
| `postcss` | `^8.5.6` | `8.5.26` |
| `resolve-url-loader` | `^5.0.0` | `5.0.0` |
| `sass` | `^1.90.0` | `1.99.0` |
| `sass-loader` | `^12.6.0` | `12.6.0` |

## Audit evidence

- Initial `npm audit --audit-level=high`: 27 findings (6 low, 11 moderate, 8 high, 2 critical).
- `npm audit fix --dry-run` showed patch/minor transitive changes only; no direct dependency major and no Mix replacement.
- Applied `npm audit fix`: 11 findings remain (5 low, 6 moderate, 0 high, 0 critical).
- Final `npm audit --audit-level=high` exits 0.
- Remaining advisories have no available fix and originate from Laravel Mix transitive chains: `elliptic` through `node-libs-browser`, and `uuid` through `webpack-notifier` / `webpack-dev-server`. Resolving them requires separately approved replacement of the Mix toolchain; they are not runtime application dependencies shipped as server packages.

## Build and asset evidence

- `npm ci`: installed 771 packages from the committed lockfile and reported the same 5 low / 6 moderate findings.
- `npm run production`: exit 0, Laravel Mix 6.0.49, Webpack compiled successfully.
- Output sizes: `public/js/app.js` 197 KiB and `public/css/app.css` 221 KiB.
- `node --check public/js/app.js`: exit 0.
- `public/mix-manifest.json` contains `/css/app.css`, `/js/app.js`, and their source-map entries.
- Both `public/css/app.css` and `public/js/app.js` exist.
- `public/js/app.js` points to `app.js.LICENSE.txt` and `app.js.map`; both regenerated files are included so bundle metadata remains consistent.

Changed generated assets:

- `public/js/app.js`
- `public/js/app.js.LICENSE.txt`
- `public/js/app.js.map`

Regenerated but byte-identical, so not included as content changes:

- `public/css/app.css`
- `public/css/app.css.map`
- `public/mix-manifest.json`

## Blade reference verification

The brief expected existing `mix()` calls, but the baseline project has no `mix()` references. The working references were preserved rather than changed without product scope:

- `resources/views/layouts/guest.blade.php:18` uses `asset('css/app.css')`.
- `resources/views/layouts/app.blade.php:16` uses `asset('js/app.js')`.
- Those paths resolve directly to the verified generated files under `public/css/app.css` and `public/js/app.js`.

This is compatible with the current non-versioned Mix manifest. If cache-busted filenames are enabled later, changing the layouts to `mix()` should be a separately reviewed behavior change.

## Laravel verification

- Default shell PHP is 7.4 and cannot boot the Laravel 13 dependency set.
- `C:\xampp\php85\php.exe artisan test`: 32 tests passed, 107 assertions, 0 failures.
- `git diff --check`: no whitespace errors.

## Production note

No production system was accessed. The remaining low/moderate advisories are accepted for this Mix-preserving task; a future Vite migration should retire the stale development-server dependency chains instead of forcing unsupported majors into Laravel Mix 6.
