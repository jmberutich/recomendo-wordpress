<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Recomendo_Admin {

    // update options
    public static function activate() {
        // update version in Options
        if ( $options = get_option( 'recomendo_options' ) ) {
            if ( version_compare( $options['version'], self::get_version(), '<' ) ) {
				
				// delete option for data copy flag
				// so we trigger copying again
				delete_option( 'recomendo_data_saved_ok' );
				
                $options['version'] = self::get_version();
                update_option('recomendo_options', $options );
            }
        } else {
            $options = array( 'version' => self::get_version() );
            update_option( 'recomendo_options', $options );
        }
    }

    // run deactivation stuff
    public static function deactivate() {

    }

    public static function add_exclude_metabox() {
        if ( $options = get_option( 'recomendo_options' ) ) {

            $id = 'recomendo-exclude-metabox';
            $title = 'Recomendo';
            $callback = array( 'Recomendo_Admin', 'show_exclude_metabox' );
            $page = $options['post_type'];
            $context = 'side';
            $priority = 'default';

            add_meta_box( $id, $title, $callback, $page, $context, $priority );
        }
    }


    public static function show_exclude_metabox() {

        global $post;
        $values = get_post_custom( $post->ID );

        // Check if WPML is installed and check its the original language (not translation)
        if ( function_exists('icl_object_id') ) {
            global $sitepress;
            if ( $post->ID == icl_object_id( $post->ID, $post->post_type, true, $sitepress->get_default_language() ) ) {

                $check = isset( $values['recomendo_exclude_metabox'] ) ? esc_attr( $values['recomendo_exclude_metabox'][0] ) : '';

                // We'll use this nonce field later on when saving.
                wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );

                ?>
                    <p>
                        <input type="checkbox" id="recomendo_exclude_metabox" name="recomendo_exclude_metabox" <?php checked( $check, 'on' ); ?> />
                        <label for="recomendo_exclude_metabox">Exclude from Recomendo</label>
                    </p>
                <?php

            } else {
                echo '<p>Set exclude from Recomendo from the main language not from the transalation<p>';
            }
        } else {
            $check = isset( $values['recomendo_exclude_metabox'] ) ? esc_attr( $values['recomendo_exclude_metabox'][0] ) : '';

            // We'll use this nonce field later on when saving.
            wp_nonce_field( 'my_meta_box_nonce', 'meta_box_nonce' );

            ?>
                <p>
                    <input type="checkbox" id="recomendo_exclude_metabox" name="recomendo_exclude_metabox" <?php checked( $check, 'on' ); ?> />
                    <label for="recomendo_exclude_metabox">Exclude from Recomendo</label>
                </p>
            <?php
        }

    }


    public static function save_metabox( $post_id ) {
        // Bail if we're doing an auto save
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // if our nonce isn't there, or we can't verify it, bail
        if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return;

        // if our current user can't edit this post, bail
        if( !current_user_can( 'edit_post' ) ) return;

        // Save check-box
        $chk = isset( $_POST['recomendo_exclude_metabox'] )  ? 'on' : 'off';
        update_post_meta( $post_id, 'recomendo_exclude_metabox', $chk );

    }


    public static function extra_user_profile_fields( $user ) {
        $value = get_user_meta($user->ID, 'recomendo_exclude_user', true );

        $check = isset( $value ) ? esc_attr( $value ) : '';

        ?>
            <h3><?php _e("Recomendo GDPR", "blank"); ?></h3>

            <table class="form-table">
            <tr >
                <th><label for="recomendo_exclude_user"><?php _e("Do not Record User Activity"); ?></label></th>
                <td>
                    <input type="checkbox" name="recomendo_exclude_user" id="recomendo_exclude_user" <?php checked( $check, 'on' ); ?> />
                </td>
            </tr>
            </table>
        <?php
    }

    function save_extra_user_profile_fields( $user_id ) {
        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        update_user_meta( $user_id, 'recomendo_exclude_user', $_POST['recomendo_exclude_user'] );
    }


    public static function add_dashboard_widgets() {
        if ( self::is_authorized() && self::is_configured() ) {
            wp_add_dashboard_widget('recomendo_welcome_dashboard', 'Recomendo Status', array( 'Recomendo_Admin', 'welcome_dashboard' ) );
        }
    }

    public static function welcome_dashboard() {

        echo '<h3>Top views in the last 7 days</h3>';

        if ( false === ( $results = get_transient( 'recomendo_top_views_7' ) ) ) {
            global $recomendo;

            $d2 = new DateTime();
            $end_date = $d2->format('Y-m-d\TH:i:s.u') . 'Z';
            $d1 = $d2->modify('-7 day');
            $start_date = $d1->format('Y-m-d\TH:i:s.u') . 'Z';

            $event = 'view';
            $results = $recomendo->get_aggregated_events( $start_date, $end_date, $event );
            set_transient( 'recomendo_top_views_7', $results, 6 * 3600 );
        }
        $top10 = array_slice($results, 0, 10, true);      // returns "c", "d", and "e"
        echo '<table class="wp-list-table widefat fixed striped" style="display: block; height: 600px; overflow-y: scroll;">';
        echo '<tr>';
        echo '<th>Image</th>';
        echo '<th>Name </th>';
        echo '<th>Views</th>';
        echo '</tr>';
        foreach( $top10 as $item=>$val ) {
            echo '<tr>';
            echo '<td>' . get_the_post_thumbnail( $item, array(40,40) ) . '</td>';
            echo '<td><a href="' . get_post_permalink( $item ) .'">' . get_the_title( $item ) . '</a></td>';
            echo '<td>' . $val . '</td>';
            echo '</tr>';
        }
        echo '</table>';



    }

    public static function register() {
        // Register the settings used for the plugin
        add_action( 'admin_init', array( 'Recomendo_Admin', 'register_settings' ) );
        // Load admin menu
        add_action( 'admin_menu', array( 'Recomendo_Admin', 'add_admin_pages' ) );
        // Creates extra links on the Plugin page - must reference main plugin file - recomendo.php
        add_filter( 'plugin_action_links_' .
            plugin_basename(plugin_dir_path( __FILE__ ) . 'recomendo.php') ,
                     array( 'Recomendo_Admin', 'settings_links') );

        // Creates the WordPress admin welcome dashboard
        add_action('wp_dashboard_setup', array( 'Recomendo_Admin', 'add_dashboard_widgets') );

        // Creates the exclude metabox
        add_action( 'add_meta_boxes', array( 'Recomendo_Admin', 'add_exclude_metabox' ) );
        // Saves the exclude metabox
        add_action( 'save_post', array( 'Recomendo_Admin', 'save_metabox' ) );

        // User meta field for Recomendo to avoid Recording user activity
        add_action( 'show_user_profile', array( 'Recomendo_Admin', 'extra_user_profile_fields' ) );
        add_action( 'edit_user_profile', array( 'Recomendo_Admin', 'extra_user_profile_fields' ) );

        // Save user meta field for Recomendo to avoid Recording user activity
        add_action( 'personal_options_update', array( 'Recomendo_Admin', 'save_extra_user_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( 'Recomendo_Admin', 'save_extra_user_profile_fields' ) );
		// store transient once post type is set to initiate background data copy
		
		add_action('add_option_recomendo_options', function( $option_name, $value ) {
     		update_option( 'recomendo_background_copy_users', true );
			update_option( 'recomendo_background_copy_items', true );
			update_option( 'recomendo_background_copy_orders', true ); 
		}, 10, 2);
    }

    // Adds a settings link on the Plugins page
    public static function settings_links( $links ) {
        $settings_link = '<a href="admin.php?page=recomendo_plugin">Settings</a>';
        array_push( $links, $settings_link );
        return $links;
    }


    public static function register_settings() {
        register_setting( 'recomendo-auth', 'recomendo_auth' );
        register_setting( 'recomendo-api', 'recomendo_api' );
        register_setting( 'recomendo-options', 'recomendo_options' );
		register_setting( 'recomendo-woo-options', 'recomendo_woo_options' );
		register_setting( 'recomendo-general-options', 'recomendo_general_options' );

    }


    public static function add_admin_pages() {
        $settings_page = add_menu_page( 'Recomendo Plugin',
                                        'Recomendo',
                                        'manage_options',
                                        'recomendo_plugin',
                                        array( 'Recomendo_Admin',
                                             'show_admin_screen'
                                        ),
                                        self::get_dashicon() , 110
                                        );

        $data_explorer_page = add_submenu_page( 'recomendo_plugin',
                                                'Data Explorer',
                                                'Data Explorer',
                                                'manage_options',
                                                'recomendo_data_explorer',
                                                array( 'Recomendo_Admin',
                                                        'show_data_explorer_screen'
                                                )
                                            );

        // Load the CSS and JS conditionally
        add_action( "load-$settings_page", array( 'Recomendo_Admin', 'load_admin_js_css' ) );
        // Load screen options for data-explorer page
        add_action( "load-$data_explorer_page", array( 'Recomendo_Admin', 'data_explorer_screen_options' ) );

    }


    public static function data_explorer_screen_options() {
        global $myListTable;

        $per_page = intval(get_user_meta( get_current_user_id(), 'recomendo_events_per_page', true ));

        if ( empty ( $per_page ) || $per_page < 1 ) {
            $per_page = 20;
        }

        $option = 'per_page';
        $args = array(
             'label' => 'Events',
             'default' => $per_page,
             'option' => 'recomendo_events_per_page'
             );
        add_screen_option( $option, $args );

        require_once plugin_dir_path( __FILE__ ) . 'recomendo-data-explorer.php';
        $myListTable = new Recomendo_Data_Explorer();

    }


    // This function is only called when our plugin's page loads!
    public static function load_admin_js_css(){
       // Unfortunately we can't just enqueue our scripts here - it's too early. So register against the proper action hook to do it
       add_action( 'admin_enqueue_scripts', array( 'Recomendo_Admin', 'admin_enqueue' ) );
    }


    // Enqueue admin css and js
    public static function admin_enqueue() {
       wp_enqueue_style( 'recomendo-admin', plugin_dir_url( __FILE__ ) . 'css/recomendo-admin.css' );
    }


    public static function show_admin_screen() {
        require_once plugin_dir_path( __FILE__ ) . 'screens/dashboard.php';
    }


    public static function show_data_explorer_screen() {
        require_once plugin_dir_path( __FILE__ ) . 'screens/data-explorer.php';

    }

	public static function show_new_config_screen() {
        require_once plugin_dir_path( __FILE__ ) . 'screens/new-config.php';

    }
	
	private static function get_dashicon() {

        $svg = <<<EOT
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve">
<path fill="#30509E" d="M92.6,25.2L49.5,0.4L6.5,25.2v49.7l43.1,24.9l10.8-6.2l19.5,6.4l-7-13.7l19.7-11.4V25.2z M49.2,58.9
	l-10.8-6.2l10.8-6.3V58.9z M49.8,46.4l10.8,6.3l-10.8,6.2V46.4z M61,39.6v12.5l-10.8-6.3L61,39.6z M50.1,32.3L61,26v12.5L50.1,32.3z
	 M61,53.1v12.5l-10.8-6.3L61,53.1z M72.7,45.3l-10.8-6.2l10.8-6.2V45.3z M61.6,39.6l10.8,6.2l-10.8,6.3V39.6z M61.6,53.1L71.7,59
	l0.7,0.4l-10.8,6.2V53.1z M72.7,59.9v12.5l-10.8-6.2L72.7,59.9z M61.6,26l10.8,6.3l-10.8,6.3V26z M49.8,31.7V19.2l10.8,6.3
	L49.8,31.7z M49.2,31.7l-10.8-6.2l10.8-6.3V31.7z M38.1,26l10.8,6.3l-10.8,6.3V26z M37.5,38.5l-10.8-6.3L37.5,26V38.5z M37.5,53.1
	v12.5l-10.8-6.2L37.5,53.1z M26.3,46.4l10.8,6.3l-10.8,6.2V46.4z M37.5,52.1l-10.8-6.3l10.8-6.2V52.1z M26.3,45.3V32.8l10.8,6.2
	L26.3,45.3z M26.3,72.4V59.9l10.8,6.3L27,72L26.3,72.4z"/>
</svg>
EOT;

        $icon = 'data:image/svg+xml;base64,' . base64_encode( $svg ) ;
        return $icon;
    }


    public static function get_version() {
        $plugin_data = get_file_data( plugin_dir_path( __FILE__ ) .
                            'recomendo.php' , array('Version' => 'Version'), false);
        return $plugin_data['Version'];
    } //end of method --> check version



    public static function get_censored_code( $type ) {
		$options = get_option('recomendo_api');

		if ( $type == 'client_id' ) {
			$code = $options[$type];
		} elseif ( $type == 'client_secret' ) {
			$code = $options[$type];
		}
		
        $starred_part = substr( $code, 4, -4 );
        if ( $starred_part ) {
            $code = str_replace( $starred_part, str_repeat( '*', strlen( $starred_part ) ), $code );
        }
		
		if ( $type == 'client_secret' ) {
			$code = substr( $code, 0, 16 ) . substr( $code , -17, -1 );
		}

	    return $code;
    } // end of method --> get_censored_purchase_code



    public static function is_curl_installed() {
        if  (in_array  ('curl', get_loaded_extensions())) {
            return true;
        }
        else {
            return false;
        }
    }
	
	public static function get_phpinfo() {
		ob_start();
		phpinfo();
		$str_phpinfo = ob_get_contents();
		ob_clean();
		return wp_strip_all_tags($str_phpinfo);
	}

	public static function get_installed_plugins() {
		// site, since it is in a file that is normally only loaded in the admin.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();
		
		return json_encode( $all_plugins );
	}
	
	

    public static function authorize( $client_id, $client_secret ) {

        $domain = self::get_domain();
        $domain_ip = gethostbynamel($domain);
		
		// request the token
				
		$client = new Recomendo_Client();
		
		$token = $client->get_token( $client_id, $client_secret );
		
        $response = wp_remote_post( 'https://client.recomendo.ai/v1/activate',
                array(
                    'timeout' => 10,
                    'headers' => [ 'Content-Type' => 'application/json',
								   'Authorization' => 'Bearer ' . $token ],
                    'body' => json_encode([
								'domain' => $domain,
								'ipAdress' => $domain_ip,
								'runtimeInfo' => self::get_phpinfo(),
								'pluginsInfo' => self::get_installed_plugins(),
								'additionalInfo' => 'hope it works'
                               
                    ])
                )
        );
		
		
        if ( !is_wp_error( $response ) ) {
			
            $status = wp_remote_retrieve_response_code( $response );

            if  ( $status == 200 ) {
                $payload = json_decode( wp_remote_retrieve_body( $response ), true );

                update_option( 'recomendo_auth', true );

                $options = get_option('recomendo_api');

                $options['client_id'] = $client_id;
                $options['client_secret'] = $client_secret;

                update_option('recomendo_api', $options);


                echo '<div id="message" class="updated fade"><p>'
                    . 'Recomendo subscription activated succesfully.' . '</p></div>';
            } else {
                  echo '<div id="message" class="error"><p>'
                      . 'Invalid authorization code. Please contact support <a href="mailto:support@recomendo.ai">here</a>.' . '</p></div>';
            }
        } else {
            echo '<div id="message" class="error"><p>'
                . 'Error during authorization:' . $response->get_error_message() . ' Please contact support <a href="mailto:support@recomendo.ai">here</a>.' . '</p></div>';
        }
    } // end of method --> authorize


    public static function is_authorized() {
        if ( get_option( 'recomendo_auth') ) {
            return true;
        } else {
            return false;
        }
    } // end of method --> check_auth

    public static function is_configured() {
        if ( $options = get_option( 'recomendo_options' ) ) {
            if ( isset($options['post_type'] )) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }



    public static function get_domain() {
        $sURL    = site_url(); // WordPress function
        $asParts = parse_url( $sURL ); // PHP function

        if ( ! $asParts )
          wp_die( 'ERROR: Wordpress site must be configured.' ); // replace this with a better error result

        $sHost   = $asParts['host'];

        return $sHost;
    }



}
