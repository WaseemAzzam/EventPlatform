<?php
// Add Meta Boxes
add_action('add_meta_boxes', function() {
    add_meta_box(
        'event_date',
        'Event Date',
        'event_date_meta_box_callback',
        'event',
        'side',
        'high'
    );
    add_meta_box(
        'event_premium',
        'Premium Event',
        'event_premium_meta_box_callback',
        'event',
        'side'
    );
    add_meta_box(
        'event_location',
        'Event Location',
        'event_location_meta_box_callback',
        'event',
        'normal',
        'default'
    );
    add_meta_box(
        'event_price',
        'Event Price',
        'event_price_meta_box_callback',
        'event',
        'side'
    );
    add_meta_box(
        'event_custom_title',
        'Custom Event Title',
        'event_custom_title_meta_box_callback',
        'event',
        'normal',
        'default'
    );
    add_meta_box(
        'event_custom_description',
        'Custom Event Description',
        'event_custom_description_meta_box_callback',
        'event',
        'normal',
        'default'
    );
    add_meta_box(
        'event_custom_image',
        'Custom Event Image',
        'event_custom_image_meta_box_callback',
        'event',
        'side',
        'default'
    );
});

function event_date_meta_box_callback($post) {
    $value = get_post_meta($post->ID, 'event_date', true);
    echo '<label for="event_date">Date:</label> ';
    echo '<input type="date" id="event_date" name="event_date" value="' . esc_attr($value) . '" style="width:100%" required />';
}

function event_premium_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_is_premium', true);
    echo '<label><input type="checkbox" name="event_is_premium" value="1"' . checked($value, '1', false) . '> Premium Event</label>';
}

function event_location_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_event_location', true);
    echo '<input type="text" name="event_location" value="' . esc_attr($value) . '" style="width:100%" placeholder="Enter event address">';
}

function event_price_meta_box_callback($post) {
    $value = get_post_meta($post->ID, 'event_price', true);
    echo '<label>Price: <input type="number" step="0.01" min="0" name="event_price" value="' . esc_attr($value) . '" style="width:100%" placeholder="Enter price"></label>';
}

function event_custom_title_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_event_custom_title', true);
    echo '<input type="text" name="event_custom_title" value="' . esc_attr($value) . '" style="width:100%" placeholder="Enter custom event title">';
}

function event_custom_description_meta_box_callback($post) {
    $value = get_post_meta($post->ID, '_event_custom_description', true);
    echo '<textarea name="event_custom_description" style="width:100%" rows="4" placeholder="Enter custom event description">' . esc_textarea($value) . '</textarea>';
}

function event_custom_image_meta_box_callback($post) {
    $image_id = get_post_meta($post->ID, '_event_custom_image_id', true);
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    echo '<div id="event-custom-image-wrapper">';
    if ($image_url) {
        echo '<img src="' . esc_url($image_url) . '" style="max-width:100%;height:auto;display:block;margin-bottom:10px;" />';
    }
    echo '<input type="hidden" id="event_custom_image_id" name="event_custom_image_id" value="' . esc_attr($image_id) . '" />';
    echo '<button type="button" class="button" id="event_custom_image_upload">Select Image</button> ';
    echo '<button type="button" class="button" id="event_custom_image_remove">Remove Image</button>';
    echo '</div>';
    ?>
    <script>
    jQuery(document).ready(function($){
        var frame;
        $('#event_custom_image_upload').on('click', function(e){
            e.preventDefault();
            if(frame){ frame.open(); return; }
            frame = wp.media({
                title: 'Select or Upload Event Image',
                button: { text: 'Use this image' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                $('#event_custom_image_id').val(attachment.id);
                $('#event-custom-image-wrapper').prepend('<img src="'+attachment.url+'" style="max-width:100%;height:auto;display:block;margin-bottom:10px;" />');
            });
            frame.open();
        });
        $('#event_custom_image_remove').on('click', function(){
            $('#event_custom_image_id').val('');
            $('#event-custom-image-wrapper img').remove();
        });
    });
    </script>
    <?php
}

// Save Meta Box Data
add_action('save_post_event', function($post_id) {
    if (isset($_POST['event_date'])) {
        update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event_date']));
    }
    if (array_key_exists('event_is_premium', $_POST)) {
        update_post_meta($post_id, '_is_premium', '1');
    } else {
        update_post_meta($post_id, '_is_premium', '0');
    }
    if (isset($_POST['event_location'])) {
        update_post_meta($post_id, '_event_location', sanitize_text_field($_POST['event_location']));
    }
    if (isset($_POST['event_price'])) {
        update_post_meta($post_id, 'event_price', sanitize_text_field($_POST['event_price']));
    }
    if (isset($_POST['event_custom_title'])) {
        update_post_meta($post_id, '_event_custom_title', sanitize_text_field($_POST['event_custom_title']));
    }
    if (isset($_POST['event_custom_description'])) {
        update_post_meta($post_id, '_event_custom_description', sanitize_textarea_field($_POST['event_custom_description']));
    }
    if (isset($_POST['event_custom_image_id'])) {
        update_post_meta($post_id, '_event_custom_image_id', intval($_POST['event_custom_image_id']));
    }

    // Create and link WooCommerce Ticket product if event is Premium and not already linked
    if (get_post_meta($post_id, '_is_premium', true) === '1' && !get_post_meta($post_id, 'linked_ticket_id', true)) {
        if (class_exists('WC_Product_Simple')) {
            $custom_title = get_post_meta($post_id, '_event_custom_title', true);
            $custom_description = get_post_meta($post_id, '_event_custom_description', true);
            $custom_image_id = get_post_meta($post_id, '_event_custom_image_id', true);
            // Fallback to event featured image if no custom image is set
            if (!$custom_image_id) {
                $custom_image_id = get_post_thumbnail_id($post_id);
            }

            $event_title = $custom_title ? $custom_title : get_the_title($post_id);
            $event_description = $custom_description ? $custom_description : apply_filters('the_content', get_post_field('post_content', $post_id));
            $event_price = get_post_meta($post_id, 'event_price', true);
            $sku = 'event_' . $post_id;

            $product = new WC_Product_Simple();
            $product->set_name($event_title);
            $product->set_description($event_description);
            $product->set_regular_price($event_price ? $event_price : '0');
            $product->set_sku($sku);
            $product->set_status('publish');
            if ($custom_image_id) {
                $product->set_image_id($custom_image_id);
            }
            $product_id = $product->save();

            if ($product_id) {
                update_post_meta($post_id, 'linked_ticket_id', $product_id);
            }
        }
    }
});

// When an event is trashed or deleted, also trash the linked WooCommerce product
add_action('before_delete_post', function($post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'event') {
        $product_id = get_post_meta($post_id, 'linked_ticket_id', true);
        if ($product_id && get_post_type($product_id) === 'product') {
            wp_trash_post($product_id);
        }
    }
});
add_action('wp_trash_post', function($post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'event') {
        $product_id = get_post_meta($post_id, 'linked_ticket_id', true);
        if ($product_id && get_post_type($product_id) === 'product') {
            wp_trash_post($product_id);
        }
    }
}); 