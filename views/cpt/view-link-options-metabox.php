<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_nonce_field( 'idat_affiliates_cpt_nonce', '_idataffiliates_nonce' ); ?>

  <div class="form-group">
      <label for="ta_no_follow">
        <?php _e( 'No follow this link?' , 'idataffiliates' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Adds the rel="nofollow" tag so search engines don\'t pass link juice.' , 'idataffiliates' ); ?>"></span>
    </label>

       <select class="form-control" name="ta_no_follow">
        <option value="global" <?php selected( $idatlink->get_prop( 'no_follow' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'idataffiliates' ) , $global_no_follow ); ?></option>
        <option value="yes" <?php selected( $idatlink->get_prop( 'no_follow' ) , 'yes' ); ?>><?php _e( 'Yes' , 'idataffiliates' ); ?></option>
        <option value="no" <?php selected( $idatlink->get_prop( 'no_follow' ) , 'no' ); ?>><?php _e( 'No' , 'idataffiliates' ); ?></option>
    </select>
  </div>
  <div class="form-group">
    <label for="ta_new_window">
        <?php _e( 'Open this link in new window?' , 'idataffiliates' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Opens links in a new window when clicked on.' , 'idataffiliates' ); ?>"></span>
    </label>
    <select class="form-control" name="ta_new_window">
        <option value="global" <?php selected( $idatlink->get_prop( 'new_window' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'idataffiliates' ) , $global_new_window ); ?></option>
        <option value="yes" <?php selected( $idatlink->get_prop( 'new_window' ) , 'yes' ); ?>><?php _e( 'Yes' , 'idataffiliates' ); ?></option>
        <option value="no" <?php selected( $idatlink->get_prop( 'new_window' ) , 'no' ); ?>><?php _e( 'No' , 'idataffiliates' ); ?></option>
    </select>
 </div>

  <div class="form-group">
    <label  for="ta_pass_query_str">
        <?php _e( 'Pass query string to destination url?' , 'idataffiliates' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Passes the query strings present after the cloaked url automatically to the destination url when redirecting.' , 'idataffiliates' ); ?>"></span>
    </label>
    <select class="form-control" name="ta_pass_query_str">
        <option value="global" <?php selected( $idatlink->get_prop( 'pass_query_str' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'idataffiliates' ) , $global_pass_query_str ); ?></option>
        <option value="yes" <?php selected( $idatlink->get_prop( 'pass_query_str' ) , 'yes' ); ?>><?php _e( 'Yes' , 'idataffiliates' ); ?></option>
        <option value="no" <?php selected( $idatlink->get_prop( 'pass_query_str' ) , 'no' ); ?>><?php _e( 'No' , 'idataffiliates' ); ?></option>
    </select>
 </div>

<?php if ( get_option( 'ta_uncloak_link_per_link' ) === 'yes' ) : ?>
  <div class="form-group">
    <label class="info-label block" for="ta_uncloak_link">
        <?php _e( 'Uncloak link?' , 'idataffiliates' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Uncloaks the link when loaded on the frontend.' , 'idataffiliates' ); ?>"></span>
    </label>
    <select class="form-control" name="ta_uncloak_link">
        <option value="global" <?php selected( $idatlink->get_prop( 'uncloak_link' ) , 'global' ); ?>><?php echo sprintf( __( 'Global (%s)' , 'idataffiliates' ) , $global_uncloak ); ?></option>
        <option value="yes" <?php selected( $idatlink->get_prop( 'uncloak_link' ) , 'yes' ); ?>><?php _e( 'Yes' , 'idataffiliates' ); ?></option>
        <option value="no" <?php selected( $idatlink->get_prop( 'uncloak_link' ) , 'no' ); ?>><?php _e( 'No' , 'idataffiliates' ); ?></option>
      </select>
 </div>

<?php endif; ?>

  <div class="form-group">
    <label class="info-label block" for="ta_redirect_type">
        <?php _e( 'Redirect type:' , 'idataffiliates' ); ?>
        <span class="tooltip" data-tip="<?php esc_attr_e( 'Override the default redirection type for this link.' , 'idataffiliates' ); ?>"></span>
    </label>
    <select class="form-control"name="ta_redirect_type">
        <?php foreach ( $redirect_types as $redirect_type => $redirect_label ) : ?>
            <option value="<?php echo esc_attr( $redirect_type ); ?>" <?php selected( $post_redirect_type , $redirect_type ); ?>>
                <?php echo $redirect_label; ?>
                <?php echo ( $redirect_type == $default_redirect_type ) ? __( '(default)' , 'idataffiliates' ) : ''; ?>
            </option>
        <?php endforeach; ?>
     </select>
 </div>


  <div class="form-group">
    <label class="info-label" for="ta_rel_tags">
        <?php _e( 'Additional rel tags:' , 'idataffiliates' ); ?>
    </label>
    <input type="text" class="form-control" id="ta_rel_tags" name="ta_rel_tags" value="<?php echo esc_attr( $rel_tags ); ?>" placeholder="<?php echo esc_attr( $global_rel_tags ); ?>">
   
 </div>


<script type="text/javascript">
jQuery( document ).ready( function($) {

    $( "#ta-link-options-metabox label .tooltip" ).tipTip({
        "attribute"       : "data-tip",
        "defaultPosition" : "left",
        "fadeIn"          : 50,
        "fadeOut"         : 50,
        "delay"           : 200
    });

});
</script>
