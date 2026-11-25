# Secure Code Embeds

Contributors: alleyinteractive

Tags: alleyinteractive, wp-secure-code-embeds

Stable tag: 0.0.0

Requires at least: 6.3

Tested up to: 6.7

Requires PHP: 8.4

License: GPL v2 or later

[![Testing Suite](https://github.com/alleyinteractive/wp-secure-code-embeds/actions/workflows/all-pr-tests.yml/badge.svg?branch=develop)](https://github.com/alleyinteractive/wp-secure-code-embeds/actions/workflows/all-pr-tests.yml)

A WordPress plugin to allow authors and editors to securely embed admin-approved code via the block editor.

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-secure-code-embeds
```

## Usage

Secure Code Embeds lets a site publish custom HTML, CSS, and JavaScript, such as
contest pages, promotional landing pages, and embedded widgets, without a
development cycle and without opening raw-HTML authoring to everyone who can
edit pages.

It separates two roles:

- **Authoring** code is limited to site administrators (and super admins on
  multisite) in a private "Code Embeds" content type.
- **Placing** that code on a page is open to anyone who can edit pages, through
  the Code Embed block.

### Authoring a code embed

Administrators find **Code Embeds** in the admin menu. Each entry has a title
and one locked block with two fields:

- **Head content** is output in the document `<head>` of every page that
  includes the entry. Use it for CSS, web fonts, and scripts that must run
  before the page body.
- **Body content** is output wherever the Code Embed block is placed.

Both fields are saved to post meta (`secure_code_embed_head` and
`secure_code_embed_body`) and are revisioned with the entry, so changes can be
compared and rolled back from the revisions screen.

> [!IMPORTANT]
> Code is stored and output exactly as written. It is intentionally not run
> through `unfiltered_html` checks, sanitized, or escaped. Users with the
> authoring capability are trusted to review code before saving it. Do not add
> sanitization or escaping to these fields.

Entries are private: they have no front-end URL, archive, or search results,
and they belong to a single site on multisite.

### Placing a code embed

Insert the **Code Embed** block on a page and select an entry. The block can be
the only content on a page or sit alongside other blocks, can be nested in
groups, columns, and synced patterns, and can be used more than once on the
same page.

In the editor, the block shows the entry title, a link to edit the entry (for
administrators), and a note that the code renders on the front end and in
previews. If the entry is deleted, trashed, or unpublished, the block shows a
warning in the editor and renders nothing on the front end.

On the front end:

- Before the `<head>` is output, the page's blocks are scanned, including nested
  blocks and synced patterns, and the head content of every referenced entry is
  added to `<head>`. An entry used more than once has its head content added
  only once.
- Body content renders, unescaped, at each block's position.
- Nothing is output on password-protected pages until the password is entered,
  or in feeds.
- The Code Embed block works on pages using any template.

### Permissions

| Action | Who |
| --- | --- |
| Create, edit, or delete entries | Users with `manage_secure_code_embeds` |
| Select entries in the block, read published entries over REST | Users who can edit pages (or any allowed post type) |
| Read entries over REST | No one else, including logged-out users |

`manage_secure_code_embeds` is granted to every user who has `manage_options`
(site administrators). Super admins have it on every site. To give the
capability to another role, or take it away from administrators, set it
explicitly on the role:

```php
get_role( 'administrator' )->add_cap( 'manage_secure_code_embeds', false );
```

Head and body code are only returned by the REST API in the `edit` context,
which requires the authoring capability.

### Filters

`wp_secure_code_embeds_allowed_post_types` sets the post types where the Code
Embed block can be inserted and rendered, and where head content is injected.
It defaults to `[ 'page' ]`, which keeps raw code out of feeds and syndicated
content.

```php
add_filter(
	'wp_secure_code_embeds_allowed_post_types',
	fn ( array $post_types ) => [ ...$post_types, 'landing_page' ],
);
```

## Development

To setup a WordPress installation and run the plugin in a local environment, you
can use `wp-env` via the `composer dev` command:

```sh
npm install
composer dev
```

The command will start a local WordPress environment with the plugin activated
while also running the front-end assets build process. You can also run `npm run
start` to start the front-end assets build process separately. The front-end
assets will be compiled into the `build` directory and will be enqueued
automatically by the plugin.

## The `entries` directory and entry points

All directories created in the `entries` directory can serve as entry points and will be compiled with [@wordpress/scripts](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/README.md#scripts) into the `build` directory with an accompanied `index.asset.php` asset map.

### Scaffolding an entry point

To generate a new entry point, run the following command:

```sh
npm run create-entry
```

To generate a new slotfill, run the following command:

```sh
npm run create-slotfill
```

The command will prompt the user through several options for creating an entry or slotfill. The entries are scaffolded with the `@alleyinteractive/create-entry` script. Run the help command to see all the options:

```sh
npx @alleyinteractive/create-entry --help
```
[Visit the package README](https://www.npmjs.com/package/@alleyinteractive/create-entry) for more information.

### Enqueuing Entry Points

You can also include an `index.php` file in the entry point directory for enqueueing or registering a script. This file will then be moved to the build directory and will be auto-loaded with the `load_scripts()` function in the `functions.php` file. Alternatively, if a script is to be enqueued elsewhere there are helper functions in the `src/assets.php` file for getting the assets.

### Scaffold a dynamic block with `create-block`

Use the `create-block` command to create custom blocks with [@alleyinteractive/create-block](https://github.com/alleyinteractive/alley-scripts/tree/main/packages/create-block) script and follow the prompts to generate all the block assets in the `blocks/` directory.
Block registration, script creation, etc will be scaffolded from the `create-block` script. Run `npm run build` to compile and build the custom block. Blocks are enqueued using the `load_scripts()` function in `src/assets.php`.

### Updating WordPress Dependencies

Update the [WordPress dependency packages](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#packages-update) used in the project to their latest version.

To update `@wordpress` dependencies to their latest version use the packages-update command:

```sh
npx wp-scripts packages-update
```

This script provides the following custom options:

-   `--dist-tag` – allows specifying a custom dist-tag when updating npm packages. Defaults to `latest`. This is especially useful when using [`@wordpress/dependency-extraction-webpack-plugin`](https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin). It lets installing the npm dependencies at versions used by the given WordPress major version for local testing, etc. Example:

```sh
npx wp-scripts packages-update --dist-tag=wp-WPVERSION`
```

Where `WPVERSION` is the version of WordPress you are targeting. The version
must include both the major and minor version (e.g., `7.0`). For example:

```sh
npx wp-scripts packages-update --dist-tag=wp-7.0`
```

## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.
Unit testing code is written in PSR-4 format and can be found in the `tests`
directory. Run `npm run build` before the PHP tests so the blocks are
registered; block rendering tests are skipped otherwise.

## Releasing the Plugin

The plugin uses
[action-release](https://github.com/alleyinteractive/action-release) via a
[built release workflow](./.github/workflows/built-release.yml) to compile and
tag releases. Whenever a new version is detected in the root plugin's headers in
the `wp-secure-code-embeds.php` file or in the `composer.json` file, the workflow will
automatically build the plugin and tag it with a new version. The built tag will
contain all the required front-end assets the plugin may require. This works
well for publishing to WordPress.org or for submodule-ing.

When you are ready to release a new version of the plugin, you can run
`npm run release`/`composer release` to start the process of setting up a new
release. If you want to do this manually you can follow these steps:

1. Change the `Version` in the `wp-secure-code-embeds.php` file to a new higher-level version.

	```diff
	- * Version: 0.0.0
	+ * Version: 0.0.1
	```

	**✨ `npm run release` will do this for you automatically.**

2. Commit your changes and push to the repository.
3. Check the actions tab in the repository to see the progress of the release.
   The action will automatically create a new tag and release for the plugin.
   You are done!

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.com/careers/).

- [Alley](https://github.com/alleyinteractive)
- [All Contributors](../../contributors)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.