<?php
/**
 * Template for displaying single event pages
 */

get_header(); ?>

<div class="wrap">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <div id="event-spa-root">
                <?php
                // Fallback content while React loads
                while (have_posts()) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="entry-header">
                            <h1 class="entry-title"><?php the_title(); ?></h1>
                        </header>

                        <div class="entry-content">
                            <?php
                            echo '<p><strong>Date:</strong> ' . esc_html(get_post_meta(get_the_ID(), 'event_date', true)) . '</p>';
                            echo '<p><strong>Location:</strong> ' . esc_html(get_post_meta(get_the_ID(), '_event_location', true)) . '</p>';
                            the_content();
                            ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</div>

<script type="text/javascript">
    // Pass event data to React app
    window.eventData = <?php
        $event_data = [
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'content' => get_the_content(),
            'date' => get_post_meta(get_the_ID(), 'event_date', true),
            'time' => get_post_meta(get_the_ID(), 'event_time', true),
            'location' => get_post_meta(get_the_ID(), '_event_location', true),
            'is_premium' => get_post_meta(get_the_ID(), '_is_premium', true) === '1',
        ];
        echo json_encode($event_data);
    ?>;
</script>

<?php
// Enqueue React app
wp_enqueue_script('event-spa', plugins_url('event-platform/assets/index.js'), [], '1.0.0', true);
wp_enqueue_style('event-spa', plugins_url('event-platform/assets/index.css'));

get_footer();
?> 
