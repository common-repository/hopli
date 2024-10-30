<?php
/**
 * Notices template
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="notice notice-success is-dismissible <?php echo esc_html ( $this->plugin->name ); ?>-notice-welcome">
	<p>
		<?php
		printf(
			/* translators: %s: Name of this plugin */
			__( 'Thank you for installing %1$s!', 'hopli' ),
			$this->plugin->displayName
		);
		?>
		<a href="<?php echo esc_html ( $setting_page ); ?>"><?php esc_html_e( 'Click here', 'hopli' ); ?></a> <?php esc_html_e( 'to configure the plugin.', 'hopli' ); ?>
	</p>
</div>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		$(document).on( 'click', '.<?php echo esc_html ( $this->plugin->name ); ?>-notice-welcome button.notice-dismiss', function( event ) {
			event.preventDefault();
			$.post( ajaxurl, {
				action: '<?php echo esc_html ( $this->plugin->name ) . '_dismiss_dashboard_notices'; ?>',
				nonce: '<?php echo esc_html ( wp_create_nonce( $this->plugin->name . '-nonce' ) ); ?>'
			});
			$('.<?php echo esc_html ( $this->plugin->name ); ?>-notice-welcome').remove();
		});
	});
</script>
