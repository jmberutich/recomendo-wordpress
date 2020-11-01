<?php
/**
 * Template for the default recomendo widget 
 */
?>
<article id="recommendation-<?php the_ID(); ?>-widget" class="recomendo-widget">
    <?php if(has_post_thumbnail() ){
            the_post_thumbnail(array('70','70'));
        }?>
        <div class="recomendo-widget-content">
            <a href="<?php the_permalink()?>"><span><?php the_title();?></span></a>
            <?php
            if ( class_exists( 'woocommerce' ))  {
                      $product_id = get_the_ID();
                     $_product = wc_get_product($product_id);
                     echo '<span >'.wc_price($_product->get_price()).'</span>';
            }else{
                echo '<span >'.the_author() .'</span>';
            }
            ?>
            
        </div> 
</article>