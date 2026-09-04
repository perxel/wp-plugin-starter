# CLAUDE.md

Guidance for working on this repository.

## What this is

`perxel-plugin-name` - a **public** WordPress plugin (repo
`github.com/perxel/wp-plugin-name`, WordPress.org slug `perxel-plugin-name`,
published under the `phucbm` .org account, branded Perxel).

It was scaffolded from
[`perxel/wp-plugin-starter`](https://github.com/perxel/wp-plugin-starter). If
this file still says "perxel-plugin-name" / "PXPREFIX" / "PluginName", the
template tokens have not been replaced yet - see the starter README.

## Layout

```
perxel-plugin-name.php      Main file: header, constants, autoloader, UI-kit loader, boot
uninstall.php               Deletes the option (and any custom tables) on delete
includes/*.php              One PSR-4-ish class per concern, namespace Perxel\PluginName\
includes/views/*.php        Dumb admin templates, fed vars by the screen classes
assets/css, assets/js       Admin-only CSS/JS (plugin-specific; layout comes from the kit)
vendor/perxel-ui/           Shared admin-UI kit - vendored, see below
languages/                  .pot template
readme.txt                  WordPress.org listing (keep in sync with README.md + version)
.wordpress-org/             Listing assets (icon, banner, screenshots) - not shipped
.github/workflows/          lint.yml (PHPCS + Plugin Check), release.yml
```

`includes/` is loaded by the `spl_autoload_register` in the main file (not
Composer). `Plugin::instance()->boot()` runs on `plugins_loaded` and wires
`Admin`.

## Architecture

- **`Plugin`** - singleton. `boot()` wires the admin surface; `activate()` is
  the activation hook (seed options, create tables).
- **`Admin`** - owns the menu (one `Tools ->` screen), the shared layout args,
  asset loading, and the plain-form / `admin-post` handlers. Each screen is a
  `render_*()` method + a view under `includes/views/`; heavier per-screen logic
  goes in its own class.
- **`Settings`** - the one option (`PXPREFIX_OPTION_KEY`), read through typed
  accessors, written through `update()` / `sanitize()`. Never call
  `get_option()` for it directly elsewhere.

## Conventions

- **Namespace** `Perxel\PluginName\`. Hooks, option keys and CSS classes stay
  `pxprefix_` / `pxprefix-`; constants `PXPREFIX_`. Product name is the constant
  `PXPREFIX_NAME` (no rebrand option).
- **Text domain** `perxel-plugin-name` (= the slug). JS i18n via `wp.i18n`
  (`wp_set_script_translations`); script deps include `wp-i18n`.
- **Escape at output.** Views set
  `// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped` because the
  kit escapes structure - every dynamic value is still escaped inline.
- Admin screens render inside `Perxel_UI_Layout::open()/close()` via
  `Admin::screen()`, which falls back to a plain notice if the kit is not
  vendored. Use the kit components (`rows()`, `notice()`, `toggle()`, `code()`,
  `meter()`, `progress_bar()`, `checkbox_group()`, `card()`) rather than
  hand-rolled markup. A bare `<input type="checkbox">` renders as a square box;
  the iOS switch is `Perxel_UI::toggle()` / the `.pxui-toggle` class. Figures
  (counts, totals) are a `rows()` group - label left, value as `content` right,
  `sub` for the qualifier, `tone` for good/warn/bad.
- Forms that can lose unsaved edits carry `data-pxui-dirty-guard` (kit >=
  0.20.0).

## The `vendor/perxel-ui/` kit

Standalone repo [`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui),
vendored via `bin/update-ui.sh <version>` (curl a tagged tarball into
`vendor/perxel-ui/`, Action Scheduler style - no Composer). Committed;
`.gitignore` keeps it out of the general `vendor/` ignore, `.distignore` strips
only its dev-only `showcase/`. Overwriting it can never change plugin behaviour
- the `loader.php` "highest version wins" negotiation picks the newest copy
across every active plugin, and a second copy is inert.

The version passed to `Perxel_UI_Loader::register()` in the main file **and** the
`vendor/perxel-ui/` contents must match the tag you vendored. Update both when
you run `bin/update-ui.sh`.

We host the kit's component showcase as a hidden maintainer-only screen
(`PERXEL_UI_SHOWCASE_HOSTED` + `Admin::can_see_showcase()`), so its own Tools
page is suppressed.

## Before committing

```bash
php -l <changed files>
composer run lint          # phpcs - must stay green
composer run build         # bin/build-zip.sh - installable zip in dist/
```

`phpcs.xml.dist` curates the base `WordPress` standard: a terse-docblock house
style, and `PrefixAllGlobals` is told about both the plugin prefix and the
kit's (`perxel_ui` / `PERXEL_UI` / `Perxel_UI` / `pxui`). CI also runs the
official **Plugin Check** action against the built zip (not the raw checkout).

There are no automated tests and no WP in the lint environment - `phpcs` and
`php -l` verify syntax and style only. Behaviour must be smoke-tested on a real
WordPress site.

## Releasing

1. Bump the version in `perxel-plugin-name.php` (header + `PXPREFIX_VERSION`) and
   `readme.txt` (`Stable tag`); add a changelog entry to both `readme.txt` and
   `CHANGELOG.md`. The tag must equal the `Version:` header or `release.yml`
   fails.
2. Create a GitHub Release with that tag. `release.yml`'s `zip` job attaches
   `perxel-plugin-name.zip`; the `deploy` / `assets` jobs push to WordPress.org
   SVN (need `SVN_USERNAME` / `SVN_PASSWORD`; the SVN repo only exists after the
   first manual review is approved). Delete those two jobs if the plugin is not
   on the .org directory.

Build artifacts (`dist/`) are never committed.
