<?php
/**
 * Template Name: Content Recomendo
 * 
 * Template for displaying  recommendations when no template is selected 
 * 
 * If you want to create a custom template, please create a folder named
 * recomendo-templates in your child's theme directory
 * name it with the prefix 'content-'  before your desired name.
 * eg.
 * content-custom.php
 * Then change the template name as in line 3 to the name of your template.
 * 
 * Feel free to use this template to override as you wish.   
 */
defined( 'ABSPATH' ) || exit;

?>
    <li id="recommendation-<?php the_ID(); ?>" class="recomendo recomendo-item">
        <?php if(has_post_thumbnail() ){
            the_post_thumbnail();
        }?>
        
        <div class="recomendo-template-content">
            <h4 class="recomendo-title"><?php the_title() ?></h4>
            <?php  if ( class_exists( 'woocommerce' ))  {
                    
                      $product_id = get_the_ID();
                     $_product = wc_get_product($product_id);
                     echo '<h4 class="recomendoTextCenter"> '.wc_price($_product->get_price()).'</h4>';
                     }
            ?>
            <a href="<?php the_permalink()?>"><button class="recomendo-button "><?php _e('See more','recomendo-template')?></button></a>
        </div> 
    </li>
