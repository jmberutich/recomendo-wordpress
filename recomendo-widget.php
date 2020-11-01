<?php


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// Creating the widget
class Recomendo_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            // Base ID of your widget
            'recomendo_widget',

            // Widget name will appear in UI
            __('Recomendo Widget', 'recomendo_widget_domain'),

            // Widget description
            array( 'description' => __( 'Shows Artificial Intelligence Personalised Recommendations', 'recomendo_widget_domain' ), )
        );

    }


    public function recomendo_locate_template_widget( $template_name, $template_path = '', $default_path = '' , $woo_path='') {
       
        // Set variable to search in recomendo folder of theme.
        if ( ! $template_path ) :
            $template_path = 'recomendo-templates/';
        
        endif;  // Set default plugin templates path.
        if ( ! $default_path ) :
            $default_path = plugin_dir_path( __FILE__ ) . 'recomendo-templates/'; // Path to the template folder
          
        endif;
        if(!$woo_path) :
            $woo_path = WC()->plugin_path(__FILE__) . '/templates/';
        endif;    


        // Search template file in theme folder.
        $template = locate_template( array(
            $template_path . $template_name,
            $template_name ) );
           
        
        if(! $template){
            $template = $default_path . $template_name;
        }
        if( ! file_exists( $template )){
            $template = $woo_path . $template_name;
        }
      
        return apply_filters( 'recomendo_locate_template', $template, $template_name, $template_path, $default_path, $woo_path );
    }


    public function recomendo_get_template_widget( $template_name, $args = array(), $template_path = '', $default_path = '', $woo_path='') {
        if ( is_array( $args ) && isset( $args ) ) :
            extract( $args );
        endif;
        $template_file = $this->recomendo_locate_template_widget( $template_name, $template_path, $default_path , $woo_path);
        if ( ! file_exists( $template_file ) ) :
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $template_file ), '1.0.0' );
            return;
        endif;
        include $template_file;
    }


        //Get all templates 
    public function recomendo_all_templates_widget(){
            //get templates in the theme
            $templates = wp_get_theme()->get_page_templates();
            $options = get_option( 'recomendo_options' );
            //set default template 
            if($options['post_type'] == 'product'){
                $array[] = 'content-widget-product';
            }
                $array[] = 'widget-recomendo';
            
            
            
            
            foreach ( $templates as $template_name => $template_filename )
            {
                //if the templates route  has levels eg: folder/file.php removes the '/'
                $exploded_name = explode('/',$template_name );

                foreach($exploded_name as $expresion){
                    //Search for the element that has .php and if it does, remove it and add to array
                    if (preg_match('/(\.php)$/i', $expresion)) {
                        $new_templateName = substr($expresion,0,-4);
                        $array[] = $new_templateName;
                    }
                }

            }
            return $array;
    }



    // Creating widget front-end

    public function widget( $args, $instance ) {

        global $recomendo, $woocommerce;

        if ( !get_option( 'recomendo_auth' ) ) return;

        if ( !$options = get_option( 'recomendo_options' ) ) return;

        $template_args = array(
            'widget_id'   => $args['widget_id'],
            'show_rating' => true
        );

        switch (  $instance['type'] ) {
            case 'Personalized' :
                $response = $recomendo->get_user_recommendations( intval( $instance['number']));
                break;
            case 'Similar Items' :
                if ( is_singular( $options['post_type'] ) ) {
                    // Check if WPML is installed and get the id of the original language post (not translation)
                    if ( function_exists('icl_object_id') ) {
                        global $sitepress;
                        $postid = icl_object_id( get_the_ID(), $options['post_type'], true, $sitepress->get_default_language() );
                    } else {
                        $postid = get_the_ID();
                    }
                    $response = $recomendo->get_item_recommendations( $postid, intval( $instance['number'] ) );
                } else {
                    //echo '<p>Recomendo warning: Similar item recomendations need to be shown on single item pages</p>' ;
                    return;
                }
                break;
            case 'Complementary Items' :
                $itemset_products = array();
                if ( class_exists( 'woocommerce' ) ) {
                    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

                        // Check if WPML is installed and get the id of the original language post (not translation)
                        if ( function_exists('icl_object_id') ) {
                            global $sitepress;
                            $itemset_products[] = icl_object_id( $values['product_id'], 'product', true, $sitepress->get_default_language() );
                        } else {
                            $itemset_products[] = $values['product_id'];
                        }
                    }

                    $response = $recomendo->get_itemset_recommendations( $itemset_products, intval( $instance['number'] ) );
                } else {
                    if ( have_posts() ) {
                        while ( have_posts() ) {
                            the_post();
                            // Check if WPML is installed and get the id of the original language post (not translation)
                            if ( function_exists('icl_object_id') ) {
                                global $sitepress;
                                $itemset_products[] = icl_object_id( get_the_ID(), $options['post_type'], true, $sitepress->get_default_language() );
                            } else {
                                $itemset_products[] = get_the_ID();
                            }
                        }
                        $response = $recomendo->get_itemset_recommendations( $itemset_products, intval( $instance['number'] ) );
                    } else {
                        echo '<p>no posts to show</p>';
                    }
                }
                break;
            case 'Trending Items' :
                $response = $recomendo->get_trending_items( intval( $instance['number']));
                break;

        }


        if ( $response != false and array_key_exists( 'itemScores', $response ) ) {

            $title = apply_filters( 'widget_title', $instance['title'] );

            // before and after widget arguments are defined by themes
            echo $args['before_widget'];
            if ( ! empty( $title ) )
                echo $args['before_title'] . $title . $args['after_title'];

            // This is where you run the code and display the output
            // -----------------------------------------------

            if ( class_exists( 'woocommerce' ) && ($options['post_type'] == 'product')) {
                echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="' . $instance['class'] .  '">' ) );
                foreach ($response['itemScores'] as $i ) {
                    if ( get_post_status ( $i['item'] ) == 'publish' ) {
                        $post_object = get_post( $i['item'] );
                        setup_postdata( $GLOBALS['post'] =& $post_object );
                        // add .php to the template -> woocommerce requires it
                        // but get_template_part does not
                        $this->recomendo_get_template_widget( $instance['template'] . '.php' );
                    }
                }
            } else {

                foreach ($response['itemScores'] as $i ) {
                    if ( get_post_status ( $i['item'] ) == 'publish' ) {
                        $post_object = get_post( $i['item'] );
                        setup_postdata( $GLOBALS['post'] =& $post_object );
                        // REPLACE by custom parameter
                        $this->recomendo_get_template_widget( $instance['template'].'.php' );

                    }
                }

            }

            wp_reset_postdata();

            // -----------------------------------------------
            // till here
            echo $args['after_widget'];
        }

    }

    // Widget Backend
    public function form( $instance ) {

        global $recomendo;

        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'recomendo_widget_domain' );
        }

        if ( isset( $instance[ 'type' ] ) ) {
            $type = $instance[ 'type' ];
        }
        else {
            $type = __( 'Personalized', 'recomendo_widget_domain' );
        }


        if ( isset( $instance[ 'number' ] ) ) {
            $number = $instance[ 'number' ];
        }
        else {
            $number = __( 5, 'recomendo_widget_domain' );
        }

        if ( isset( $instance[ 'template' ] ) ) {
            $template = $instance[ 'template' ];
        }
        else {
            if ( (class_exists( 'woocommerce' )) && ($options['post_type'] == 'product') ) {
                $template = __( 'content-widget-product', 'recomendo_widget_domain' );
            } else {
                $template = __( 'widget-recomendo' , 'recomendo_widget_domain' );
            }
        }


        if ( isset( $instance[ 'class' ] ) ) {
            $class = $instance[ 'class' ];
        } else {
            if ( class_exists( 'woocommerce' ) ) {
                $class = __( 'product_list_widget', 'recomendo_widget_domain' );
            } else {
                $class = __( 'widget_text', 'recomendo_widget_domain' );
            }
        }


        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Type of recommendations:' ); ?></label>
            <select class="widefat"  id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="text">

            <?php
                foreach ( [ 'Personalized', 'Similar Items', 'Complementary Items', 'Trending Items' ] as $i ) {
                    $line = '<option value="' . $i . '" ' ;
                    if ( $i == $instance[ 'type' ] ) {
                        $line .= 'selected';
                    }
                    $line .= '>' . $i . '</option>';
                    echo $line;
                }

                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of recommendations to show:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo esc_attr( $number ); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template part:' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>" value="<?php echo esc_attr( $template ); ?>" >
                <?php $options= $this->recomendo_all_templates_widget(); 
                foreach( $options as $option){
                    $html = '<option value="'. $option .'"';
                    if($option == $instance['template']){
                        $html .= 'selected';
                    }
                    $html .= '>'. $option . '</option>';
                    echo $html;
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'class' ); ?>"><?php _e( 'CSS class:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'class' ); ?>" name="<?php echo $this->get_field_name( 'class' ); ?>" type="text" value="<?php echo esc_attr( $class ); ?>" />
        </p>


        <?php
    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['type'] = ( ! empty( $new_instance['type'] ) ) ? strip_tags( $new_instance['type'] ) : '';
        $instance['number'] = ( ! empty( $new_instance['number'] ) ) ? strip_tags( $new_instance['number'] ) : '';

        $instance['template'] = ( ! empty( $new_instance['template'] ) ) ? strip_tags(  $new_instance['template'] ) : '';
        $instance['class'] = ( ! empty( $new_instance['class'] ) ) ? strip_tags( $new_instance['class'] ) : '';

        return $instance;
    }
} // Class recomendo_widget ends here
