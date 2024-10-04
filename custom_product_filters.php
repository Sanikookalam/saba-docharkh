<?php
function custom_product_filters() {
    loggg($_SERVER['REQUEST_URI']);
    // Check if we are on a WooCommerce product archive or taxonomy page
    if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
        return;  // Do not run the code if we are not on these pages
    }

    // Get the current queried object (taxonomy or archive)
    $current_taxonomy = get_queried_object();

    // Get all the product attributes
    $attributes = wc_get_attribute_taxonomies();

    if (empty($attributes)) {
        return;
    }

    // Get the full list of products for the current taxonomy/archive
    $tax_query = $GLOBALS['wp_query']->tax_query->queries;

    // Get filtered products IDs across the current archive (all pages, not just the current page)
    $filtered_ids = wc_get_products(array(
        'limit'    => -1,  // Get all products without pagination
        'return'   => 'ids',
        'paginate' => false,
        'tax_query' => $tax_query,  // Use the same tax query as the current archive
    ));
    loggg("Filtered IDs: " . print_r($filtered_ids, true));
    loggg("tax query is:". print_r($tax_query , true));
    // If no products, don't render the filters
    if (empty($filtered_ids)) {
        return;
    }

    // Initialize an empty array for the tax_query (for filtering)
    $tax_query = array();

    // Process filters from the URL (GET parameters)
    foreach ($attributes as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

        // If the attribute is present in the URL (GET), process it
        if (isset($_GET[$taxonomy])) {
            $terms = explode(',', sanitize_text_field($_GET[$taxonomy]));

            // Add the selected terms to the tax query for filtering
            $tax_query[] = array(
                'taxonomy' => $taxonomy,
                'field'    => 'slug',
                'terms'    => $terms,
                'operator' => 'AND',  // Ensure that all selected terms are used in the filtering
            );
        }
    }
    loggg("tax query is:". print_r($tax_query , true));

    // Fetch the filtered product IDs based on selected filters (if any)
    $filtered_product_ids = $filtered_ids;
//	if (!empty($tax_query)) {
//		$filtered_products = wc_get_products(array(
//			'limit'    => -1,  // Get all filtered products
//			'return'   => 'ids',
//			'paginate' => false,
//			'tax_query' => array_merge(array('relation' => 'AND'), $tax_query),
//		));
//		//loggg($tax_query);
//		//loggg(array_merge(array('relation' => 'AND'), $tax_query));
//
//		// Update the filtered product IDs
//		$filtered_product_ids = $filtered_products;
//	}

    // Now display the filters, ensuring we show all attributes even if one is already filtered
    echo '<div class="custom-product-filters">';
    echo '<h3 class="custom-product-filters-header">فیلترها</h3>';
    echo '<div id="product-filters-accordion">';

    foreach ($attributes as $index => $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

        // Fetch terms for the current attribute that are associated with the filtered product IDs
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
            'object_ids' => $filtered_ids,  // Show all terms associated with products in this taxonomy
        ));

        if (!empty($terms) && !is_wp_error($terms)) {
            $accordion_id = 'filter-group-' . $index;
            $collapse_id  = 'collapse-' . $index;

            echo '<div class="accordion-item ' . $attribute->attribute_name . '">';
            echo '<div class="accordion-header" id="' . $accordion_id . '">';
            echo '<h4 class="mb-0">';
            echo '<a href="#" class="accordion-toggle" data-target="#' . $collapse_id . '">';
            echo esc_html($attribute->attribute_label);
            echo '</a>';
            echo '</h4>';
            echo '</div>';

            echo '<div id="' . $collapse_id . '" class="accordion-content">';
            echo '<div class="accordion-body">';
            echo '<ul>';

            // Loop through each term and generate the checkbox filter
            foreach ($terms as $term) {
                $checked = isset($_GET[$taxonomy]) && in_array($term->slug, explode(',', $_GET[$taxonomy])) ? 'checked' : '';
                echo '<li><div class="checkbox-wrapper-4">';
                echo '<input type="checkbox" id="input-' . $index . '-' . $term->term_id . '" class="inp-cbx product-attribute-filter" data-attribute="' . esc_attr($taxonomy) . '" value="' . esc_attr($term->slug) . '" ' . $checked . '>';
                echo '<label class="cbx" for="input-' . $index . '-' . $term->term_id . '">
                    <span>
                        <svg width="12px" height="10px">
                            <use xlink:href="#check-4"></use>
                        </svg>
                    </span>
                    <span>' . esc_html($term->name) . '</span>
                    </label>
                    <svg class="inline-svg">
                        <symbol id="check-4" viewbox="0 0 12 10">
                            <polyline points="1.5 6 4.5 9 10.5 1"></polyline>
                        </symbol>
                    </svg>
                </div>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
}

// Hook the custom product filters function to WooCommerce
add_action('woocommerce_before_shop_loop', 'custom_product_filters', 20);


// Shortcode (if you want to add filters via shortcode)
add_shortcode('custom_product_filters', 'custom_product_filters');

// Enqueue custom JS for filters
function custom_filter_scripts()
{
    wp_enqueue_script('custom-filter-script', get_template_directory_uri() . '/assets/js/custom-filters.js', array('jquery'), null, true);
}

add_action('wp_enqueue_scripts', 'custom_filter_scripts');

// Adjust WooCommerce product query based on selected filters
function custom_woocommerce_product_query($query)
{
    if (!is_admin() && $query->is_main_query() && (is_product_category() || is_product_taxonomy())) {

        // Get all registered attributes
        $attribute_taxonomies = wc_get_attribute_taxonomies();

        if (!$attribute_taxonomies) {
            return;
        }

        // Initialize tax query
        $tax_query = array();

        // Loop through each attribute
        foreach ($attribute_taxonomies as $attribute) {
            $taxonomy = 'pa_' . $attribute->attribute_name;

            // Check if the attribute is present in the URL
            if (isset($_GET[$taxonomy])) {
                $terms = explode(',', sanitize_text_field($_GET[$taxonomy]));

                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $terms,
                );
            }
        }

        // Add tax_query to the query
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }
    }
}

add_action('pre_get_posts', 'custom_woocommerce_product_query', 9999999999999999);
