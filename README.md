# wp-plugin-starter

A GitHub **template repository** for a Perxel WordPress plugin. It ships the
house layout every Perxel plugin shares - a namespaced, autoloaded codebase, the
[`perxel/wp-plugin-ui`](https://github.com/perxel/wp-plugin-ui) admin kit wired
in, PHPCS + WordPress Plugin Check in CI, and a release workflow that builds the
installable zip and deploys to WordPress.org.

Out of the box it is a working plugin: activate it and **Tools -> Perxel Plugin
Name** shows a Settings screen (a text field + a toggle, saved through
`admin-post`) rendered in the shared UI layout, plus a hidden maintainer-only
"Perxel UI" component showcase.

## Creating a new plugin from it

### 1. Use this template

On GitHub: **Use this template -> Create a new repository**, named
`wp-<something>` under `perxel/`. Clone it.

### 2. Replace the template tokens

The template has no build step - personalising is a find-and-replace across the
tree. Replace these **case-sensitively, top to bottom** (order matters - the
compound tokens must go before their parts):

| Find | Replace with | Example |
|---|---|---|
| `Perxel_PluginName` | `@package` tag (underscored) | `Perxel_Seo_Helper` |
| `Perxel Plugin Name` | Display name | `Perxel SEO Helper` |
| `Perxel\PluginName` | PHP namespace | `Perxel\SeoHelper` |
| `PluginName` | Namespace segment (leftover uses) | `SeoHelper` |
| `perxel-plugin-name` | Slug = text domain = .org slug | `perxel-seo-helper` |
| `wp-plugin-name` | GitHub repo name | `wp-seo-helper` |
| `PXPREFIX` | Uppercase constant / hook prefix | `PXSH` |
| `pxprefix` | Lowercase hook / option / CSS prefix | `pxsh` |

Then:

```sh
# Rename the main file to match the slug
git mv perxel-plugin-name.php perxel-seo-helper.php
git mv languages/perxel-plugin-name.pot languages/perxel-seo-helper.pot
```

One-liner for the text replacements (macOS `sed`; drop the `''` on Linux):

```sh
git grep -lZ -e 'Perxel_PluginName' -e 'Perxel Plugin Name' -e 'PluginName' \
  -e 'perxel-plugin-name' -e 'wp-plugin-name' -e 'PXPREFIX' -e 'pxprefix' \
| xargs -0 sed -i '' \
  -e 's/Perxel_PluginName/Perxel_Seo_Helper/g' \
  -e 's/Perxel Plugin Name/Perxel SEO Helper/g' \
  -e 's/Perxel\\PluginName/Perxel\\SeoHelper/g' \
  -e 's/PluginName/SeoHelper/g' \
  -e 's/perxel-plugin-name/perxel-seo-helper/g' \
  -e 's/wp-plugin-name/wp-seo-helper/g' \
  -e 's/PXPREFIX/PXSH/g' \
  -e 's/pxprefix/pxsh/g'
```

### 3. Fill in the free text

Search for **`A short description of what this plugin does.`** (main file header,
`composer.json`, `readme.txt`, this README) and write the real one-liner. Then
work through `readme.txt` (tags, `Tested up to`, Description, FAQ, Screenshots)
and replace this README with the plugin's own.

### 4. Vendor the UI kit

```sh
bin/update-ui.sh 0.21.0        # newest tag at github.com/perxel/wp-plugin-ui
```

Set that same version in the main file's `Perxel_UI_Loader::register( '0.21.0',
... )` call - it is what the "highest version wins" loader compares. (The plugin
still activates without this step - it just shows a "UI library could not be
loaded" notice until the kit is vendored.)

### 5. Wire up the repo

- `composer install` - pulls PHPCS + the WordPress standard.
- `php -l perxel-seo-helper.php && composer run lint` - both must be green.
- `composer run build` - produces `dist/perxel-seo-helper.zip`.
- Reserve the slug at <https://wordpress.org/plugins/developers/add/> (first
  submission is a manual review).
- Add repo secrets **`SVN_USERNAME`** and **`SVN_PASSWORD`** (your WordPress.org
  account) so `release.yml` can deploy. Not on .org? Delete the `deploy` /
  `assets` jobs from `release.yml` and the External services / Screenshots
  scaffolding you do not need.
- Delete `.wordpress-org/README.md` once you have added the real listing assets.

### 6. Delete this section

Once the new repo builds, remove "Creating a new plugin from it" from its
README.

## What's inside

See [CLAUDE.md](CLAUDE.md) for the full architecture, conventions, and release
process - it is written to travel with the generated plugin.

## Updating the template itself

Improvements to the shared layout (`includes/Admin.php`, `phpcs.xml.dist`, the
workflows, `bin/`) land here first. Existing plugins pull UI changes through
`bin/update-ui.sh`; they do **not** auto-sync template changes - port those by
hand when they matter.
