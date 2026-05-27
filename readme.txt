=== WP Movie Collector ===
Contributors: imonroe
Tags: movies, collection, dvd, barcode
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Catalog and manage your physical movie collection — DVDs, Blu-rays, 4K UHDs, and box sets — with barcode scanning and metadata lookup.

== Description ==

WP Movie Collector is a comprehensive WordPress plugin for movie collectors who want to catalog and organize their physical movie collection. Whether you have DVDs, Blu-rays, 4K UHDs, or box sets, this plugin provides a robust solution to keep track of your collection.

= Features =

* **Movie Management** — Add, edit, and delete movies in your collection.
* **Box Set Support** — Group movies together in box sets and manage their contents.
* **Custom Database** — Dedicated tables for optimal performance with large collections.
* **Metadata Retrieval** — Fetch movie details automatically from TMDb and OMDb.
* **Barcode Scanning** — Quickly add movies by scanning their barcode.
* **Search & Filter** — Find movies by title, director, actor, genre, format, year, and more.
* **Import / Export** — Back up or migrate your collection via CSV and JSON.
* **Responsive Display** — Show your collection on any page or post with a shortcode.

= External Services =

This plugin can optionally connect to third-party APIs to look up movie metadata and barcodes. These services are only contacted when you supply an API key and trigger a lookup (for example, scanning a barcode or searching for a title).

* **TMDb (The Movie Database)** — movie metadata lookup. [Terms](https://www.themoviedb.org/terms-of-use) / [Privacy](https://www.themoviedb.org/privacy-policy).
* **OMDb (Open Movie Database)** — fallback movie metadata lookup. [Site](https://www.omdbapi.com/).
* **Barcode Lookup** — resolves UPC/EAN barcodes to titles. [Site](https://www.barcodelookup.com/).

When a lookup is performed, the relevant search term (title or barcode) is sent to the configured provider along with your API key.

== Installation ==

1. Upload the `wp-movie-collector` folder to the `/wp-content/plugins/` directory, or install the ZIP through **Plugins > Add New > Upload Plugin**.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to the plugin **Settings** page and enter your API keys for TMDb, OMDb, and/or Barcode Lookup (all optional).
4. Add movies via **Movies > Add New Movie**, manually or by scanning a barcode.
5. Add the `[movie_collection]` shortcode to any page or post to display your collection.

== Frequently Asked Questions ==

= Do I need API keys to use the plugin? =

No. You can add and manage your collection entirely by hand. API keys only enable automatic metadata and barcode lookups.

= Where do I get the API keys? =

* TMDb: register at https://www.themoviedb.org/ and request an API key from your account settings.
* OMDb: request a free key at https://www.omdbapi.com/apikey.aspx.
* Barcode Lookup: sign up at https://www.barcodelookup.com/api.

= What barcode scanners are supported? =

Any scanner that acts as a keyboard (HID) input device works, since it simply types the barcode into the focused field. Mobile camera-based scanning is also supported on the add-movie form.

= How do I display my collection? =

Use the `[movie_collection]` shortcode. See the Description and the project README for all supported attributes.

= Will uninstalling remove my data? =

Yes. Deleting the plugin runs `uninstall.php`, which removes the plugin's custom tables and options.

== Screenshots ==

1. Admin dashboard with collection statistics.
2. Add Movie form with barcode scanning.
3. Public collection display with filters.
4. Single movie detail page.
5. Plugin settings page.

== Changelog ==

= 1.3.0 =
* Added a `readme.txt` for the WordPress.org plugin directory.
* Ongoing release-readiness improvements.

= 1.0.0 =
* Initial release: movie and box set management, custom tables, metadata lookup, barcode scanning, search/filter, import/export, and shortcode display.

== Upgrade Notice ==

= 1.3.0 =
Adds WordPress.org directory metadata. No database changes; safe to upgrade.
