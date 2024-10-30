<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_nonce_field( 'idat_affiliates_cpt_nonce', '_idataffiliates_nonce' );

if ( function_exists( 'wp_enqueue_media' ) && $legacy_uploader != 'yes' ) : ?>

    <div class="button-secondary" id="ta_upload_media_manager"
         data-editor="content"
         data-uploader-title="<?php esc_attr_e( 'Add Featured Image to Affiliate Link' , 'idataffiliates' ); ?>"
         data-uploader-button-text="<?php esc_attr_e('Add To Affiliate Link'); ?>" >
        <?php _e( 'Upload/Insert' , 'idataffiliates' ); ?>
        <span class="wp-media-buttons-icon"></span>
    </div>

<?php else : ?>

    <div class="button-secondary" id="ta_upload_insert_img">
        <?php _e( 'Upload/Insert' , 'idataffiliates' ); ?>
        <a class="thickbox" href="<?php echo esc_attr( admin_url( 'media-upload.php?post_id=' . $post->ID . '?type=image&TB_iframe=1' ) ); ?>">
            <span class="wp-media-buttons-icon"></span>
        </a>
    </div>

<?php endif; ?>

<script type="text/javascript">
    var wpActiveEditor = 'content';
</script>

<?php if ( ! empty( $attachments ) ) : ?>
    <div id="idat_image_holder">
        <?php foreach ( $attachments as $attachment_id ) {
            $img = wp_get_attachment_image_src( $attachment_id , 'full' );
    		include( $this->_constants->VIEWS_ROOT_PATH() . 'cpt/view-attach-images-metabox-single-image.php' );
        } ?>
    </div>
<?php endif; ?>
