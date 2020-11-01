<?php

//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Recomendo_Plugin {


    function __construct( $options ) {

        $this->options = $options;
        $this->woo_options = get_option( 'recomendo_woo_options' );
        $this->general_options = get_option( 'recomendo_general_options');
        // set up the hooks, actions and register styles, scripts, etc.
        $this->register();
        $this->client = new Recomendo_Client();

        $this->bg_user_copy = new Recomendo_Background_User_Copy();
        $this->bg_item_copy = new Recomendo_Background_Item_Copy();
        $this->bg_order_copy = new Recomendo_Background_Order_Copy();

    } // end of method -> __construct



    public static function is_event_server_up() {

        // static function - need to initialize client
        $client = new Recomendo_Client();

        $response = $client->get_event_server_status();

        $status = wp_remote_retrieve_response_code( $response );
        if  ( $status == 200 ) {
            return 1;
        } elseif ( $status == 404) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $body['message'] == 'Not Found') {
                // server is up but with no data
                return 2;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    } // end of method --> get_event_server_statuss



    public static function is_prediction_server_up() {

        $client = new Recomendo_Client();

        $response = $client->get_prediction_server_status();

        $status = wp_remote_retrieve_response_code( $response );
        if  ( $status == 200 ) {
            return 1;
        } elseif ( $status == 429 ) {
            // quota exceeded
            return 2;
        } else {
            return 0;
        }
    } // end of method --> get_event_server_statuss




    public function register() {

        // Tracks non-registered users with cookies
        add_action( 'init', array( $this, 'set_cookie' ) );
        // Add user to Recomendo
        add_action( 'user_register', array( $this, 'add_user' ) );
        // Delete user from Recomendo
        add_action( 'delete_user', array( $this, 'delete_user' ) );
        // Add item to Recomendo
        add_action( 'publish_' . $this->options['post_type'], array( $this, 'add_item' ), 10, 1);
        // Delete item from Recomendo
        add_action( 'transition_post_status', array( $this, 'delete_item' ), 10, 3 );
        // Record view events
        add_action( 'wp', array( $this, 'record_view_event' ) );
        // Record add_to_cart events
        add_action('woocommerce_add_cart_item_data', array( $this, 'record_add_to_cart_event' ), 10, 2);
        // Record Buy Event
        add_action( 'woocommerce_thankyou', array( $this, 'record_buy_event' ) );
        // Record category_pref
        add_action( 'wp', array( $this, 'record_category_pref' ) );
        // Register and load the widget
        add_action( 'widgets_init', array( $this, 'load_widget' ) );
        // Creates the [recomendo] shortcode
        add_shortcode( 'recomendo', array( $this, 'show_shortcode' ) );
        //Register Recomendo Blocks for Gutenberg
        add_action('init',array( $this, 'register_recomendo_blocks'));
        //Function to retrieve post type
        add_action('wp_ajax_return_posttype_variable', array($this,'return_posttype_variable'));
         //Function to show all templates in current theme
        add_action('wp_ajax_recomendo_all_templates', array($this,'recomendo_all_templates'));
        //Register function so ajax function can listen to this
        add_action('wp_ajax_get_items_progress_background',array($this,'get_items_progress_background'));
        //Register option in database for items progress
        add_option('recomendo_progress_background', 0);
        add_option('recomendo_items_background_completed',0);
        //Register option in database for users progress
        add_option('recomendo_progress_background_users', 0);
        add_option('recomendo_users_background_completed',0);
        //Register option in database for orders progress
        add_option('recomendo_progress_background_orders', 0);
        add_option('recomendo_orders_background_completed',0);
        //Add general scripts
        add_action('admin_enqueue_scripts',array($this,'recomendo_register_scripts'));
        //Callback when Force Data Sync is clicked
        add_action( 'admin_post_accept_force_datasync', array($this,'accept_force_datasync' )); 
        //Action that runs if the plugins has been upated
        add_action('upgrader_process_complete',array( $this,'recomendo_is_updated'), 10, 2);
        // Show recommendations in WooCommerce related products
        if ( isset( $this->woo_options['woo_show_related'] ) )
            add_filter( 'woocommerce_related_products', array( $this, 'show_related_products' ), 10, 1);
        // Show recommendations in the cart 
        if ( isset( $this->woo_options['woo_show_cart'] ) )
            add_action( 'woocommerce_after_cart_table', array( $this, 'show_cart_recommendations'), 10, 1);
        // UPDATE PRODUCT IS FEATURED on  Woocommerce admin panel
        if ( isset( $this->options['post_type'] ) && $this->options['post_type'] == 'product' ) {
                add_action( 'woocommerce_product_set_visibility', array( $this, 'add_item' ), 10, 1 );
        }


    } //end of method


    /**
     * Register Javascripts,CSS and callbacks for the Gutenberg Block
     */
    public function register_recomendo_blocks(){
        if ( ! function_exists( 'register_block_type' ) ) {
            // Gutenberg is not active.
            return;
        }

        wp_register_script(
            'recomendo-block-script',
            plugins_url('/js/index.js',__FILE__),
            array('wp-blocks','wp-element','wp-i18n','wp-components','wp-editor')
        );
        wp_register_style(
            'recomendo-template-style',
            plugins_url( '/css/recomendo-template.css', __FILE__ ),
            array( 'wp-edit-blocks' )
        );
        wp_register_style(
            'recomendo-template-style-editor',
            plugins_url( '/css/recomendo-template-editor.css', __FILE__ ),
            array( 'wp-edit-blocks' )
        );
     
        wp_localize_script( 'recomendo-block-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        
        $postType = $this->options['post_type'];

        if($postType != 'product'){
            $defaultTemplate = 'content-recomendo';
        }
        else{
            $defaultTemplate = 'content-'.$postType;
        }
        register_block_type('recomendo/recomendo-block',array(
            'editor_script' => 'recomendo-block-script',
            'style' => 'recomendo-template-style',
            'editor_style' => 'recomendo-template-style-editor',
            'render_callback'=> array($this, 'show_shortcode'),
            'attributes' => [
                'number' => [
                    'default' => 16
                ],
                'type' => [
                    'default' => 'personalized'
                ],
                'template' => [
                    'default' => $defaultTemplate
                ]
            ]
        ));
    }
    
    /*
    *Enqueue general javascripts files for the plugin
    */ 
   public function recomendo_register_scripts(){
       wp_enqueue_script('recomendo-general-scripts',
                        plugin_dir_url(__FILE__).'js/recomendo-js-general.js', 
                        true
                        );
       wp_localize_script( 'recomendo-general-scripts', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
   }

    public function load_widget() {
        register_widget( 'Recomendo_Widget' );
    }

    //Function that only returns the post_type selected on the Recomendo settings when installed
    public function return_posttype_variable(){
        if(function_exists('WC') && class_exists( 'WooCommerce' ) ){
            $is_woocommerce_active = "true";
            $int = apply_filters( 'loop_shop_columns', $int ); 
                         
            if ( !empty( $int ) ) {                       
                $woo_columns = intVal($int);
            }

        }else{
            $is_woocommerce_active = "false";
        }
        $postType = $this->options['post_type'];

        $array = [
            'post_type' => $postType,
            'is_woocommerce' => $is_woocommerce_active,
           'woo_columns'=> $woo_columns
        ];
        $data = json_encode($array);
        echo $data;
        wp_die();
    }
    
    //Get all templates 
    public function recomendo_all_templates(){
        //get templates in the theme
        $templates = wp_get_theme()->get_page_templates();

   
        //set default template 
        if($this->options['post_type'] == 'product'){
            $array[] = array("value" => 'content-product',   "label" => 'content-product'); 
        }
            $array[] = array("value" => 'content-recomendo',   "label" => 'content-recomendo');

        foreach ( $templates as $template_name => $template_filename )
        {
            //if the templates route  has levels eg: /folder/file.php removes the '/'
            $exploded_name = explode('/',$template_name );
            foreach($exploded_name as $expresion){
                //search for the ones that starts with 'content-'
                if(preg_match('/^(content-)/i', $expresion)){
                  //Search for the element that has .php and if it does, remove it and add to array
                  if (preg_match('/(\.php)$/i', $expresion)) {
                      $new_templateName = substr($expresion,0,-4);
                      $array[] = array("value" => $new_templateName,   "label" => $new_templateName);
                     
                  }
                }
                  
              }

        }
        $data = json_encode($array);
        echo $data;
       wp_die();
    }

    //Function to locate the template given
    public function recomendo_locate_template( $template_name, $template_path = '', $default_path = '' ,$woo_path ='',$woo_theme_path='') {
      
       
        // Set variable to search in recomendo folder of theme.
        if ( ! $template_path ) :
            $template_path = 'recomendo-templates/';
        
        endif;  // Set default plugin templates path.
        if ( ! $default_path ) :
            $default_path = plugin_dir_path( __FILE__ ) . 'recomendo-templates/'; // Path to the template folder
          
        endif;
        if(!$woo_path) : //if WooCoomerce installed, set path to templates folder
            if(function_exists('WC')){
                $woo_path = WC()->plugin_path(__FILE__) . '/templates/';
            }
        endif;   
        //Set path if there is a woocommerce folder in the active theme
        if(!$woo_theme_path) :
            $woo_theme_path = 'woocommerce/';
        endif; 


        // Search template file in theme folder.
        $template = locate_template( array(
            $template_path . $template_name,
            $template_name ) );
           
        if(! $template){
            $template = locate_template($woo_theme_path . $template_name);
        }
        if(! $template){
            $template = $default_path . $template_name;
        }
           
        
        if( ! file_exists( $template )){
            $template = $woo_path . $template_name;
        }

        return apply_filters( 'recomendo_locate_template', $template, $template_name, $template_path, $default_path , $woo_path, $woo_theme_path);
    }


    public function recomendo_get_template( $template_name, $args = array(), $template_path = '', $default_path = '', $woo_path='',$woo_theme_path = '' ) {
        if ( is_array( $args ) && isset( $args ) ) :
            extract( $args );
        endif;
        $template_file = $this->recomendo_locate_template( $template_name, $template_path, $default_path , $woo_path, $woo_theme_path);
       // var_dump($template_file);
        if ( ! file_exists( $template_file ) ) :
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
            return;
        endif;
        include $template_file;
    }

    /**
     * This function runs when datasync is accepted to be forced via Recomendo Admin
     * works with admin_post action hook declared on line 115
     * The action is triggered on dashboard.php
     */
    public function accept_force_datasync(){
        delete_option( 'recomendo_data_saved_ok' );
        //Update option in database for items progress
        update_option('recomendo_progress_background', 0);
        update_option('recomendo_items_background_completed',0);
        //Update option in database for users progress
        update_option('recomendo_progress_background_users', 0);
        update_option('recomendo_users_background_completed',0);
        //Update option in database for orders progress
        update_option('recomendo_progress_background_orders', 0);
        update_option('recomendo_orders_background_completed',0);
        
        $this->copy_data_to_eventserver();
        
        wp_safe_redirect(admin_url('admin.php').'?page=recomendo_plugin');
    }
  
    /**
     * This function runs when WordPress completes its upgrade process
     * It iterates through each plugin updated to see if ours is included
     * @param $upgrader_object Array
     * @param $options Array
     */
    public function recomendo_is_updated($upgrader_object,$options){
        // The path to our plugin's main file
        $our_plugin = plugin_basename( __FILE__ );
        // If an update has taken place and the updated type is plugins and the plugins element exists
        if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        // Iterate through the plugins being updated and check if ours is there
            foreach( $options['plugins'] as $plugin ) {
                if( $plugin == $our_plugin ) {
                    update_option('recomendo_items_background_completed',100);
                    update_option('recomendo_users_background_completed',100);
                    update_option('recomendo_orders_background_completed',100);

                }
            }
        }
    }

    public function detect_crawler() {
        // User lowercase string for comparison.
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if ( is_null( $user_agent ) ) return TRUE;

        // ignore requests from this wordpress server... cron or other plugins
        #if ( $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] ) return TRUE;

        if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $transient = 'recomendo_' . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_X_FORWARDED_FOR'] ;
        } else {
            $transient = 'recomendo_' . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'];
        }

        $cookies_generated = get_transient( $transient );
        if ( $cookies_generated > 1 ) return TRUE;

        // A list of some common words used only for bots and crawlers.
        $bot_identifiers = array(
                                'bot',
                                'slurp',
                                'crawler',
                                'spider',
                                'curl',
                                'facebook',
                                'fetch',
                                'amazon',
                                'wordpress',
                                'wget',
                                'go-http-client',
                                'trustpilot'
                                );

                            // See if one of the identifiers is in the UA string.
        foreach ($bot_identifiers as $identifier) {
            if (strpos($user_agent, $identifier) !== FALSE) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function is_robot_allowed() {

        if ( ! isset( $this->general_options['allow_seo'] ) ) return FALSE;

        // User lowercase string for comparison.
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $allowed_robots = array(
            'google',
            'bing',
            'yahoo',
            'duckduck',
            'baidu',
            'yandex'
        );

        foreach ($allowed_robots as $robot) {
            if (strpos($user_agent, $robot) !== FALSE) {
                return TRUE;
            }
        }

        return FALSE;
    }


    // Tracks non-registered users with cookies
    public function set_cookie() {

        if ( get_current_user_id() == 0 ) {
            if ( !isset($_COOKIE['recomendo-cookie-user'] ) ) {
                $uniq_id= uniqid('recomendo_', true);
                setcookie('recomendo-cookie-user', $uniq_id, time()+60*60*24*365*5, COOKIEPATH, COOKIE_DOMAIN);
                $_COOKIE['recomendo-cookie-user'] = $uniq_id;

                if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                    $transient = 'recomendo_' . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['HTTP_X_FORWARDED_FOR'] ;
                } else {
                    $transient = 'recomendo_' . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'];
                }

                $cookies_generated = get_transient( $transient );

                if ( $cookies_generated ) {
                    $cookies_generated += 1;
                } else {
                    $cookies_generated = 1;
                }

                set_transient( $transient, $cookies_generated, 60 );
                $this->add_user( $uniq_id );
            }
        }
    } // end of method --> set_cookie



    // Creates shortcode [recomendo]
    public function show_shortcode( $atts, $content=null ) {

        $postType = $this->options['post_type'];

        if($postType != 'product'){
            $defaultTemplate = 'content-recomendo';
        }
        else{
            $defaultTemplate = 'content-'.$postType;
        }

        $a = shortcode_atts( array(
         'number' => 16,
         'type' => 'personalized',
         'template' => $defaultTemplate
        ), $atts );

        // get the slug and name from the shortcode template arg
        $template_woo = explode( '-', basename( $a['template'], '.php') );
        $template_slug = $template_woo[0];
        $template_name = implode( '-', array_slice( $template_woo, 1) );
        $template = $a['template'].'.php';

        switch (  strtolower( $a['type'] ) ) {
            case 'personalized' :
                if ( class_exists( 'woocommerce' ))  {
                    WC()->frontend_includes();
                }
                $response = $this->get_user_recommendations( intval( $a['number']));
                break;
            case 'similar' :
                if ( is_singular( $this->options['post_type'] ) ) {
                    if ( class_exists( 'woocommerce' ))  {
                        WC()->frontend_includes();
                    }

                    // Check if WPML is installed and get the id of the original language post (not translation)
                    if ( function_exists('icl_object_id') ) {
                        global $sitepress;
                        $postid = icl_object_id( get_the_ID(), $this->options['post_type'], true, $sitepress->get_default_language() );
                    } else {
                        $postid = get_the_ID();
                    }
                    $response = $this->get_item_recommendations( $postid, intval( $a['number'] ) );
                } else {
                    //echo '<p>Recomendo warning: Similar item recomendations need to be shown on single item pages</p>' ;
                    return;
                }
                break;
            case 'complementary' :
                $itemset_products = array();
                if ( class_exists( 'woocommerce' ))  {

                global $woocommerce;
                WC()->frontend_includes();
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
                WC()->cart = new WC_Cart();
                    foreach (WC()->cart->get_cart() as $cart_item_key => $values) {

                        // Check if WPML is installed and get the id of the original language post (not translation)
                        if (function_exists('icl_object_id')) {
                            global $sitepress;
                            $itemset_products[] = icl_object_id($values['product_id'], 'product', true, $sitepress->get_default_language());
                        } else {
                            $itemset_products[] = $values['product_id'];
                        }
                    }

                    $response = $this->get_itemset_recommendations($itemset_products, intval($a['number']));

                } else {
                    if ( have_posts() ) {
                        while ( have_posts() ) {
                            the_post();
                            // Check if WPML is installed and get the id of the original language post (not translation)
                            if ( function_exists('icl_object_id') ) {
                                global $sitepress;
                                $itemset_products[] = icl_object_id( get_the_ID(), $this->options['post_type'], true, $sitepress->get_default_language() );
                            } else {
                                $itemset_products[] = get_the_ID();
                            }
                        }
                        $response = $this->get_itemset_recommendations( $itemset_products, intval( $a['number'] ) );
                    } else {
                        echo '<p>no posts to show</p>';
                    }
                }
                break;
            case 'trending' :
            if ( class_exists( 'woocommerce' ))  {
                WC()->frontend_includes();
            }
                
                $response = $this->get_trending_items( intval( $a['number']));
                break;

        }
      
        if ( $response != false and array_key_exists( 'itemScores', $response ) ) {
           
            ob_start();
            if ( class_exists( 'woocommerce' ) && ($postType == 'product')) {
               woocommerce_product_loop_start();
                echo '<div class="recomendo-container">';
                foreach ($response['itemScores'] as $i ) {
                    if ( get_post_status ( $i['item'] ) == 'publish' ) {
                        $post_object = get_post( $i['item'] );
                        setup_postdata( $GLOBALS['post'] =& $post_object );
                        //wc_get_template_part( $template_slug, $template_name );
                        $this->recomendo_get_template($template);
                        
                    }
                }
                echo '</div>';
               woocommerce_product_loop_end();
            } else {
                echo '<div class="recomendo-container recomendo">';
                foreach ($response['itemScores'] as $i ) {
                    if ( get_post_status ( $i['item'] ) == 'publish' ) {
                        $post_object = get_post( $i['item'] );
                        setup_postdata( $GLOBALS['post'] =& $post_object );
                        // REPLACE by custom parameter
                        //get_template_part( $template_slug, $template_name );
                       $this->recomendo_get_template($template);
                    }
                }
                echo '</div>';
            }

            wp_reset_postdata();
            $output = ob_get_clean();
            return $output;
        }


    } // end of method --> show_shortcode


    // Shows recommendations in the WooCommerce Related Products area
    public function show_related_products( $args ) {

        global $product;

        $resp = $this->get_item_recommendations( $product->get_id(), intval( $this->woo_options['woo_num_related'] ) );

        if ( $resp != false and array_key_exists( 'itemScores', $resp ) ) {
            $related_products = array();
            foreach ($resp['itemScores'] as $i ) {
                //$related_products[] = wc_get_product( $i['item'] );
                $related_products[] = $i['item'] ;
            }
            $args = $related_products;
        }
        return $args;
    } // end of method --> show_related_products


    // Shows recommendations in the WooCommerce Cart
    public function show_cart_recommendations( $args = array() ) {

        global $woocommerce;

        $defaults = array(
            'posts_per_page' => intval($this->woo_options['woo_num_cart']),
            'title' => $this->woo_options['woo_cart_title']
        );

        $args = wp_parse_args( $args, $defaults );


        if ( ! $woocommerce ) {
            return;
        }

        $itemset_products = array();

        foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

            // Check if WPML is installed and get the id of the original language post (not translation)
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $itemset_products[] = icl_object_id( $values['product_id'], 'product', true, $sitepress->get_default_language() );
            } else {
                $itemset_products[] = $values['product_id'];
            }
        }

        $related_products = array();

        $resp = $this->get_itemset_recommendations( $itemset_products, $args['posts_per_page'] );

        if ( $resp != false and array_key_exists( 'itemScores', $resp ) ) {
            if ( sizeof( $resp['itemScores'] ) > 0 ) {
                foreach ($resp['itemScores'] as $i ) {
                    $related_products[] = $i['item'];
                }
            }
            echo '<section class="related-products">';
            echo '<h3>';
            esc_html_e( $args['title'], 'woocommerce' );
            echo '</h3>';

            woocommerce_product_loop_start();

            foreach ( $related_products as $related_product ) {
                $post_object = get_post( $related_product );
                setup_postdata( $GLOBALS['post'] =& $post_object );
                wc_get_template_part( 'content', 'product' );
            }
            echo '</section>';
            woocommerce_product_loop_end();
            wp_reset_postdata();

        }
    } // end of method --> show_cart_recommendations



    // post ids to exclude set via the metabox or out of stock
    public function get_excluded_items() {

        if ( class_exists( 'woocommerce' ) &&
            $this->options['post_type'] == 'product'  &&
            isset( $this->woo_options['woo_exclude_outofstock'] ) ) {

            $args = array(
                'post_type' => 'product',
                'fields' => 'ids',
                'numberposts' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                      'key' => '_stock_status',
                      'value' => 'outofstock'                    ),
                    array(
                        'key' => 'recomendo_exclude_metabox',
                        'value' => 'on'
                    )
                )
            );

        } else {

            $args = array(
                'post_type' => $this->options['post_type'],
                'fields' => 'ids',
                'numberposts' => -1,
                'meta_key' => 'recomendo_exclude_metabox',
                'meta_value' => 'on'
            );
        }

        $post_ids = get_posts( $args );

        // Remove Singular pages from Recommendations...
        if ( is_singular( $this->options['post_type'] ) ) {
            // Check if WPML is installed and get the id of the original language post (not translation)
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $postid = icl_object_id( get_the_ID(), $this->options['post_type'], true, $sitepress->get_default_language() );
            } else {
                $postid = get_the_ID();
            }

            // add the id of singular page
            $post_ids[] = $postid;
        }

        return $post_ids;

    }


    public function get_excluded_users() {
        return get_users(array('fields' => 'ids', 'meta_key' => 'recomendo_exclude_user', 'meta_value' => 'on'));
    }


    // returns trending items. This is a fallback for other recommendations.
    // when no user, item or complementary recommendations are found, trending items are returned
    public function get_trending_items( $number ) {

        //ignore bots and crawlers
        if ( $this->detect_crawler() and ! $this->is_robot_allowed() ) return false;

        $query = array(
            'num' => $number
        );

        $query['blacklistItems'] = $this->get_excluded_items();

        if ( isset ( $this->general_options['expire_date'] ) &&
            $this->general_options['expire_date'] != 0 ) {

                $after_date = date('c', strtotime('-' . $this->general_options['expire_date'] . ' days' ) );
                $query['dateRange'] = array(
                    'name' => 'published_date',
                    'after' => $after_date
                );

        }

        $response = $this->client->send_query( $query );

        // check that the response is not WP_error
        if ( !is_wp_error( $response ) ) {
            $body = $this->client->get_json( $response );
            if ( ! empty( $body ) ) {
                // Check if WPML is installed and get the id of the original language post (not translation)
                if ( function_exists('icl_object_id') ) {
                    global $sitepress;

                    $result = array();
                    foreach ($body['itemScores'] as $key => $i ) {
                        $postid = icl_object_id( $i['item'], $this->options['post_type'], true, $sitepress->get_current_language() );
                        $result['itemScores'][$key]['item'] = strval( $postid );
                        $result['itemScores'][$key]['score'] = $i['score'];
                    }
                } else {
                    $result = $body;
                }
            } else {
                // empty results
                error_log( "[RECOMENDO] --- Trending items recommendations returned no results." );
                $result = false;
            }
        } else {
            // If the request has failed, show the error message
            error_log( "[RECOMENDO] --- Error getting trending items recommendations." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            $result = false;
        }

        return $result;

    }  // end of method --> get_trending_items


    private function map_range_input_to_bias( $value ) {
        if ( intval($value) == -1 ) return 0.0; // exclude
        if ( intval($value) == 0 ) return 0.5; // less importance
        if ( intval($value) == 1 ) return 1.0;  // neutral
        if ( intval($value) == 2 ) return 1.5; // more importance
        if ( intval($value) == 3) return -1.0;  // Include only
    }

    // Recomendo user based recommendations
    // returns post ids and relevance score
    public function get_user_recommendations( $number ) {

        //ignore bots and crawlers
        if ( $this->detect_crawler() and ! $this->is_robot_allowed() ) return false;

        if ( get_current_user_id() == 0 ) {
            $userid = $_COOKIE['recomendo-cookie-user'];
        } else {
            $userid = get_current_user_id();
        }

        $fields = array();

        if ( isset(  $this->woo_options['woo_onsale_relevance'] ) ) {

                $on_sale_bias = $this->map_range_input_to_bias(
                    $this->woo_options['woo_onsale_relevance'] );

                // we ignore neutral and dont add it to the query
                if ( $on_sale_bias != 1.0 ) {
                    // less importance boost "no" instead
                    if ( $on_sale_bias == 0.5 ) {
                        $on_sale_args = array(
                            'name' => 'is_on_sale',
                            'values' => ['no'],
                            'bias' => 1.5
                        );
                    } else {
                        $on_sale_args = array(
                            'name' => 'is_on_sale',
                            'values' => ['yes'],
                            'bias' => $on_sale_bias
                        );
                    }

                    $fields[] = $on_sale_args;
                }

        }

        if ( isset(  $this->woo_options['woo_featured_relevance'] ) ) {
                $featured_bias = $this->map_range_input_to_bias(
                    $this->woo_options['woo_featured_relevance'] );

                // we ignore neutral and dont add it to the query
                if ( $featured_bias != 1.0 ) {
                    // less importance boost "no" instead
                    if ( $featured_bias == 0.5 ) {
                        $featured_args = array(
                            'name' => 'is_featured',
                            'values' => ['no'],
                            'bias' => 1.5
                        );
                    } else {
                        $featured_args = array(
                            'name' => 'is_featured',
                            'values' => ['yes'],
                            'bias' => $featured_bias
                        );

                    }

                    $fields[] = $featured_args;

                }

        }

        $query = array(
            'user' => $userid,
            'num' => $number,
            'fields' => $fields
        );

        if ( isset ( $this->general_options['expire_date'] ) &&
            $this->general_options['expire_date'] != 0 ) {

                $after_date = date('c', strtotime('-' . $this->general_options['expire_date'] . ' days' ) );
                $query['dateRange'] = array(
                    'name' => 'published_date',
                    'after' => $after_date
                );
        }

        $query['blacklistItems'] = $this->get_excluded_items();

        $response = $this->client->send_query( $query );

        // check that the response is not WP_error
        if ( !is_wp_error( $response ) ) {
            $body = $this->client->get_json( $response );
            if ( ! empty( $body ) ) {
                // Check if WPML is installed and get the id of the original language post (not translation)
                if ( function_exists('icl_object_id') ) {
                    global $sitepress;

                    $result = array();
                    foreach ($body['itemScores'] as $key => $i ) {
                        $postid = icl_object_id( $i['item'], $this->options['post_type'], true, $sitepress->get_current_language() );
                        $result['itemScores'][$key]['item'] = strval( $postid );
                        $result['itemScores'][$key]['score'] = $i['score'];
                    }
                } else {
                    $result = $body;
                }
            } else {
                // empty results
                error_log( "[RECOMENDO] --- User recommendations returned no results." );
                $result = false;
            }
        } else {
            // If the request has failed, show the error message
            error_log( "[RECOMENDO] --- Error getting user recommendations." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            $result = false;
        }



        return $result;

    }  // end of method --> get_user_recommendations


    // Recomendo Similar Item Recommendations
    // returns post ids and relevance score
    public function get_item_recommendations( $item, $number ) {

        //ignore bots and crawlers
        if ( $this->detect_crawler() and ! $this->is_robot_allowed() ) return false;


        $fields = array();

        // Check if WPML is installed and get the id of the original language post (not translation)
        if ( function_exists('icl_object_id') ) {
            global $sitepress;
            if ( $sitepress->get_default_language() != $sitepress->get_current_language() ) {
                $item = icl_object_id( $item, $this->options['post_type'], true, $sitepress->get_default_language() );
            }
        }

        // Apply the Similar Categories Bias/Boost to the query
        if ( isset(  $this->general_options['similar_categories_relevance'] ) ) {

            $same_categories_bias = $this->map_range_input_to_bias(
                    $this->general_options['similar_categories_relevance'] );

            // we ignore neutral and dont add it to the query
            if ( $same_categories_bias != 1.0 ) {

                $categories = array();

                // get the categories to boost
                if ( class_exists( 'woocommerce' ) ) {
                    $terms = get_the_terms( $item, 'product_cat' );
                } else {
                    $terms = get_the_terms( $item, 'category' );
                }

                if (is_array($terms) or is_object($terms)) {
                    foreach ($terms as $term) {
                        $categories[] = (string) $term->term_id;
                    }

                    $similar_categories_args = array(
                        'name' => 'categories',
                        'values' => $categories,
                        'bias' => $same_categories_bias
                    );

                    $fields[] = $similar_categories_args;
                }
            }


        }

        // Apply the Similar Tags Bias/Boost to the query
        if ( isset(  $this->general_options['similar_tags_relevance'] ) ) {

            $same_tags_bias = $this->map_range_input_to_bias(
                    $this->general_options['similar_tags_relevance'] );

            // we ignore neutral and dont add it to the query
            if ( $same_tags_bias != 1.0 ) {

                $tags = array();

                // get the tags to boost
                if ( class_exists( 'woocommerce' ) ) {
                    $taglist = get_the_terms( $item, 'product_tag' );
                } else {
                    $taglist = get_the_tags( $item );
                }


                if (is_array($taglist) or is_object($taglist)) {
                    foreach ($taglist as $tagitem) {
                        $tags[] = (string) $tagitem->term_id;
                    }

                    $similar_tags_args = array(
                        'name' => 'tags',
                        'values' => $tags,
                        'bias' => $same_tags_bias
                    );

                    $fields[] = $similar_tags_args;

                }

            }


        }

        // Apply Boost-Bias to Items on Sale
        if ( isset(  $this->woo_options['woo_onsale_relevance'] ) ) {

            $on_sale_bias = $this->map_range_input_to_bias(
                $this->woo_options['woo_onsale_relevance'] );

            // we ignore neutral and dont add it to the query
            if ( $on_sale_bias != 1.0 ) {
                // less importance boost "no" instead
                if ( $on_sale_bias == 0.5 ) {
                    $on_sale_args = array(
                        'name' => 'is_on_sale',
                        'values' => ['no'],
                        'bias' => 1.5
                    );
                } else {
                    $on_sale_args = array(
                        'name' => 'is_on_sale',
                        'values' => ['yes'],
                        'bias' => $on_sale_bias
                    );
                }

                $fields[] = $on_sale_args;
            }

        }

        // Apply Boost-Bias to Featured Items
        if ( isset(  $this->woo_options['woo_featured_relevance'] ) ) {
            $featured_bias = $this->map_range_input_to_bias(
                $this->woo_options['woo_featured_relevance'] );

            // we ignore neutral and dont add it to the query
            if ( $featured_bias != 1.0 ) {
                // less importance boost "no" instead
                if ( $featured_bias == 0.5 ) {
                    $featured_args = array(
                        'name' => 'is_featured',
                        'values' => ['no'],
                        'bias' => 1.5
                    );
                } else {
                    $featured_args = array(
                        'name' => 'is_featured',
                        'values' => ['yes'],
                        'bias' => $featured_bias
                    );

                }

                $fields[] = $featured_args;
            }
        }

        $query = array(
            'item'=> $item,
            'num' => $number,
            'fields'=> $fields
        );

        if ( isset ( $this->general_options['expire_date'] ) &&
            $this->general_options['expire_date'] != 0 ) {

                $after_date = date('c', strtotime('-' . $this->general_options['expire_date'] . ' days' ) );
                $query['dateRange'] = array(
                    'name' => 'published_date',
                    'after' => $after_date
                );

        }

        $query['blacklistItems'] = $this->get_excluded_items();

        $response = $this->client->send_query( $query );


        // check that the response is not WP_error
        if ( !is_wp_error( $response ) ) {
            $body = $this->client->get_json( $response );
            if ( ! empty( $body ) ) {
                // Check if WPML is installed and get the id of the original language post (not translation)
                if ( function_exists('icl_object_id') ) {
                    global $sitepress;

                    $result = array();
                    foreach ($body['itemScores'] as $key => $i ) {
                        $postid = icl_object_id( $i['item'], $this->options['post_type'], true, $sitepress->get_current_language() );
                        $result['itemScores'][$key]['item'] = strval( $postid );
                        $result['itemScores'][$key]['score'] = $i['score'];
                    }
                } else {
                    $result = $body;
                }

            } else {
                // empty results
                error_log( "[RECOMENDO] --- Item recommendations returned no results." );
                $result = false;
            }
        } else {
            // If the request has failed, show the error message
            error_log( "[RECOMENDO] --- Error getting item recommendations." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            $result = false;
        }


        return $result;

    } // end of method --> get_item_recommendations


    // recommendations for Cart type pages
    // returns post ids and relevance score
    public function get_itemset_recommendations( array $items, $number ) {

        //ignore bots and crawlers
        if ( $this->detect_crawler() and ! $this->is_robot_allowed() ) return false;


        $fields = array();

        if ( isset(  $this->woo_options['woo_onsale_relevance'] ) ) {

            $on_sale_bias = $this->map_range_input_to_bias(
                $this->woo_options['woo_onsale_relevance'] );

            // we ignore neutral and dont add it to the query
            if ( $on_sale_bias != 1.0 ) {
                // less importance boost "no" instead
                if ( $on_sale_bias == 0.5 ) {
                    $on_sale_args = array(
                        'name' => 'is_on_sale',
                        'values' => ['no'],
                        'bias' => 1.5
                    );
                } else {
                    $on_sale_args = array(
                        'name' => 'is_on_sale',
                        'values' => ['yes'],
                        'bias' => $on_sale_bias
                    );
                }

                $fields[] = $on_sale_args;
            }

        }

        if ( isset(  $this->woo_options['woo_featured_relevance'] ) ) {
                $featured_bias = $this->map_range_input_to_bias(
                    $this->woo_options['woo_featured_relevance'] );

                // we ignore neutral and dont add it to the query
                if ( $featured_bias != 1.0 ) {
                    // less importance boost "no" instead
                    if ( $featured_bias == 0.5 ) {
                        $featured_args = array(
                            'name' => 'is_featured',
                            'values' => ['no'],
                            'bias' => 1.5
                        );
                    } else {
                        $featured_args = array(
                            'name' => 'is_featured',
                            'values' => ['yes'],
                            'bias' => $featured_bias
                        );

                    }

                    $fields[] = $featured_args;

                }

        }

        $query = array(
            'itemSet'=> $items,
            'num'=> $number,
            'fields' => $fields
        );

        $query['blacklistItems'] = $this->get_excluded_items();

        if ( isset ( $this->general_options['expire_date'] ) &&
            $this->general_options['expire_date'] != 0 ) {

                $after_date = date('c', strtotime('-' . $this->general_options['expire_date'] . ' days' ) );
                $query['dateRange'] = array(
                    'name' => 'published_date',
                    'after' => $after_date
                );

        }

        $response = $this->client->send_query( $query );

        // check that the response is not WP_error
        if ( !is_wp_error( $response ) ) {
            $body = $this->client->get_json( $response );
            if ( ! empty( $body) ) {
                // Check if WPML is installed and get the id of the original language post (not translation)
                if ( function_exists('icl_object_id') ) {
                    global $sitepress;

                    $result = array();
                    foreach ($body['itemScores'] as $key => $i ) {
                        $postid = icl_object_id( $i['item'], 'product', true, $sitepress->get_current_language() );
                        $result['itemScores'][$key]['item'] = strval( $postid );
                        $result['itemScores'][$key]['score'] = $i['score'];
                    }
                } else {
                    $result = $body;
                }
            } else {
                // empty results
                error_log( "[RECOMENDO] --- Itemset recommendations returned no results." );
                $result = false;
            }
        } else {
            // If the request has failed, show the error message
            error_log( "[RECOMENDO] --- Error getting itemSet recommendations." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            $result = false;
        }

        return $result;
    } // end of method --> get_itemset_recommendations


    // add user to Recomendo
    public function add_user( $user_id ) {

        // ignore bots
        if ( $this->detect_crawler() ) {
            return;
        }

        $response = $this->client->set_user( $user_id, array(
                                        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                                        'ip_address' => $_SERVER['REMOTE_ADDR']
                                        )
                             );

        // check the response
        if ( is_wp_error( $response ) ) {
            error_log( "[RECOMENDO] --- Error adding a user." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
        }
    } // end of method --> add_user


    // Add all users using background processing
    public function add_all_users_background() {

        $args = array(
            'fields'       => 'ids',
            'numberposts' => -1
        );

        $user_ids = get_users( $args );

        // Array of WP_User objects.

        foreach ( $user_ids as $id ) {
            $this->bg_user_copy->push_to_queue( $id );
        }

        $this->bg_user_copy->save()->dispatch();

    } //end-of-method add_all_users_background()


    // Delete user from Recomendo
    public function delete_user( $user_id ) {
        $response = $this->client->delete_user( $user_id );

        // check the response
        if ( is_wp_error( $response) ) {
            error_log( "[RECOMENDO] --- Error deleting a user." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
        }
    } // end of method --> delete_user


    // Add item to Recomendo
    public function add_item( $postid ) {

        // Check if WPML is installed and get the id of the original language post (not translation)
        if ( function_exists('icl_object_id') ) {
            global $sitepress;
            $postid = icl_object_id( $postid, $this->options['post_type'], true, $sitepress->get_default_language() );
        }


        if ( class_exists( 'woocommerce' ) ) {
            $terms = get_the_terms( $postid, 'product_cat' );
            $taglist = get_the_terms( $postid, 'product_tag' );
            $product = wc_get_product( $postid );
            // item on sale !
            $is_on_sale = array($product->is_on_sale() ? "yes" : "no" );
            // Featured item
            $is_featured = array($product->is_featured() ? "yes" : "no" );
        } else {
            $terms = get_the_terms( $postid, 'category' );
            $taglist = get_the_tags( $postid );
            $is_on_sale = array("no"); //false
            $is_featured = array("no");    //false
        }

        $title =  wp_filter_nohtml_kses( get_the_title( $postid ) );

        $categories = array();
        if (is_array($terms) or is_object($terms)) {
            foreach ($terms as $term) {
                $categories[] = (string) $term->term_id;
            }
        }

        $tags = array();
        if (is_array($taglist) or is_object($taglist)) {
            foreach ($taglist as $tagitem) {
                $tags[] = (string) $tagitem->term_id;
            }
        }

        $published_date = get_the_date( 'c', $postid );

        $properties = compact(
            'title',
            'categories',
            'tags',
            'is_on_sale',
            'is_featured',
            'published_date'

        );

        $response = $this->client->set_item($postid, $properties);


        if ( !is_wp_error( $response ) ) {
            $response = $this->client->send_train_request();

            if ( is_wp_error( $response ) ) {
                error_log( "[RECOMENDO] --- Error sending train request after adding item." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            }
        } else {
            error_log( "[RECOMENDO] --- Error adding an item." );
            error_log( "[RECOMENDO] --- " . $response->get_error_message() );
        }


    } // end of method --> add_item



    public function add_all_items_background() {

        //$options = get_option( 'recomendo_options ' );
        $args = array(
            'post_type' => $this->options['post_type'],
            'fields' => 'ids',
            'numberposts' => -1
        );

        // Check if WPML is installed and get the id of the original language post (not translation)
        if ( function_exists('icl_object_id') )
            $args['suppress_filters'] = 0;

        $post_ids = get_posts( $args );

        foreach ($post_ids as $id) {
            $this->bg_item_copy->push_to_queue( $id );
            
        }
        $this->bg_item_copy->save()->dispatch();

    } //end-of-method add_all_items_background()


    /**
     * This function returns the values of the progress
     * stored in wordpress options table
     * updated on a background process: 
     */
    public function get_items_progress_background(){
      
        
        $item_progress_complete = intVal(get_option('recomendo_items_background_completed'));
        $user_progress_complete = intVal(get_option('recomendo_users_background_completed'));
        
        if(class_exists( 'woocommerce' ) && $this->options['post_type'] == 'product'){
           
            $order_progress_complete = intVal(get_option('recomendo_orders_background_completed'));
            $array[] = array(
                "items" => $item_progress_complete,
                "users" => $user_progress_complete,
                "orders" => $order_progress_complete);
        }else{
            $array[] = array(
               
                "items" => $item_progress_complete,
                "users" => $user_progress_complete);
        }
       
        $data = json_encode($array);
        echo $data;
        wp_die();
    }

    // delete item from Recomendo when Post status changes to not Published
    public function delete_item( $new_status, $old_status, $post ) {

        if ( $old_status == 'publish' &&
                $new_status != 'publish' &&
                    $post->post_type == $this->options['post_type'] ) {

            // WPML Check the post being deleted is the original and not a translation
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $lang_information = wpml_get_language_information( $post->ID );
                if ( ! is_wp_error( $lang_information ) ) {
                    $post_language = substr( $lang_information['locale'], 0, 2 );
                    if ( $post_language !== $sitepress->get_default_language() ) {
                        return;
                    }
                }
            }

            // Send the $delete event
            $response = $this->client->delete_item( $post->ID );
         
            if ( !is_wp_error( $response )) {
                $response = $this->client->send_train_request();

                if ( is_wp_error( $response )) {
                    error_log( "[RECOMENDO] --- Error sending train request after delete event." );
                    error_log( "[RECOMENDO] --- " . $response->get_error_message() );
                }
            } else {
                error_log( "[RECOMENDO] --- Error recording delete event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            }
        }
    } // end of method --> delete_item


    // Send view event
    public function record_view_event() {

        // ignore bots
        if ( $this->detect_crawler() ) {
            return;
        }

        if ( is_singular( $this->options['post_type'] ) ) {

            if ( get_current_user_id() == 0 ) {
                $userid = $_COOKIE['recomendo-cookie-user'];
            } else {
                $userid = get_current_user_id();
            }

            // Check if registered user does not want user behaviour to be tracked
            if ( in_array( $userid, $this->get_excluded_users() ) ) return;

            //WPML get the default language of the post
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $postid = icl_object_id( get_the_ID(), $this->options['post_type'], true, $sitepress->get_default_language() );
             } else {
                $postid = get_the_ID();
             }

            $response = $this->client->record_user_action('view', $userid, $postid);

            if ( !is_wp_error( $response )) {
                $response = $this->client->send_train_request();

                if ( is_wp_error( $response )) {
                    error_log( "[RECOMENDO] --- Error sending train request after view event." );
                    error_log( "[RECOMENDO] --- " . $response->get_error_message() );
                }
            } else {
                error_log( "[RECOMENDO] --- Error recording view event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            }
        }

    } // end of method --> record_view_event


    // Send category-pref
    public function record_category_pref() {

        // ignore bots
        if ( $this->detect_crawler() ) {
            return;
        }

        if ( is_category() or is_tax() or is_tag() ) {

            $queried_object = get_queried_object();

            $term_id = $queried_object->term_id ;
            $term_type = $queried_object->name ;


            if ( get_current_user_id() == 0 ) {
                $userid = $_COOKIE['recomendo-cookie-user'];
            } else {
                $userid = get_current_user_id();
            }

            // Check if registered user does not want user behaviour to be tracked
            if ( in_array( $userid, $this->get_excluded_users() ) ) return;

            //WPML get the default language of the post
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $term_id = icl_object_id( $term_id , $term_type, true, $sitepress->get_default_language() );
             }

            $response = $this->client->record_user_action('category_pref', $userid, $term_id);

            if ( !is_wp_error( $response )) {
                $response = $this->client->send_train_request();

                if ( is_wp_error( $response )) {
                    error_log( "[RECOMENDO] --- Error sending train request after view event." );
                    error_log( "[RECOMENDO] --- " . $response->get_error_message() );
                }
            } else {
                error_log( "[RECOMENDO] --- Error recording view event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            }
        }

    } // end of method --> record_category_pref

    // Send add_to_cart event
    public function record_add_to_cart_event( $cart_item_data, $product_id ) {

        // ignore bots
        if ( $this->detect_crawler() ) {
            return;
        }

        if ( get_current_user_id() == 0 ) {
            $userid = $_COOKIE['recomendo-cookie-user'];
        } else {
            $userid = get_current_user_id();
        }


        // Check if registered user does not want user behaviour to be tracked
        if ( in_array( $userid, $this->get_excluded_users() ) ) return;


        //WPML get the default language of the post
        if ( function_exists('icl_object_id') ) {
            global $sitepress;
            $postid = icl_object_id( $product_id, 'product', true, $sitepress->get_default_language() );
        } else {
               $postid = $product_id;
        }

        $response = $this->client->record_user_action( 'add_to_cart', $userid, $postid );
        if ( !is_wp_error( $response )) {
            $response = $this->client->send_train_request();

            if ( is_wp_error( $response )) {
                error_log( "[RECOMENDO] --- Error sending train request after add_to_cart event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
            }
        } else {
                error_log( "[RECOMENDO] --- Error recording add_to_cart event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
        }

    } // end of method --> record_add_to_cart_event



    // Send Completed WooCommerce Order as Buy event
    public function record_buy_event( $order_id ) {

        // ignore bots
        if ( $this->detect_crawler() ) {
            return;
        }

        if ( get_current_user_id() == 0 ) {
            $userid = $_COOKIE['recomendo-cookie-user'];
        } else {
            $userid = get_current_user_id();
        }

        // Check if registered user does not want user behaviour to be tracked
        if ( in_array( $userid, $this->get_excluded_users() ) ) return;


        // Lets grab the order
        $order = wc_get_order( $order_id );
        // This is how to grab line items from the order
        $line_items = $order->get_items();
        // This loops over line items
        foreach ( $line_items as $item ) {
            // This will be a product
            $product = $order->get_product_from_item( $item );

            if ( $product->is_type( 'variation' )) {
               // Get the parent id of the product
               $productid = $product->get_parent_id();
            } else {
               // This is the products ID
               $productid = $product->get_id();
            }

            //WPML get the default language of the post
            if ( function_exists('icl_object_id') ) {
                global $sitepress;
                $postid = icl_object_id( $productid, 'product', true, $sitepress->get_default_language() );
            } else {
                   $postid = $productid;
            }

            // Send the buy event to recomendo
            $response = $this->client->record_user_action( 'buy', $userid, $postid );
            if ( !is_wp_error( $response )) {
                $response = $this->client->send_train_request();

                if ( is_wp_error( $response )) {
                    error_log( "[RECOMENDO] --- Error sending train request after buy event." );
                    error_log( "[RECOMENDO] --- " . $response->get_error_message() );
                }
            } else {
                error_log( "[RECOMENDO] --- Error recording buy event." );
                error_log( "[RECOMENDO] --- " . $response->get_error_message() );
                break;
            }
        }
    } // end of method --> record_buy_event


    // Adds all orders as buy events to the eventserver.
    // Executed when all data is copied to eventserver
    public function add_all_orders_background() {

        $query_args = array(
            'fields'         => 'ids',
            'post_type'      => 'shop_order',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        );

        $order_ids = get_posts( $query_args );

        if ( sizeof( $order_ids ) > 0 ) {
            foreach ( $order_ids as $id ) {
                $this->bg_order_copy->push_to_queue( $id );
            }
        }

        $this->bg_order_copy->save()->dispatch();

    } //end-of-method add_all_orders_background()


    public function copy_data_to_eventserver() {

            update_option( 'recomendo_data_saved_ok', true );

            $this->add_all_users_background();
            $this->add_all_items_background();
            if ( class_exists( 'woocommerce' ) &&
                $this->options['post_type'] == 'product' ) {

                    $this->add_all_orders_background();
            }
    }



    public function get_aggregated_events( $start_date, $end_date, $event ) {
        $count = array();

        $response = $this->client->get_events( $start_date, $end_date, 'user', null, $event, 'item', null, -1, 'false' ) ;
        $status = wp_remote_retrieve_response_code( $response );
        if ( $status == 200) {
            $viewed_items = $this->client->get_json($response);

            foreach ($viewed_items as $item) {
                if ( ! isset( $count[$item['targetEntityId']] ) ) {
                    $count[$item['targetEntityId']] = 1;
                } else {
                    $count[$item['targetEntityId']] += 1;
                }
            }
        }
        arsort($count);
        return $count;

    }



} // end of class --> Recomendo_Plugin
