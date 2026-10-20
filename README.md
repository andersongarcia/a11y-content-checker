# A11y Content Checker

[Português](README.pt-BR.md)

A WordPress plugin that checks the accessibility of content **while it is written** and shows authors what to fix. Its rules are mapped to WCAG 2.2 and to ABNT NBR 17225:2025, the Brazilian web accessibility standard.

> Status: **early prototype (0.1.0)**. Tested on a local multisite with WordPress 7.1, PHP 8.2 and Elementor 4.3.

## Why

Automated audits of a theme usually come back clean after the first round of fixes. What degrades over time is the **content**, published every day by different people. Auditing page by page does not scale; checking at publishing time does.

Editoria11y and Sa11y already offer front-end checks for logged-in users and are worth a look. This plugin focuses on what they leave out:

1. **Content rules** tied to WCAG success criteria and NBR 17225 items, such as links to files without format, posters published as images and all-caps text.
2. **A stored result per post**, so you can list the issues of the whole site and report on a whole multisite network from the command line.
3. **A link to a human checklist**, which covers what a machine cannot decide.

## How it works

```
Author saves ──► Checker (PHP, content rules) ──► post meta
                                                     │
     ┌─────────────────────────┬─────────────────────┤
     ▼                         ▼                     ▼
"Accessibility" column   Tools › Accessibility   wp a11ycc check
in post lists            (site-wide report)      (batch, CSV, whole network)

Author views the page ──► toolbar › "Check accessibility"
                          └─ axe-core on the rendered page + stored issues
```

| Layer | What it checks | Why |
|---|---|---|
| **Server (PHP)** | Content rules on the rendered HTML of the post | Runs on save and in batch, without a browser. This is what scales. |
| **Browser (axe-core)** | WCAG A and AA rules on the real page, including contrast | Only a browser computes styles. Works with any editor, including Elementor. |

Design decisions:

- **It informs; it does not block.** Blocking publication breeds workarounds. An optional block for errors may come later.
- **Nothing is loaded from third parties.** axe-core is bundled in `vendor/`, with its pt-BR and Spanish translations.
- **Content only.** Theme problems (landmarks, menus, header contrast) belong to a per-template audit.

## Content rules

| Rule | Level | WCAG |
|---|---|---|
| `img-missing-alt`: image without an alt attribute | error | 1.1.1 |
| `alt-is-filename`: alt such as "IMG_2034.jpg" or "photo" | error | 1.1.1 |
| `link-no-name`: link without text (e.g. image with empty alt inside a link) | error | 2.4.4 |
| `link-generic`: "click here", "read more" (English and Portuguese) | warning | 2.4.4 |
| `link-file-no-info`: link to a PDF or DOCX without the format in its text | warning | 2.4.4 |
| `heading-skipped`: H2 followed by H4 | warning | 1.3.1 |
| `heading-multi-h1`: more than one H1 | warning | 1.3.1 |
| `heading-fake`: bold paragraph used as a heading | warning | 1.3.1 |
| `text-all-caps`: long passage in capital letters | warning | 3.1.5 |
| `table-no-header` | error | 1.3.1 |
| `iframe-no-title` | error | 4.1.2 |
| `video-captions`: embedded video, reminder to check captions | warning | 1.2.2 |
| `image-little-text`: image with fewer than 30 words of text (a poster?) | warning | 1.1.1 |

## Extending

| Hook | Use |
|---|---|
| `a11ycc_rules` | Change levels and messages, or register new rules |
| `a11ycc_issues` | Add issues from your own checks |
| `a11ycc_post_types` | Post types to check (default: `post`, `page`) |
| `a11ycc_first_heading_level` | Heading level the content starts after (default: 1) |
| `a11ycc_axe_scope` | CSS selector axe-core checks (default: `main`) |
| `a11ycc_checklist_url` | Link to your publishing checklist |

```php
add_filter( 'a11ycc_checklist_url', fn() => 'https://example.org/publishing-checklist/' );
```

## Command line

```sh
wp a11ycc check
wp a11ycc check --post_type=page --format=csv > pages.csv

# Every site of a multisite network
wp site list --field=url | xargs -I{} wp --url={} a11ycc check --format=csv
```

## Development

The `dev/` folder has a local WordPress multisite in Docker (WordPress on PHP 8.2, MariaDB 11.4, WP-CLI) with the plugin mounted and a `demo` site with test content.

1. `npm install`: installs axe-core and copies it to `vendor/`.
2. Copy `dev/.env.example` to `dev/.env` and set the passwords.
3. Optional: unzip Elementor from wordpress.org into `dev/plugins/elementor/`.
4. `npm run dev:up`, then `npm run dev:install`.
5. Open http://localhost:8888/demo/wp-admin/ and log in with the user from `.env`.
6. `npm run check` checks the content; `npm run i18n` regenerates the translation files.

Requirements: WordPress 6.0+, PHP 7.4+ with the **mbstring** extension.

## Roadmap

- [ ] Fill in the NBR 17225 item of each rule.
- [ ] Show checklist questions the machine cannot answer in the editor panel.
- [ ] Network Admin page summarising every site.
- [ ] Measure false positives on a sample of real posts.
- [ ] Optional blocking of errors in the block editor.
- [ ] Publish on the WordPress.org plugin directory.

## License

GPL-2.0-or-later. Bundles [axe-core](https://github.com/dequelabs/axe-core) (MPL-2.0).

Built by [Anderson Canale Garcia](https://github.com/andersongarcia) as part of his research on digital accessibility.
