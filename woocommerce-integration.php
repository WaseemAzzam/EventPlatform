<?php
add_action('save_post_event', function($post_id) {
    // Only create if premium and not already linked
    if (get_post_meta($post_id, '_is_premium', true) === '1' && !get_post_meta($post_id, 'linked_ticket_id', true)) {
        if (class_exists('WC_Product_Simple')) {
            $event_title = get_the_title($post_id);
            $event_price = get_post_meta($post_id, 'event_price', true);
            $sku = 'event_' . $post_id;

            $product = new WC_Product_Simple();
            $product->set_name($event_title);
            $product->set_regular_price($event_price ? $event_price : '0');
            $product->set_sku($sku);
            $product->set_status('publish');
            $product_id = $product->save();

            if ($product_id) {
                update_post_meta($post_id, 'linked_ticket_id', $product_id);
            }
        }
    }
}); 


