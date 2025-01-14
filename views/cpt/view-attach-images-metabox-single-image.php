<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<div class="idat-attached-image">
    <span class="idat-remove-img" title="<?php esc_attr_e( 'Remove This Image' , 'idataffiliates' ); ?>" id="<?php echo esc_attr( $attachment_id ); ?>">&times;</span>
    <a class="idat-img thickbox" href="<?php echo esc_attr( $img[0] ); ?>" rel="gallery-linkimgs" title="<?php echo esc_attr( get_the_title( $attachment_id ) ); ?>">
        <?php echo wp_get_attachment_image( $attachment_id , array( 100 , 100 ) ); ?>
    </a>
</div>
