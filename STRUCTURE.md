# WP Movie Collector - Plugin Structure

## Core Files

- `wp-movie-collector.php` - Main plugin file, entry point
- `uninstall.php` - Cleanup when plugin is uninstalled

## Includes Directory

Core plugin classes and functionality:

- `class-wp-movie-collector.php` - Main plugin class
- `class-wp-movie-collector-loader.php` - Action/filter hook loader
- `class-wp-movie-collector-activator.php` - Plugin activation logic
- `class-wp-movie-collector-deactivator.php` - Plugin deactivation logic
- `class-wp-movie-collector-post-types.php` - Custom post types and taxonomies
- `class-wp-movie-collector-api.php` - API integration for metadata retrieval

### DB Subdirectory

- `class-wp-movie-collector-db.php` - Database operations class

## Admin Directory

Admin-facing functionality:

- `class-wp-movie-collector-admin.php` - Admin class, handles all admin hooks
- `/css/wp-movie-collector-admin.css` - Admin styles
- `/js/wp-movie-collector-admin.js` - Admin JavaScript

### Admin Partials

Template files for admin pages:

- `wp-movie-collector-admin-dashboard.php` - Main dashboard template
- `wp-movie-collector-admin-add-movie.php` - Add/edit movie form
- `wp-movie-collector-admin-add-box-set.php` - Add/edit box set form
- `wp-movie-collector-admin-settings.php` - Plugin settings page
- `wp-movie-collector-admin-import-export.php` - Import/export functionality

## Public Directory

Public-facing functionality:

- `class-wp-movie-collector-public.php` - Public class, handles all public hooks
- `/css/wp-movie-collector-public.css` - Public styles
- `/js/wp-movie-collector-public.js` - Public JavaScript

### Public Partials

Template files for public display:

- `wp-movie-collector-public-display.php` - Collection display template
- `wp-movie-collector-public-movie.php` - Single movie template
- `wp-movie-collector-public-box-set.php` - Single box set template
- `wp-movie-collector-public-movie-item.php` - Grid item for a movie
- `wp-movie-collector-public-box-set-item.php` - Grid item for a box set

## Other Files

- `README.md` - Plugin documentation
- `CLAUDE.md` - Development guide and commands
- `TODO.md` - List of completed and missing components
- `STRUCTURE.md` - This file, explaining the plugin structure
- `requirements.md` - Original plugin requirements specification

## Database Tables

- `wp_movie_collection` - Stores individual movie details
- `wp_movie_box_sets` - Stores box set information
- `wp_movie_box_set_relationships` - Links movies to box sets

## Integration Points

1. **WordPress Hooks**
   - Uses WordPress actions and filters via the loader class
   - Registers custom post types and taxonomies
   - Integrates with admin menu

2. **External APIs**
   - TMDb (The Movie Database) for primary metadata
   - OMDb (Open Movie Database) as a fallback

3. **Shortcodes**
   - `[movie_collection]` - Primary shortcode for displaying collection

4. **AJAX Endpoints**
   - Barcode lookup
   - Movie search
   - Movie details retrieval
   - Load more items

## Plugin Flow

1. Plugin is activated:
   - Database tables are created
   - Default options are set

2. Admin loads plugin:
   - Admin menu is added
   - Settings are registered
   - Admin assets are enqueued

3. Public page with shortcode loads:
   - Shortcode is processed
   - Collection is displayed based on parameters
   - Assets are enqueued for public display

4. User interacts with collection:
   - Filters trigger page reloads with GET parameters
   - AJAX is used for "load more" functionality
   - Movie/box set detail views use clean URLs with query parameters