# Changelog

All notable changes to this plugin are documented here. This file mirrors the
`== Changelog ==` section of `readme.txt` (keep the two in sync).

## 0.0.2

* Admin output is escaped late: `Admin::kit()` echoes through `wp_kses()` with the shared UI kit's `Perxel_UI::allowed_html()` (kit 0.23.0+). `bin/check-suppressions.sh` also fails on any `EscapeOutput` suppression.

## 0.0.1

* First release.
