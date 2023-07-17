<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Soft Media Delete
 * Plugin URI:        https://xyz.com
 * Description:       Allows uploading images for categories, prevent directly delete images on media library and display IDs if image attached with post or category. Also implementation of REST API to get the info of image and delete the image through Image ID.
 * Version:           1.0.0
 * Author:            Faheem
 * Author URI:        https://xyz.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       soft-media-delete
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 
 */
define( 'SOFT_MEDIA_DELETE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-soft-media-delete-activator.php
 */
function activate_soft_media_delete() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-soft-media-delete-activator.php';
	Soft_Media_Delete_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-soft-media-delete-deactivator.php
 */
function deactivate_soft_media_delete() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-soft-media-delete-deactivator.php';
	Soft_Media_Delete_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_soft_media_delete' );
register_deactivation_hook( __FILE__, 'deactivate_soft_media_delete' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-soft-media-delete.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_soft_media_delete() {

	$plugin = new Soft_Media_Delete();
	$plugin->run();

}
run_soft_media_delete();

/**
 * Begins Implementation of the plugin with commit details
 *
 */
// Enqueue necessary scripts and styles
function enqueue_category_image_upload_scripts() {
    wp_enqueue_media();
    wp_enqueue_script('soft-media-delete', plugin_dir_url(__FILE__) . 'admin/js/soft-media-delete-admin.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'enqueue_category_image_upload_scripts');


// Add category image upload field
function category_image_upload($term) {
    $term_id = is_object($term) ? $term->term_id : '';
    $image = get_term_meta($term_id, 'category_image', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="category-image"><?php _e('Category Image', 'category-image-upload'); ?></label></th>
        <td>
            <input type="hidden" id="category-image" name="category-image" class="custom_media_url" value="<?php echo esc_attr($image); ?>">
            <div id="category-image-preview"><?php if ($image) echo '<img src="' . esc_url($image) . '">'; ?></div>
            <p>
                <button type="button" class="button button-secondary category-image-upload"><?php _e('Upload Image', 'category-image-upload'); ?></button>
                <button type="button" class="button button-secondary category-image-remove"><?php _e('Remove Image', 'category-image-upload'); ?></button>
            </p>
        </td>
    </tr>
    <script type="text/javascript">
        jQuery(function ($) {
            // Remove the image preview after category added
            $('#submit').on('click', function () {
                $('#category-image-preview').empty();
            });
        });
    </script>
    <?php
}

add_action('category_add_form_fields', 'category_image_upload', 10, 2);
add_action('category_edit_form_fields', 'category_image_upload', 10, 2);


// Save category image
function save_category_image($term_id) {
    if (isset($_POST['category-image'])) {
        $image = sanitize_text_field($_POST['category-image']);
        update_term_meta($term_id, 'category_image', $image);
        
        // Trigger the category image display on category pages
        do_action('manage_category_custom_column', 'image', '', $term_id);
    }
}

add_action('edited_category', 'save_category_image', 10, 2);
add_action('create_category', 'save_category_image', 10, 2);

// Display category image on category page in admin
function display_category_image($term) {
    $image = get_term_meta($term->term_id, 'category_image', true);
    if ($image) {
        echo '<div><img src="' . $image . '" style="max-width: 100%; height: auto;"></div>';
    }
}
add_action('category_admin_head', 'display_category_image');

// Prevent image deletion if image is associated with featured image, content body and categories

function prevent_image_deletion($post_id) {
    $attachment = get_post($post_id);
    $alert_message = '';

    if ($attachment && $attachment->post_type === 'attachment' && strpos($attachment->post_mime_type, 'image/') === 0) {
        // Check if the image is used as a featured image
        $featured_query_args = array(
            'post_type' => 'any',
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'value' => $post_id,
                ),
            ),
            'posts_per_page' => -1, // Retrieve all posts
        );

        $featured_query = new WP_Query($featured_query_args);

        // Check if the image is used in post content
        $post_content_query_args = array(
            'post_type' => 'any',
            's' => $attachment->post_name,
            'posts_per_page' => -1, // Retrieve all posts
        );

        $post_content_query = new WP_Query($post_content_query_args);

        $categories = get_categories(array(
            'meta_query' => array(
                array(
                    'key' => 'category_image',
                    'value' => wp_get_attachment_url($post_id),
                    'compare' => '=',
                ),
            ),
            'hide_empty' => false,
        ));

        $post_ids = array();

        if ($featured_query->have_posts()) {
            while ($featured_query->have_posts()) {
                $featured_query->the_post();
                $post_ids[] = get_the_ID();
            }
            wp_reset_postdata();
        }

        if ($post_content_query->have_posts()) {
            while ($post_content_query->have_posts()) {
                $post_content_query->the_post();
                $post_ids[] = get_the_ID();
            }
            wp_reset_postdata();
        }

        $category_ids = array();
        foreach ($categories as $category) {
            $category_ids[] = $category->term_id;
        }

        if (!empty($post_ids) || !empty($category_ids)) {
            if (!empty($post_ids)) {
                $alert_message .= "The image cannot be deleted because it is used in the following post ID(s): " . implode(', ', $post_ids);
            }

            if (!empty($category_ids)) {
                $alert_message .= "\n\nThis image is assigned to the following Category ID(s): " . implode(', ', $category_ids);
            }
        }
    }

    if (!empty($alert_message)) {
        $alert_message = str_replace(array("\r", "\n"), '\n', $alert_message); // Escape new lines

        if (isset($_GET['mode']) && $_GET['mode'] === 'grid') {
            // Show the same alert message for grid view
            echo '<div class="error"><p>' . $alert_message . '. ' . __('Error in deleting the attachment.') . '</p></div>';
        } else {
            // Show the alert message for list view
            echo '<script type="text/javascript">';
            echo 'alert("' . $alert_message . '.\n\nIt cannot be deleted.");';
            echo 'window.history.back();';
            echo '</script>';
            exit; // Stop further execution
        }
    }
}

add_action('delete_attachment', 'prevent_image_deletion');

/**
 * Add post and term ID to Attachment Details popup in WordPress media library.
 */

// Display term ID if the image path exists in attachment details
function category_image_upload_display_term_id($form_fields, $post) {
    $image_path = wp_get_attachment_url($post->ID);
    global $wpdb;
    $category_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT tm.term_id
            FROM {$wpdb->termmeta} AS tm
            WHERE tm.meta_key = 'category_image'
            AND tm.meta_value = %s",
            $image_path
        )
    );

    $category_edit_links = array();
    foreach ($category_ids as $category_id) {
        $category_edit_link = get_edit_term_link($category_id, 'category');
        if (!empty($category_edit_link)) {
            $category_edit_links[] = '<a href="' . esc_url($category_edit_link) . '">Edit Category ' . $category_id . '</a>';
        }
    }

    // Check if category IDs exist
    if (!empty($category_ids)) {
        // Set category IDs value
        $category_ids_value = implode(', ', $category_ids);

        // Set category edit links value
        $category_edit_links_value = implode(', ', $category_edit_links);

        $form_fields['category_ids'] = array(
            'label' => 'Associate Cat ID(s)',
            'input' => 'html',
            'html'  => '<input type="text" value="' . $category_ids_value . '" readonly="readonly" class="widefat" /><br />' .
                       $category_edit_links_value,
			'required' => false,      
	 );
    }

    return $form_fields;
}
add_filter('attachment_fields_to_edit', 'category_image_upload_display_term_id', 10, 2);



// Display post ID if the image path exists in content body of post
function add_custom_attachment_fields($form_fields, $post) {
    // Get the post IDs associated with the image in the attachment details
    $attachment_post_ids = get_posts(array(
        'post_type'      => 'any',
        'posts_per_page' => -1,
        'meta_key'       => '_thumbnail_id',
        'meta_value'     => $post->ID,
        'fields'         => 'ids',
    ));

    // Get the post IDs associated with the image in the body content
    $body_post_ids = get_post_ids_by_attached_image($post->ID);

    // Merge the post IDs from both sources
    $all_post_ids = array_unique(array_merge($attachment_post_ids, $body_post_ids));

    if (!empty($all_post_ids)) {
        // Add the post ID fields to the attachment details popup
        $form_fields['post_id'] = array(
            'label'    => 'Associate Post ID(s)',
            'input'    => 'html',
            'html'     => '<input type="text" value="' . implode(', ', $all_post_ids) . '" readonly="readonly" class="widefat" /><br />' .
                          generate_post_links($all_post_ids),
			'required' => false, 
        );
    }

    return $form_fields;
}

add_filter('attachment_fields_to_edit', 'add_custom_attachment_fields', 10, 2);


function generate_post_links($post_ids) {
    $links = '';

    foreach ($post_ids as $post_id) {
        $edit_link = get_edit_post_link($post_id);
        $links .= '<a href="' . esc_url($edit_link) . '">Edit Post ' . $post_id . '</a>,&nbsp';
    }

    return $links;
}

function get_post_ids_by_attached_image($image_id) {
    global $wpdb;

    // Search for the image ID within post content
    $query = $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_type = %s AND post_status = %s",
        '%' . $image_id . '%',
        'post',
        'publish'
    );

    $results = $wpdb->get_col($query);

    if (!empty($results)) {
        return $results;
    }

    return array();
}

/**
 * Add custom media table in WordPress media library.
 */
function custom_media_column( $cols ) {
    $cols['attached_objects'] = __( 'Attached Objects', 'text-domain' );
    return $cols;
}
add_filter( 'manage_media_columns', 'custom_media_column' );

function custom_media_column_data($col_name, $attachment_id) {
    if ('attached_objects' == $col_name) {
        $attached_posts = get_attached_post_ids($attachment_id);
        $attached_categories = get_attached_category_ids($attachment_id);

        $output = '';

        if (!empty($attached_posts)) {
            foreach ($attached_posts as $post_id) {
                $edit_link = get_edit_post_link($post_id);
                $output .= '<a href="' . esc_url($edit_link) . '">Post ' . $post_id . '</a>, ';
            }
        }

        if (!empty($attached_categories)) {
            foreach ($attached_categories as $category_id) {
                $edit_link = get_edit_term_link($category_id, 'category');
                $output .= '<a href="' . esc_url($edit_link) . '">Category ' . $category_id . '</a>, ';
            }
        }

        echo rtrim($output, ', '); // Remove trailing comma and space
    }
}

add_action('manage_media_custom_column', 'custom_media_column_data', 10, 2);
/**
 * Get data for custom media table in WordPress media library list view.
 */
function get_attached_post_ids($attachment_id) {
    $post_ids = array();
    
    // Search for the image ID within post content
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_content LIKE %s AND post_type = %s AND post_status = %s",
        '%' . $attachment_id . '%',
        'post',
        'publish'
    );
    $results = $wpdb->get_col($query);
    
    if (!empty($results)) {
        $post_ids = $results;
    }
    
    // Check if the image is attached to any posts in the attachment details
    $args = array(
        'post_type' => 'any',
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'value' => $attachment_id,
                'compare' => '='
            )
        )
    );
    $posts = get_posts($args);
    
    if ($posts) {
        foreach ($posts as $post) {
            $post_ids[] = $post->ID;
        }
    }
    
    return $post_ids;
}

function get_attached_category_ids($attachment_id) {
    $category_ids = array();

    $image_path = wp_get_attachment_url($attachment_id);
    global $wpdb;
    $category_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT tm.term_id
            FROM {$wpdb->termmeta} AS tm
            WHERE tm.meta_key = 'category_image'
            AND tm.meta_value = %s",
            $image_path
        )
    );

    return $category_ids;
}

/**
 * Define API endpoint, fetch the images of media library through image id.
 */
function register_image_details_endpoint() {
    register_rest_route('assignment/v1', '/images/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'get_image_details',
        'args' => array(
            'id' => array(
                'sanitize_callback' => 'absint',
            ),
        ),
    ));
}
add_action('rest_api_init', 'register_image_details_endpoint');

// Get image details from WordPress media library through image ID
function get_image_details($request) {
    $image_id = $request->get_param('id');
	
	// Check if the image exists
    $image = get_post($image_id);

    if (!$image || $image->post_type !== 'attachment') {
        return new WP_Error('image_not_found', 'Image not found: Invalid image ID', array('status' => 404));
    }

    // Get image details from WordPress media library
    $attachment = wp_get_attachment_metadata($image_id);

    if (!$attachment || !is_array($attachment) || empty($attachment['file'])) {
        return new WP_Error('image_not_found', 'Image not found', array('status' => 404));
    }

    // Get alt text
    $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);

    // Check if image is attached to any post
    $attached_posts = get_posts(array(
        'post_type' => 'any',
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'value' => $image_id,
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    ));

    // Check if image is included in any post content
    $content_posts = get_posts(array(
        'post_type' => 'any',
        's' => $attachment['file'],
        'fields' => 'ids',
    ));

    // Combine the attached and content posts
    $post_ids = array_unique(array_merge($attached_posts, $content_posts));

    // Get term IDs of categories associated with the image
    global $wpdb;
    $category_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT tm.term_id
            FROM {$wpdb->termmeta} AS tm
            WHERE tm.meta_key = 'category_image'
            AND tm.meta_value = %s",
            wp_get_attachment_url($image_id)
        )
    );
    $cat_ids = array_unique($category_ids);
    
    // Convert the category IDs to integers
    $cat_ids = array_map('intval', $cat_ids);

    // Merge the post IDs and category IDs together
    $term_ids = array_merge($post_ids, $cat_ids);

    // Prepare response object
    $response = array(
        'id' => $image_id,
        'date' => date('Y-m-d H:i:s', filemtime(get_attached_file($image_id))),
        'slug' => isset($attachment['file']) ? basename($attachment['file']) : '',
        'type' => pathinfo($attachment['file'], PATHINFO_EXTENSION),
        'link' => wp_get_attachment_url($image_id),
        'alt_text' => !empty($alt_text) ? $alt_text : null,
        'attached_objects ID(s)' => count($term_ids) > 0 ? $term_ids : null,
    );
    return rest_ensure_response($response);
}

/**
 * Define API endpoint AND delete the images of media library through image id.
 */
function register_assignment_api_endpoints() {
    register_rest_route('assignment/v1', '/delete-image/(?P<id>\d+)', array(
        'methods' => 'DELETE',
        'callback' => 'delete_image',
    ));
}
add_action('rest_api_init', 'register_assignment_api_endpoints');

// Delete image through image id
function delete_image($request) {
    $image_id = $request->get_param('id');

    // Check if the image exists
    $image = get_post($image_id);

    if (!$image || $image->post_type !== 'attachment') {
        return new WP_Error('image_not_found', 'Image not found: Invalid image ID', array('status' => 404));
    }

    // Check if the image is attached to any posts or terms
    $attached_posts = get_posts(array(
        'post_type' => 'any',
        'meta_query' => array(
            array(
                'key' => '_thumbnail_id',
                'value' => $image_id,
                'compare' => '=',
            ),
        ),
        'fields' => 'ids',
    ));

    global $wpdb;
    $category_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT tm.term_id
            FROM {$wpdb->termmeta} AS tm
            WHERE tm.meta_key = 'category_image'
            AND tm.meta_value = %s",
            wp_get_attachment_url($image_id)
        )
    );

    // Check if image is included in any post content
    $content_posts = get_posts(array(
        'post_type' => 'any',
        's' => $image->post_name,
        'fields' => 'ids',
    ));

    if (!empty($attached_posts) || !empty($category_ids) || !empty($content_posts)) {
        return new WP_Error('deletion_failed', 'Deletion failed: Image is attached to posts, terms, or included in post content', array('status' => 400));
    }

    // Delete the image
    $result = wp_delete_attachment($image_id, true);

    if ($result === false) {
        return new WP_Error('deletion_failed', 'Deletion failed: Unable to delete the image', array('status' => 500));
    }

    return array(
        'message' => 'Image deleted successfully',
    );

}

