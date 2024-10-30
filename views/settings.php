<?php
/**
 * Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wrap">
	<h2><?php echo esc_html( $this->plugin->displayName ); ?> &raquo; <?php esc_html_e( 'Settings', 'hopli' ); ?></h2>

	<?php
	if ( isset( $this->message ) ) {
		?>
		<div class="updated fade"><p><?php echo esc_html( $this->message ); ?></p></div>
		<?php
	}
	if ( isset( $this->errorMessage ) ) {
		?>
		<div class="error fade"><p><?php echo esc_html( $this->errorMessage ); ?></p></div>
		<?php
	}
	?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- Content -->
			<div id="post-body-content">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h3 class="hndle"><?php esc_html_e( 'Settings', 'hopli' ); ?></h3>

						<div class="inside">
							<form action="options-general.php?page=<?php echo esc_html( $this->plugin->name ); ?>" method="post">
								<p>
									<label for="hopli_client_id"><strong><?php esc_html_e( 'Your Hopli Client ID', 'hopli' ); ?></strong></label>
									<textarea name="hopli_client_id" id="hopli_client_id" class="widefat" rows="8" style="font-family:Courier New;" <?php echo ( ! current_user_can( 'unfiltered_html' ) ) ? ' disabled="disabled" ' : ''; ?>><?php echo esc_html( $this->settings['hopli_client_id'] ); ?></textarea>
								</p>

								<p>
									<label for="hopli_app_id"><strong><?php esc_html_e( 'Your Hopli App ID', 'hopli' ); ?></strong></label>
									<textarea name="hopli_app_id" id="hopli_app_id" class="widefat" rows="8" style="font-family:Courier New;" <?php echo ( ! current_user_can( 'unfiltered_html' ) ) ? ' disabled="disabled" ' : ''; ?>><?php echo esc_html( $this->settings['hopli_app_id'] ); ?></textarea>
								</p>

								<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
									<?php wp_nonce_field( $this->plugin->name, $this->plugin->name . '_nonce' ); ?>
									<p>
										<input name="submit" type="submit" name="Submit" class="button button-primary" value="<?php esc_attr_e( 'Save', 'hopli' ); ?>" />
									</p>
								<?php endif; ?>
							</form>
						</div>
					</div>
					<!-- /postbox -->
				</div>
				<!-- /normal-sortables -->
			</div>
			<!-- /post-body-content -->

			<!-- /postbox-container -->
		</div>
	</div>
</div>
