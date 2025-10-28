<?php
function my_theme_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_add_to_cart', 10);


// Conditional function that checks if a product is in cart and return the correct button text
function change_button_text( $product_id, $button_text ) {
    foreach( WC()->cart->get_cart() as $item ) {
        if( $product_id === $item['product_id'] ) {
            return __('Toegevoegd aan winkelwagen', 'woocommerce');
        }
    }
    return $button_text;
}

// Archive pages: For simple products (ajax add to cart button)
add_filter( 'woocommerce_product_add_to_cart_text', 'change_ajax_add_to_cart_button_text', 10, 2 );
function change_ajax_add_to_cart_button_text( $button_text, $product ) {
    if ( $product->is_type('simple') ) {
        $button_text = change_button_text( $product->get_id(), $button_text );
    }
    return $button_text;
}

// Single product pages: Simple and external products
add_filter( 'woocommerce_product_single_add_to_cart_text', 'change_single_add_to_cart_button_text', 10, 2 );
function change_single_add_to_cart_button_text( $button_text, $product ) {
    if (  ! $product->is_type('variable') ) {
        $button_text = change_button_text( $product->get_id(), $button_text );
    }
    return $button_text;
}

// Single product pages: Variable product and its variations
add_action( 'woocommerce_after_variations_form', 'action_after_variations_form_callback' );
function action_after_variations_form_callback() {
    global $product;

    // Get the produc variation Ids for the variable product
    $children_ids = $product->get_visible_children();

    $ids_in_cart  = [];

    // Loop through cart items
    foreach( WC()->cart->get_cart() as $item ) {
        if( in_array( $item['variation_id'], $children_ids ) ) {
            $ids_in_cart[] = $item['variation_id'];
        }
    }
    ?>
    <script type="text/javascript">
    jQuery(function($){
        var b = 'button.single_add_to_cart_button',
            t = '<?php echo $product->single_add_to_cart_text(); ?>';

        $('form.variations_form').on('show_variation hide_variation found_variation', function(){
            $.each(<?php echo json_encode($ids_in_cart); ?>, function(j, v){
                var i = $('input[name="variation_id"]').val();
                if(v == i && i != 0 ) {
                    $(b).html('<?php _e('Toegevoegd aan winkelwagen', 'woocommerce'); ?>');
                    return false;
                } else {
                    $(b).html(t);
                }
            });
        });
    });
    </script>
    <?php
}

add_action( 'pre_get_posts', 'custom_product_search_include_taxonomy', 10, 1 );
function custom_product_search_include_taxonomy( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->is_search() && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'product' && ! empty( $_GET['s'] ) ) {
        $search = sanitize_text_field( wp_unslash( $_GET['s'] ) );

        $taxonomies = array( 'product_cat', 'product_tag' );
        $found = array();

        foreach ( $taxonomies as $tax ) {
            $term = get_term_by( 'slug', sanitize_title( $search ), $tax );
            if ( $term ) {
                $found[ $tax ][] = (int) $term->term_id;
                continue;
            }

            $term = get_term_by( 'name', $search, $tax );
            if ( $term ) {
                $found[ $tax ][] = (int) $term->term_id;
                continue;
            }
			
            $terms = get_terms( array(
                'taxonomy'   => $tax,
                'hide_empty' => false,
                'search'     => $search,
            ) );

            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                foreach ( $terms as $t ) {
                    $found[ $tax ][] = (int) $t->term_id;
                }
            }
        }

        if ( ! empty( $found ) ) {
            // lưu kết quả tìm được lên query để dùng trong posts_clauses
            $query->set( 'rs_found_terms', $found );
            add_filter( 'posts_clauses', 'custom_product_search_posts_clauses', 10, 2 );
        }
    }
}

function custom_product_search_posts_clauses( $clauses, $query ) {
    global $wpdb;

    if ( is_admin() || ! $query->is_main_query() ) {
        return $clauses;
    }

    $found = $query->get( 'rs_found_terms' );
    if ( empty( $found ) || ! is_array( $found ) ) {
        return $clauses;
    }

    $or_parts = array();
    foreach ( $found as $tax => $term_ids ) {
        $term_ids = array_map( 'intval', (array) $term_ids );
        if ( empty( $term_ids ) ) {
            continue;
        }
        $in = implode( ',', $term_ids );

        $or_parts[] = "EXISTS (
            SELECT 1 FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = '{$tax}'
            AND tt.term_id IN ({$in})
            AND tr.object_id = {$wpdb->posts}.ID
        )";
    }

    if ( ! empty( $or_parts ) ) {

        $clauses['where'] .= ' OR (' . implode( ' OR ', $or_parts ) . ')';
    }

    remove_filter( 'posts_clauses', 'custom_product_search_posts_clauses', 10, 2 );

    return $clauses;
}

add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'hide_shipping_calculator_for_free_shipping' );

function hide_shipping_calculator_for_free_shipping( $show_shipping ) {

    $free_shipping_threshold = 75;

    $cart_total = WC()->cart->get_displayed_subtotal();

    if ( $cart_total >= $free_shipping_threshold ) {
        return false;
    }

    return $show_shipping;
}

add_action( 'woocommerce_email_before_order_table', 'add_custom_text_above_order_table', 10, 4 );

function add_custom_text_above_order_table( $order, $sent_to_admin, $plain_text, $email ) {
    if ( ! $sent_to_admin && $email->id === 'customer_processing_order' ) {
        echo '<p style="margin-bottom:15px;">';
        echo 'Bedankt voor uw bestelling! Ons team gaat er meteen mee aan de slag om ervoor te zorgen dat u de kwaliteit ontvangt die u van ons gewend bent. Wij bereiden en leveren met zorg, zodat u uw bestelling zo snel mogelijk in huis heeft. Mocht u tijdens het proces vragen of speciale wensen hebben, dan horen wij dat graag. Samen zorgen we ervoor dat u tevreden bent met elke levering.';
        echo '</p>';
    }
}

add_action( 'woocommerce_checkout_process', 'restrict_orders_by_zipcode_range' );

function restrict_orders_by_zipcode_range() {
	
    $postcode = isset($_POST['billing_postcode']) ? strtoupper(trim($_POST['billing_postcode'])) : '';

    if (empty($postcode)) {
        return;
    }

    preg_match('/(\d{4})/', $postcode, $matches);
    if (empty($matches[1])) {
        return;
    }
    $zip = intval($matches[1]);

    $blocked_ranges = array(
        'Heemstede'                => array(2100, 2106),
        'Haarlem'                  => array(2000, 2037),
        'Zandvoort'                => array(2040, 2042),
        'Overveen'                 => array(2050, 2051),
        'Bloemendaal'              => array(2060, 2061),
        'Santpoort-Noord & -Zuid'  => array(2070, 2071),
        'IJmuiden'                 => array(1970, 1976),
        'Beverwijk & Heemskerk'    => array(1940, 1969),
        'Wormer'                   => array(1530, 1531),
        'Assendelft'               => array(1560, 1567),
        'Muiden'                   => array(1398, 1398),
        'Weesp'                    => array(1380, 1384),
        'Amstelveen'               => array(1180, 1189),
        'Hoofddorp'                => array(2130, 2136),
        'Ouderkerk aan de Amstel'  => array(1190, 1191),
        'Badhoevedorp'             => array(1170, 1171),
        'Zaanstad'                 => array(1500, 1547),
        'Amsterdam'                => array(1000, 1109),
        'Diemen'                   => array(1110, 1113),
    );

    foreach ($blocked_ranges as $city => $range) {
        list($min, $max) = $range;
        if ($zip >= $min && $zip <= $max) {
            wc_add_notice(
                sprintf(
                    'Wij nemen geen bestellingen aan uit dit postcodegebied: %s (%04d–%04d)',
                    $city,
                    $min,
                    $max
                ),
                'error'
            );
            break;
        }
    }
}