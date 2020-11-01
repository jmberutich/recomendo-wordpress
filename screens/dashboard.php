<?php
// File Security Check.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

$options = get_option( 'recomendo_options' );
// To avoid showing in post type selection
$avoid_post_types = array(
    'attachment', 'revision', 'nav_menu_item', 'custom_css',
    'customize_changeset', 'oembed_cache', 'mc4wp-form', 'user_request',
    'mailpoet_page',  'product_variation', 'shop_order', 'shop_order_refund',
    'shop_coupon', 'shop_webhook', 'vc4_templates', 'vc_grid_item' ,
    'wpcf7_contact_form', 'featured_item', 'sidebar', 'blocks',
    'scheduled-action'
);

?>

<div id="recomendo-dashboard" class="wrap">

    <div class="recomendo-welcome">
        <div class="recomendo-logo">
            <div class="recomendo-version"><?php echo esc_html( sprintf( __( 'v.%s', 'admin-screen' ), Recomendo_Admin::get_version() ) ); ?></div>
        </div>
        <h1>
            <?php if ( !Recomendo_Admin::is_authorized() ): ?>
                <?php esc_html_e( 'Thank you for choosing Recomendo!', 'admin-screen' ); ?>
            <?php endif; ?>
            <?php if ( Recomendo_Admin::is_authorized() ): ?>
                <?php esc_html_e( 'Welcome to Recomendo!', 'admin-screen' ); ?>
           <?php endif; ?>
        </h1>
        <p class="recomendo-subtitle">
            <?php if ( !Recomendo_Admin::is_authorized() ): ?>
                <?php esc_html_e( 'Please activate this copy of Recomendo to get access to personalized recommendations.', 'admin-screen' ); ?>
                <?php printf( __( 'If you don’t have an account, you can request it %1$shere%2$s.', 'admin-screen' ), '<a href="https://www.recomendo.ai/" target="_blank">', '</a>' );?>
            <?php endif; ?>
            <?php if ( Recomendo_Admin::is_authorized() ): ?>
                <?php esc_html_e( 'Your copy of the plugin is activated and ready to rock!', 'admin-screen' ); ?><br>
                <?php esc_html_e( 'We are super excited and honored to see a new member of ever growing Recomendo family. ', 'admin-screen' ); ?>
            <?php endif; ?>
        </p>
    </div>

    <?php
        settings_errors( 'recomendo-auth' );
        if ( !Recomendo_Admin::is_authorized() &&
                isset($_POST['recomendo_authorize_button']) &&
                    check_admin_referer('recomendo_authorize_button_clicked') )  {

                    Recomendo_Admin::authorize( $_POST['recomendo_client_id'],
											    $_POST['recomendo_client_secret']
					);
        }
    ?>

    <div class="recomendo-postbox">
        <h2 class="recomendo-with-subtitle"><?php esc_html_e( 'Let’s recommend awesome content!', 'admin-screen' ); ?></h2>
        <p class="recomendo-subtitle"><?php esc_html_e( "We have assembled useful links to get you started:", 'admin-screen' ); ?></p>

        <div class="recomendo-column-container">

            <?php if ( is_super_admin() ): ?>
            <div class="recomendo-column" style="width: 40%">
                <h3>
                    <?php
                        if ( Recomendo_Admin::is_authorized() ) {
                            esc_html_e( 'Plugin is Registered', 'admin-screen' );
                        }
                        else {
                            esc_html_e( 'Plugin Registration', 'admin-screen' );
                        }
                    ?>
                </h3>
                <form action="admin.php?page=recomendo_plugin" method="post">
                    <?php settings_fields( 'recomendo-auth' ); ?>

                    <?php if ( Recomendo_Admin::is_authorized() ): ?>
                        <p><?php esc_html_e( 'Client ID:', 'admin-screen' ); ?><br><code class="recomendo-code"><?php echo esc_html( Recomendo_Admin::get_censored_code( 'client_id') ); ?></code></p>
						<p><?php esc_html_e( 'Secret:', 'admin-screen' ); ?><br><code class="recomendo-code"><?php echo esc_html( Recomendo_Admin::get_censored_code( 'client_secret' ) ); ?></code></p>
                    <?php endif; ?>

                    <?php if ( !Recomendo_Admin::is_authorized() ): ?>
                        <p><?php esc_html_e( 'Client ID:', 'admin-screen' ); ?>
							<br>
							<input id="recomendo_client_id" class="of-input" name="recomendo_client_id" type="text" value="" size="36">
						</p>
						<p><?php esc_html_e( 'Client Secret:', 'admin-screen' ); ?>
							<br>
							<textarea id="recomendo_client_secret" class="of-input" name="recomendo_client_secret" value="" rows="2" cols="36"></textarea>
						</p>
                    <?php endif; ?>

                    <?php if ( !Recomendo_Admin::is_authorized() ): ?>
                        <?php wp_nonce_field('recomendo_authorize_button_clicked'); ?>
                    	<input type="hidden" class="button button-primary" name="recomendo_authorize_button" value="true" />
                        <?php submit_button( 'Authorize Plugin' ); ?>
                    <?php endif; ?>

                </form>
            </div>
            <?php endif; ?>

            <div class="recomendo-column" style="width: 30%">
                <h3><?php esc_html_e( 'Guides & Support', 'admin-screen' ); ?></h3>
                <ul class="recomendo-links">
                    <li><a href="https://www.recomendo.ai/my-account/" class="recomendo-dashboard-icons-cloud-download"><?php esc_html_e( 'Manage your subscription', 'admin-screen' ); ?></a></li>
                    <li><a href="https://www.recomendo.ai/support/getting-started-with-recomendo/" target="_blank" class="recomendo-dashboard-icons-rocket"><?php esc_html_e( 'Quick start guide', 'admin-screen' ); ?></a></li>
                    <li><a href="https://www.recomendo.ai/support/" target="_blank" class="recomendo-dashboard-icons-life-bouy"><?php esc_html_e( 'Support portal', 'admin-screen' ); ?></a></li>
                </ul>
            </div>

            <div class="recomendo-column" style="width: 30%">
                <h3><?php esc_html_e( 'Help us Improve!', 'admin-screen' ); ?></h3>
                <ul class="recomendo-links">
                    <li><a href="https://www.recomendo.ai/contact-support/" class="recomendo-dashboard-icons-plug"><?php esc_html_e( 'Contact Support', 'admin-screen' ); ?></a></li>
                    <li><a href="mailto:support@recomendo.ai?subject=Bugs&body=Hi%20there!%20I%20found%20a%20bug...%20let%20me%20explain%20you%20how%20to%20replicate%20the%20error..." class="recomendo-dashboard-icons-paint-brush"><?php esc_html_e( 'Report bugs', 'admin-screen' ); ?></a></li>
                    <li><a href="https://wordpress.org/support/plugin/recomendo/reviews/#new-post" class="recomendo-dashboard-icons-paint-brush"><?php esc_html_e( 'Please rate us', 'admin-screen' ); ?></a></li>
                </ul>
            </div>
        </div>
    </div>

   <?php if ( Recomendo_Admin::is_authorized() ) : ?>
		<?php if ( !Recomendo_Admin::is_configured() ) : ?>
			<div class="recomendo-postbox">
				<form method="post" action="options.php">
					<?php settings_errors( 'recomendo-options' ); ?>
					<?php settings_fields( 'recomendo-options' ); ?>
					<?php do_settings_sections( 'recomendo-options' ); ?>


					<h2>What post type do you want to recommend?</h2>


					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="recomendo_post_type"><span>Post Type</span></label>
								</th>
								<td>
									<?php
										foreach ( get_post_types( '', 'names' ) as $post_type ) {
											if (!in_array($post_type, $avoid_post_types)) {
													echo '<label><input type="radio" id="recomendo_post_type" name="recomendo_options[post_type]" value="' . $post_type . '" />' . ucwords($post_type) . '</label><br>';
											}
										}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php else : ?>
			<?php if (!get_option('recomendo_data_saved_ok')) : ?>
				<?php global $recomendo; ?>
				<?php $recomendo->copy_data_to_eventserver(); ?>
			<?php endif; ?>

			<div class="recomendo-postbox">
				<h2>Post Type to Recommend is Configured</h2>
				<p>
					If you want to change the post type you need to uninstall and re-install the Recomendo plugin.
				</p>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="recomendo_post_type"><span>Post Type</span></label>
							</th>
							<td>
								<?php
									foreach ( get_post_types( '', 'names' ) as $post_type ) {
										if ( $post_type == $options['post_type']) {
											echo '<label><input type="radio" id="recomendo_post_type" name="recomendo_options[post_type]" value="' . $post_type . '" checked disabled/>' . ucwords($post_type) . '</label><br>';
										} else if (!in_array($post_type, $avoid_post_types)) {
											echo '<label><input type="radio" id="recomendo_post_type" name="recomendo_options[post_type]" value="' . $post_type . '" disabled />' . ucwords($post_type) . '</label><br>';
										}
									}
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

		<?php endif; ?>
	<?php endif; ?>

	<?php if ( Recomendo_Admin::is_authorized() and Recomendo_Admin::is_configured() ) : ?>

		<div class="recomendo-postbox">
			<form method="post" action="options.php">
				<?php settings_fields( 'recomendo-general-options' ); ?>
				<?php $general_options = get_option( 'recomendo_general_options' ); ?>

				<h2>Recommendation Options</h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="recomendo_general_options"><span>Show Personalized Content to Main Search Engines</span></label>
							</th>
							<td>
								<?php
								if ( isset( $general_options['allow_seo'] ) ) {
									echo '<label><input type="checkbox" id="recomendo_general_options" name="recomendo_general_options[allow_seo]" value="yes" checked /> Improves SEO but consumes your Recomendo Plan</label><br>';
								} else {
									echo '<label><input type="checkbox" id="recomendo_general_options" name="recomendo_general_options[allow_seo]" value="yes" /> Improves SEO but consumes your Recomendo Plan</label><br>';
								}

								?>
							</td>
						</tr>						
						<tr>
							<th scope="row">
								<label for="recomendo_general_options"><span>Exclude Items Older Than</span></label>
							</th>
							<td>
								<?php
								if ( isset( $general_options['expire_date'] ) ) {
									echo '<label><input type="number" id="recomendo_general_options" name="recomendo_general_options[expire_date]" value="' . $general_options['expire_date'] . '" /> Days</label><br>';
								} else {
									echo '<label><input type="number" id="recomendo_general_options" name="recomendo_general_options[expire_date]" value="0" /> Days</label><br>';
								}

								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="recomendo_general_options"><span>Relevance of Similar Items Having the Same Categories</span></label>
							</th>
							<td>
								<span class="recomendo-range-title">None</span>
								<span class="recomendo-range-title">Neutral</span>
								<span class="recomendo-range-title">All</span>
								<?php
									if ( isset( $general_options['similar_categories_relevance'] ) ) {
											echo '<input type="range" id="recomendo_general_options" name="recomendo_general_options[similar_categories_relevance]" min="-1" max="3" step="1" value="' .  $general_options['similar_categories_relevance'] . '" list="tickmarks" />';
										} else {
											echo '<input type="range" id="recomendo_general_options" name="recomendo_general_options[similar_categories_relevance]" min="-1" max="3" step="1" value="2" list="tickmarks"/>';
										}
								?>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="recomendo_general_options"><span>Relevance of Similar Items Having the Same Tags</span></label>
							</th>
							<td>
								<span class="recomendo-range-title">None</span>
								<span class="recomendo-range-title">Neutral</span>
								<span class="recomendo-range-title">All</span>
								<?php
									if ( isset( $general_options['similar_tags_relevance'] ) ) {
											echo '<input type="range" id="recomendo_general_options" name="recomendo_general_options[similar_tags_relevance]" min="-1" max="3" step="1" value="' .  $general_options['similar_tags_relevance'] . '" list="tickmarks" />';
										} else {
											echo '<input type="range" id="recomendo_general_options" name="recomendo_general_options[similar_tags_relevance]" min="-1" max="3" step="1" value="2" list="tickmarks"/>';
										}
								?>
							</td>
						</tr>


					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
				</p>
			</form>
		</div>
	<?php endif; ?>

	<?php if ( Recomendo_Admin::is_authorized() and Recomendo_Admin::is_configured() ) : ?>
		<?php if ( class_exists( 'woocommerce' ) and $options['post_type'] == "product" ) : ?>

			<div class="recomendo-postbox">
				<form method="post" action="options.php">
					<?php settings_fields( 'recomendo-woo-options' ); ?>
					<?php $woo_options = get_option( 'recomendo_woo_options' ); ?>

					<h2>WooCommerce Options</h2>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="recomendo_woo_options"><span>Show Recomendo on</span></label>
								</th>
								<td>
									<?php
									if ( isset( $woo_options['woo_show_related'] ) ) {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_show_related]" value="yes" checked/>WooCommerce Related Products</label><br>';
									} else {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_show_related]" value="yes" />WooCommerce Related Products</label><br>';
									}

									if ( isset( $woo_options['woo_show_cart'] ) ) {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_show_cart]" value="yes" checked/>WooCommerce Cart</label><br>';
									} else {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_show_cart]" value="yes"/>WooCommerce Cart</label><br>';
									}
									?>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="recomendo_woo_num_related"><span>Recommendations in Related Products</span></label>
								</th>
								<td>
									<?php
									if ( isset( $woo_options['woo_num_related'] ) ) {

										echo '<input type="number" id="recomendo_woo_num_related" name="recomendo_woo_options[woo_num_related]" value="' . $woo_options['woo_num_related'] . '"/>';
									} else {
										echo '<input type="number" id="recomendo_woo_num_related" name="recomendo_woo_options[woo_num_related]" value="12"/>';
									}

									?>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="recomendo_woo_num_cart"><span>Recommendations in Cart</span></label>
								</th>
								<td>
									<?php
									if ( isset( $woo_options['woo_num_cart'] ) ) {

										echo '<input type="number" id="recomendo_woo_num_cart" name="recomendo_woo_options[woo_num_cart]" value="' . $woo_options['woo_num_cart'] . '"/>';
									} else {
										echo '<input type="number" id="recomendo_woo_num_cart" name="recomendo_woo_options[woo_num_cart]" value="3"/>';
									}

									?>
								</td>
							</tr>



							<tr>
								<th scope="row">
									<label for="recomendo_woo_cart_title"><span>Cart Recommendations Title</span></label>
								</th>
								<td>
									<?php
									if ( isset( $woo_options['woo_cart_title'] ) ) {

										echo '<textarea id="recomendo_woo_cart_title" name="recomendo_woo_options[woo_cart_title]">' . $woo_options['woo_cart_title'] . '</textarea>';
									} else {
										echo '<textarea id="recomendo_woo_cart_title" name="recomendo_woo_options[woo_cart_title]">Usually bought together</textarea>';
									}

									?>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="recomendo_woo_exclude_outofstock"><span> Out of Stock Products</span></label>
								</th>
								<td>
									<?php
									if ( isset( $woo_options['woo_exclude_outofstock'] ) ) {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_exclude_outofstock]" value="yes" checked/>Exclude from Recommendations</label><br>';
									} else {
										echo '<label><input type="checkbox" id="recomendo_woo_options" name="recomendo_woo_options[woo_exclude_outofstock]" value="yes" />Exclude from Recommendations</label><br>';
									}
									?>
								</td>

							<tr>
								<th scope="row">
									<label for="recomendo_woo_onsale_relevance"><span> On Sale Products Relevance</span></label>
								</th>
								<td>
									<span class="recomendo-range-title">None</span>
									<span class="recomendo-range-title">Neutral</span>
									<span class="recomendo-range-title">All</span>
									<?php

									if ( isset( $woo_options['woo_onsale_relevance'] ) ) {
										echo '<input type="range" id="recomendo_woo_options" name="recomendo_woo_options[woo_onsale_relevance]" min="-1" max="3" step="1" value="' .  $woo_options['woo_onsale_relevance'] . '" list="tickmarks" />';
									} else {
										echo '<input type="range" id="recomendo_woo_options" name="recomendo_woo_options[woo_onsale_relevance]" min="-1" max="3" step="1" value="1" list="tickmarks"/>';
									}
									?>
									<datalist id="tickmarks">
										<option value="-1">
										<option value="0">
										<option value="1">
										<option value="2">
										<option value="3">
									</datalist>
								</td>

							</tr>

							<tr>
								<th scope="row">
									<label for="recomendo_woo_featured_relevance"><span> Featured Products Relevance</span></label>
								</th>
								<td>
									<span class="recomendo-range-title">None</span>
									<span class="recomendo-range-title">Neutral</span>
									<span class="recomendo-range-title">All</span>
									<?php
										if ( isset( $woo_options['woo_featured_relevance'] ) ) {
											echo '<input type="range" id="recomendo_woo_options" name="recomendo_woo_options[woo_featured_relevance]" min="-1" max="3" step="1" value="' .  $woo_options['woo_featured_relevance'] . '" list="tickmarks" />';
										} else {
											echo '<input type="range" id="recomendo_woo_options" name="recomendo_woo_options[woo_featured_relevance]" min="-1" max="3" step="1" value="1" list="tickmarks" />';
										}
									?>
								</td>
							</tr>


						</tbody>
					</table>
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
					</p>
				</form>
			</div>
		<?php endif; ?>
    <?php endif; ?>

    <?php if ( Recomendo_Admin::is_authorized() ) : ?>
        <div class="recomendo-postbox">
            <h2><?php esc_html_e( 'System Status', 'admin-screen' ); ?></h2>
            <table class="recomendo-system-status" cellspacing="0" cellpadding="0">
                <?php if ( Recomendo_Admin::is_configured() ) : ?>
                    <tr>
                        <td><?php _e( 'Event Server:', 'admin-screen' ); ?></td>
                        <td>
                            <?php
                            if ( Recomendo_Plugin::is_event_server_up() == 1 ) {
                                printf( '<code class="status-good">%s</code>', esc_html_x( 'Connection OK', 'backend', 'admin-screen' ) );
                            } elseif  ( Recomendo_Plugin::is_event_server_up() == 0 ) {
                                printf( '<code class="status-bad">%s</code> ', esc_html_x( 'Connection DOWN', 'backend', 'admin-screen' ) );
                                printf( esc_html_x( 'Servers not reachable. Please contact support.', 'backend', 'admin-screen' ) );
                            } else  {
                                printf( '<code class="status-okay">%s</code> ', esc_html_x( 'Connection UP', 'backend', 'admin-screen' ) );
                                printf( esc_html_x( 'Event server has no data yet. Data will be copied automatically. Please check again in 30 minutes.', 'backend', 'admin-screen' ) );

                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e( 'Recommendation Server:', 'admin-screen' ); ?></td>
                        <td>
                            <?php

                            if ( Recomendo_Plugin::is_prediction_server_up() == 1 ) {
                                _e( '<code class="status-good">Connection OK</code>', 'admin-screen' );
                            } elseif (  Recomendo_Plugin::is_prediction_server_up() == 0 and Recomendo_Plugin::is_event_server_up() == 1) {
                                _e( '<code class="status-okay">Connection DOWN</code> Recomendo servers do not have any data yet. Please check again in 30 minutes.', 'admin-screen' );
                            }  elseif ( Recomendo_Plugin::is_prediction_server_up() == 0 ) {
                                _e( '<code class="status-bad">Connection DOWN</code> Servers not reachable. Please contact support.', 'admin-screen' );
                            } elseif ( Recomendo_Plugin::is_prediction_server_up() == 2 ) {
                                _e( '<code class="status-bad">Connection UP</code> You have reached your monthly API quota limit. Please contact support to upgrade your subscription.', 'admin-screen' );
                            }

                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td><?php _e( 'Curl Support:', 'admin-screen' ); ?></td>
                    <td>
                    <?php
                        if ( Recomendo_Admin::is_curl_installed() ) {
                            _e( '<code class="status-good">Yes</code>', 'admin-screen' );
                        } else {
                            echo sprintf( __( '<code class="status-bad">No</code> Curl is required for communicating with Recomendo servers.<br><span class="recomendo-tip">Please contact your hosting provider.</span>', 'admin-screen' ), 'https://codex.wordpress.org/Changing_File_Permissions' );
                        }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td><?php _e( 'WP Memory Limit:', 'admin-screen' ); ?></td>
                    <td>
                    <?php
                        $memory = wp_convert_hr_to_bytes( @ini_get( 'memory_limit' ) );
                        $tip = sprintf( __( '<br><span class="recomendo-tip">See <a href="%1$s" target="_blank" rel="noopener noreferrer">increasing memory allocated to PHP</a> or contact your hosting provider.</span>', 'admin-screen' ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' );

                        if ( $memory < 67108864 ) {
                            echo sprintf( __( '<code class="status-bad">%1$s</code> Minimum value is <strong>64 MB</strong>. <strong>128 MB</strong> is recommended. Up to <strong>256 MB</strong> may be required to copy all your data to Recomendo servers.', 'admin-screen' ), size_format( $memory ) );
                            echo $tip;
                        }
                        else if ( $memory < 134217728 ) {
                            echo sprintf( __( '<code class="status-okay">%1$s</code> Current memory limit is sufficient for most tasks. However, recommended value is <strong>128 MB</strong>. Up to <strong>256 MB</strong> may be required to copy all your data to Recomendo servers.', 'admin-screen' ), size_format( $memory ) );
                            echo $tip;
                        }
                        else if ( $memory < 268435456 ) {
                            echo sprintf( __( '<code class="status-good">%1$s</code> Current memory limit is sufficient. However, up to <strong>256 MB</strong> may be required to copy all your data to Recomendo servers.', 'admin-screen' ), size_format( $memory ) );
                            echo $tip;
                        }
                        else {
                            echo sprintf( __( '<code class="status-good">%1$s</code> Current memory limit is sufficient.', 'admin-screen' ), size_format( $memory ) );
                        }
                    ?>
                    </td>
                </tr>
                <?php if ( function_exists( 'ini_get' ) ) : ?>
                    <tr>
                        <td><?php _e( 'PHP Time Limit:', 'admin-screen' ); ?></td>
                        <td>
                            <?php
                            $time_limit = ini_get( 'max_execution_time' );
                            $tip = sprintf( __( '<br><span class="recomendo-tip">See <a href="%1$s" target="_blank" rel="noopener noreferrer">increasing max PHP execution time</a> or contact your hosting provider.</span>', 'admin-screen' ), 'http://codex.wordpress.org/Common_WordPress_Errors#Maximum_execution_time_exceeded' );

                            if ( 30 > $time_limit && 0 != $time_limit ) {
                                echo sprintf( __( '<code class="status-bad">%1$s</code> Minimum value is <strong>30</strong>. <strong>60</strong> is recommended. Up to <strong>300</strong> seconds may be required to copy all your data to Recomendo servers.', 'admin-screen' ), $time_limit );
                                echo $tip;
                            }
                            else if ( 60 > $time_limit && 0 != $time_limit ) {
                                echo sprintf( __( '<code class="status-okay">%1$s</code> Current time limit is sufficient for most tasks. However, recommended value is <strong>60</strong>. Up to <strong>300</strong> seconds may be required to copy all your data to Recomendo servers.', 'admin-screen' ), $time_limit );
                                echo $tip;
                            }
                            else if ( 300 > $time_limit && 0 != $time_limit ) {
                                echo sprintf( __( '<code class="status-good">%1$s</code> Current time limit is sufficient. However, up to <strong>300</strong> seconds may be required to copy all your data to Recomendo servers.', 'admin-screen' ), $time_limit );
                                echo $tip;
                            }
                            else if ( 300 < $time_limit && 0 != $time_limit ) {
                                echo sprintf( __( '<code class="status-good">%1$s</code> Current time limit is sufficient.', 'admin-screen' ), $time_limit );
                            } else {
                                echo sprintf( __( '<code class="status-good">unlimited</code> Current time limit is sufficient.', 'admin-screen' ), $time_limit );
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
	<?php endif; ?>
</div>
