<?php
/**
 * WDS Site Documentation.
 *
 * @package           WebDevStudios\Documentation
 * @author            WebDevStudios
 * @copyright         2021 WebDevStudios
 * @license           GPL-2.0-or-later
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WDS Site Documentation
 * Plugin URI:        https://github.com/webdevstudios/wds-site-documentation
 * Description:       A plugin to host site documentation in an easily accessible place in the WordPress dashboard.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            WebDevStudios
 * Author URI:        https://webdevstudios.com
 * Text Domain:       wds-site-documentation
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WebDevStudios\Documentation;

use WP_Query;

/**
 * Add Site Documentation page to the admin menu for admins only.
 *
 * @author Evan Hildreth <evan.hildreth@webdevstudios.com>
 * @since  1.0.0
 */
function add_wds_documentation_dashboard_page() {
	add_submenu_page(
		'options-general.php',
		'Site Documentation',
		'Documentation',
		'manage_options',
		'wds_documentation',
		__NAMESPACE__ . '\wds_documentation_dashboard',
		100
	);
}

/**
 * Output the dashboard page for documentation
 *
 * @author Evan Hildreth <evan.hildreth@webdevstudios.com>
 * @since  1.0.0
 */
function wds_documentation_dashboard() {
	$img_url        = plugin_dir_url( __FILE__ ) . '/wds_banner.png';
	$enable_changes = apply_filters( 'wds_documentation_enable_changes', true );

	if ( $enable_changes ) {
		// Save attachment ID.
		if ( isset( $_POST['submit_video_selector'] ) && isset( $_POST['wds_documentation_video_id'] ) ) :
			// wp_die('<pre>'.print_r($_POST, true).'</pre>');
			update_option( 'wds_documentation_video_id', absint( $_POST['wds_documentation_video_id'] ) );
		endif;

		wp_enqueue_media();
	}
?>
	<h1><?php esc_html_e( 'Site Documentation', 'wds-site-documentation' ); ?></h1>

	<p><a href="https://webdevstudios.com/"><img src="<?php echo esc_url( $img_url ); ?>" style="max-width:100%;height:auto;" alt="WebDevStudios"></a></p>

	<?php display_documentation(); ?>

	<p>If you need help, we're here to support you! <a href="https://webdevstudios.com/contact/">Contact WDS</a></p>

	<?php if ( $enable_changes ) : ?>
		<h2>Administration</h2>

		<form method='post'>
			<p>Current video: <span id="wds-video-name"><?php echo get_the_title( get_option( 'wds_documentation_video_id' ) ); ?></span></p>
			<input id="upload_image_button" type="button" class="button" value="<?php _e( 'Select or upload video' ); ?>" />
			<input type='hidden' name='wds_documentation_video_id' id='wds_documentation_video_id' value='<?php echo get_option( 'wds_documentation_video_id' ); ?>'>
			<input type="submit" name="submit_video_selector" value="Save" class="button-primary">
		</form>
	<?php endif; ?>

<?php
}

add_action( 'admin_menu', __NAMESPACE__ . '\add_wds_documentation_dashboard_page' );

add_action( 'admin_bar_menu', __NAMESPACE__ . '\add_toolbar_items', 100 );
function add_toolbar_items( $admin_bar ) {
	$admin_bar->add_menu( [
		'id'    => 'wds-documentation',
		'title' => 'Site Documentation',
		'href'  => '/wp-admin/admin.php?page=wds_documentation',
		'meta'  => [
			'title' => __( 'Documentation', 'wds-site-documentation' ),
		],
	] );
}

add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_widget' );
function add_widget() {
	wp_add_dashboard_widget( 'wds_site_documentation', 'Site Documentation', __NAMESPACE__ . '\display_documentation' );
}

function display_documentation() {
	$video_url = '';
	$pdf_url   = '';

	$video_id = get_option( 'wds_documentation_video_id' );
	if ( $video_id ) {
		$video_url = wp_get_attachment_url( $video_id );
	}
	$video_url = apply_filters( 'wds_documentation_video_url', $video_url );

	$pdf_query = new WP_Query( [
		'name'                => 'wds-documentation-pdf',
		'post_type'           => [ 'attachment' ],
		'nopaging'            => false,
		'posts_per_page'      => '1',
		'ignore_sticky_posts' => false,
	] );
	if ( $pdf_query->have_posts() ) {
		$pdf_url = wp_get_attachment_url( $pdf_query->posts[0]->ID );
	}
	$pdf_url = apply_filters( 'wds_documentation_pdf_url', $pdf_url );
?>
	<?php if ( $video_url ) : ?>
		<p><video controls>
		<source src="<?php echo esc_url( $video_url ); ?>">
		<?php esc_html_e( 'Sorry, your browser doesn\'t support embedded videos.', 'wds-site-documentation' ); ?>
		</video></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Video not found; upload a video to the media library with the slug', 'wds-site-documentation' ); ?> <code>wds-documentation-video</code>.</p>
	<?php endif; ?>

	<?php if ( $pdf_url ) : ?>
		<p><a href="<?php echo esc_url( $pdf_url ); ?>"><?php esc_html_e( 'View PDF documentation', 'wds-site-documentation' ); ?></a></p>
	<?php else : ?>
		<p><?php esc_html_e( 'PDF not found; upload a PDF to the media library with the slug', 'wds-site-documentation' ); ?> <code>wds-documentation-pdf</code>.</p>
	<?php endif; ?>
<?php
}

// ////////////////////////////////////////.


add_action( 'admin_footer', __NAMESPACE__ . '\media_selector_print_scripts' );

function media_selector_print_scripts() {

	$my_saved_attachment_post_id = get_option( 'wds_documentation_video_id', 0 );

	?><script type='text/javascript'>

		jQuery( document ).ready( function( $ ) {

			// Uploading files
			var file_frame;
			var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
			var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this

			jQuery('#upload_image_button').on('click', function( event ){

				event.preventDefault();

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
					// Set the post ID to what we want
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					// Open frame
					file_frame.open();
					return;
				} else {
					// Set the wp.media post id so the uploader grabs the ID we want when initialised
					wp.media.model.settings.post.id = set_to_post_id;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
					title: 'Select a image to upload',
					button: {
						text: 'Use this image',
					},
					multiple: false	// Set to true to allow multiple files to be selected
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
					// We set multiple to false so only get one image from the uploader
					attachment = file_frame.state().get('selection').first().toJSON();

					// Do something with attachment.id and/or attachment.url here
					$( '#wds-video-name' ).text(attachment.title);
					$( '#wds_documentation_video_id' ).val( attachment.id );

					// Restore the main post ID
					wp.media.model.settings.post.id = wp_media_post_id;
				});

					// Finally, open the modal
					file_frame.open();
			});

			// Restore the main ID when the add media button is pressed
			jQuery( 'a.add_media' ).on( 'click', function() {
				wp.media.model.settings.post.id = wp_media_post_id;
			});
		});

	</script><?php

}
