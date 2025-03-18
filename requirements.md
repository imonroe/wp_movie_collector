WP Movie Collector - WordPress Plugin

## Overview

The **WP Movie Collector** is a WordPress plugin designed to help users catalog and manage their physical movie collection, including DVDs, Blu-rays, collector’s editions, box sets, and TV series. The plugin will provide a modern user interface for adding, searching, and displaying movie details, with support for barcode scanning and metadata retrieval from external APIs.

## Features

### 1. **Movie Data Input**
- **Manual Entry**: Users can manually input movie details through an easy-to-use WordPress admin interface.
- **Barcode Scanning**: Users can scan barcodes using a USB barcode scanner (keyboard-emulating device).
- **API Integration**: The plugin will fetch metadata (title, director, studio, cast, description, release date, special features, cover image, etc.) from one or more movie databases (e.g., TMDb, OMDb, or similar).

### 2. **Movie Collection Management**
- **Custom Database Tables**: Data will be stored in dedicated tables for better performance and flexibility.
- **Custom Post Types**: 
  - `movie` – Represents an individual movie (e.g., *The Thing* (1982)).
  - `box_set` – Represents a collection of movies (e.g., *A Nightmare on Elm Street Collection*).
- **Relationships Between Items**:
  - Individual movies (`movie`) can be linked to a box set (`box_set`).
  - Compilation DVDs can contain multiple movies.
  - A TV series box set (`box_set`) can have multiple seasons or episodes.

### 3. **Search, Sort, and Filter**
- **Searchable Collection**: Users can search movies and box sets by title, year, genre, director, or actor.
- **Sorting Options**: Sort by title, release date, format (DVD/Blu-ray), or acquisition date.
- **Filters**: Users can filter by format, genre, studio, and more.
- **Hierarchy Display**: Box sets show all associated movies inside them.

### 4. **Movie & Box Set Detail Pages**
- **Single Movie View**: Each movie will have a dedicated page with:
  - Title, cover image, and basic information.
  - Director, studio, cast, genre, and special features.
  - Format, region code, and release date.
  - API-fetched metadata if available.
  - Notes or personal collection details (e.g., purchase date, location).
  - **If part of a box set**, it links back to that box set.

- **Box Set View**: Each box set will have a detail page showing:
  - Title, cover image, and general information.
  - A list of all movies included in the box set.
  - Format, region code, and number of discs.

### 5. **User Interface**
- **Admin Panel**: A clean, intuitive dashboard for managing the collection.
- **Front-End Display**: A responsive page listing all movies and box sets with search and filtering options.
- **Bulk Import**: Ability to import/export collection data in CSV format.

---

## Technical Specifications

### 1. **Custom Database Schema**
#### `wp_movie_collection`
Stores individual movie details.

| Column            | Type          | Description |
|------------------|--------------|-------------|
| `id`            | INT (Primary) | Unique identifier |
| `title`         | VARCHAR       | Movie title |
| `release_year`  | YEAR          | Year of release |
| `format`        | ENUM          | DVD, Blu-ray, etc. |
| `region_code`   | VARCHAR       | Region code (e.g., R1, R2) |
| `barcode`       | VARCHAR       | Unique barcode identifier |
| `director`      | VARCHAR       | Movie director |
| `studio`        | VARCHAR       | Production studio |
| `actors`        | TEXT          | List of main actors |
| `genre`         | VARCHAR       | Genre(s) |
| `special_features` | TEXT        | Special features included |
| `cover_image_url` | TEXT        | URL to movie cover image |
| `description`   | TEXT          | Movie description |
| `acquisition_date` | DATE        | When the user acquired it |
| `box_set_id`    | INT (Foreign) | Links to `wp_movie_box_sets.id` if part of a set |
| `api_source`    | VARCHAR       | Source of metadata (e.g., TMDb) |
| `custom_notes`  | TEXT          | User notes |
| `created_at`    | DATETIME      | Timestamp of record creation |
| `updated_at`    | DATETIME      | Timestamp of last update |

#### `wp_movie_box_sets`
Stores information about box sets and compilation DVDs.

| Column            | Type          | Description |
|------------------|--------------|-------------|
| `id`            | INT (Primary) | Unique identifier |
| `title`         | VARCHAR       | Box set title |
| `release_year`  | YEAR          | Year of release |
| `format`        | ENUM          | DVD, Blu-ray, etc. |
| `region_code`   | VARCHAR       | Region code |
| `barcode`       | VARCHAR       | Unique barcode identifier |
| `cover_image_url` | TEXT        | URL to box set cover image |
| `description`   | TEXT          | Summary of the box set |
| `acquisition_date` | DATE        | When the user acquired it |
| `special_features` | TEXT        | Special features included |
| `api_source`    | VARCHAR       | Source of metadata |
| `custom_notes`  | TEXT          | User notes |
| `created_at`    | DATETIME      | Timestamp of record creation |
| `updated_at`    | DATETIME      | Timestamp of last update |

#### `wp_movie_box_set_relationships`
Links movies to their respective box sets.

| Column     | Type  | Description |
|-----------|------|-------------|
| `id`      | INT (Primary) | Unique relationship ID |
| `movie_id` | INT (Foreign) | Links to `wp_movie_collection.id` |
| `box_set_id` | INT (Foreign) | Links to `wp_movie_box_sets.id` |

### 2. **Barcode Scanner Support**
- Accepts input from USB barcode scanners (acts as a keyboard).
- Automatically triggers metadata lookup when a barcode is scanned.
- Detects whether the scanned item is a **movie** or a **box set**.

### 3. **API Integration**
- API calls to services like:
  - [TMDb](https://www.themoviedb.org/) (Preferred)
  - [OMDb](https://www.omdbapi.com/) (Backup)
- **Multi-Layer Lookup**:
  - If a barcode belongs to a **box set**, fetch its metadata and all associated movies.
  - If a barcode belongs to a **single movie**, fetch and store its details.

### 4. **WordPress Integration**
- **Custom Post Types**:
  - `movie`
  - `box_set`
- **Custom Taxonomies**:
  - `genre`, `director`, `studio`, `actors`
- **Custom REST API Endpoints**:
  - `/wp-json/movie-collection/v1/movies`
  - `/wp-json/movie-collection/v1/box-sets`

### 5. **Front-End UI**
- **Shortcode for Displaying Collection**: `[movie_collection]`
- **Detail Page Templates**:
  - Individual movie page.
  - Box set page (with linked movies).
- **AJAX-Powered Search**: Fast filtering and sorting.


## Future Enhancements
- **Wishlist Support**: Track movies the user wants to add.
- **Loan Tracking**: Track borrowed/lent movies.
- **Mobile App Sync**: Integration with mobile apps for easier scanning.


