#  WordPress Advanced Search

This component enhances the default WordPress search functionality to include custom fields (post meta) and taxonomy terms in the search results.

It is designed as a theme-specific module, not a plugin. It works by modifying the main search query's SQL to create a comprehensive `OR` search across `post_title`, `post_content`, specified `meta_keys`, and `taxonomy_terms`.

## Features

* **True "OR" Search:** Finds results if the search term matches the title, content, a custom field, *or* a taxonomy term.
* **Performance-Conscious:** Does not search all meta keys. You must explicitly define which meta keys are searchable, preventing slow queries.
* **Configurable:** Easily define which post types, meta keys, and taxonomies to include via WordPress filters.
* **Lightweight:** No admin settings pages or overhead. Just pure, functional code.

---

## 1. Installation

1.  Place the entire `advanced-search-module` folder inside your theme's directory.
    * Example: `wp-content/themes/your-theme/wp-advanced-search/`
2.  Open your theme's `functions.php` file.
3.  Add the following line to include the module:

    ```php
    require_once get_template_directory() . '/wp-advanced-search/advanced-search-module.php';
    ```

---

## 2. Configuration (Required)

By default, this module does nothing until you configure it. You **must** specify which meta keys to search for security and performance.

Add these configurations to your theme's `functions.php` file (or a dedicated configuration file).

### Example: Making ACF Fields or 'sku' Searchable

This is the most critical step. Use the `hussainas_searchable_meta_keys` filter to add your custom field keys.

```php
/**
 * Define which meta keys to include in the advanced search.
 *
 * @param array $meta_keys The default empty array of keys.
 * @return array The modified array of keys.
 */
function my_theme_add_searchable_meta_keys( $meta_keys ) {
    // Add your custom field keys here
    $meta_keys[] = 'sku';                // e.g., for WooCommerce product SKU
    $meta_keys[] = 'product_subtitle';   // e.g., a custom ACF field
    $meta_keys[] = 'author_name';        // e.g., another custom field

    return $meta_keys;
}
add_filter( 'hussainas_searchable_meta_keys', 'my_theme_add_searchable_meta_keys' );
```

---

## 3. Advanced Configuration (Optional)

You can also control which post types and taxonomies are searched.

### Customizing Searchable Post Types

By default, the module searches `post` and `page`. You can add your custom post types (e.g., `product`) like this:

```php
/**
 * Define which post types to include in the advanced search.
 *
 * @param array $post_types The default array (['post', 'page']).
 * @return array The modified array of post types.
 */
function my_theme_add_searchable_post_types( $post_types ) {
    // Add your custom post types
    $post_types[] = 'product';
    $post_types[] = 'portfolio';

    // Or, to search *only* products:
    // $post_types = ['product'];

    return $post_types;
}
add_filter( 'hussainas_searchable_post_types', 'my_theme_add_searchable_post_types' );
```

### Customizing Searchable Taxonomies

The module automatically searches all taxonomies associated with your defined post types (e.g., `category`, `post_tag`, `product_cat`). If you need to *exclude* a specific taxonomy, you can do so by modifying the list.

```php
/**
 * Modify the list of searchable taxonomies.
 *
 * @param array $taxonomies The auto-generated list of taxonomies.
 * @return array The modified array.
 */
function my_theme_modify_searchable_taxonomies( $taxonomies ) {
    
    // Example: To remove 'product_tag' from the search
    if ( ( $key = array_search( 'product_tag', $taxonomies ) ) !== false ) {
        unset( $taxonomies[$key] );
    }

    return $taxonomies;
}
add_filter( 'hussainas_searchable_taxonomies', 'my_theme_modify_searchable_taxonomies' );


