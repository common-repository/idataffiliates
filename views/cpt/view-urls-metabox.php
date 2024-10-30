<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_nonce_field( 'idat_affiliates_cpt_nonce', '_idataffiliates_nonce' ); ?>

 <div class="input-group">
  <span class="input-group-addon" id="basic-addon1"><?php _e( 'Destination URL:' , 'idataffiliates' ); ?></span>
  <input type="text" class="form-control" placeholder="Destination URL" aria-label="Destination URL" aria-describedby="basic-addon1" id="ta_destination_url" name="ta_destination_url" value="<?php echo $idatlink->get_prop( 'destination_url' ); ?>">
</div>
<?php if ( $screen->action != 'add' ) : ?>
<p class="input-group">

 <div class="input-group">
      <span class="input-group-addon" id="basic-addon1"><?php _e( 'Cloaked URL:' , 'idataffiliates' ); ?></span>

    <!--<span class="cloaked-fields">-->
        <input type="url" class="form-control" id="ta_cloaked_url" name="ta_cloaked_url" value="<?php echo esc_attr( $idatlink->get_prop( 'permalink' ) ); ?>" readonly>
     </div>
        <button type="button" class="btn btn-info"><?php _e( 'Edit slug' , 'idataffiliates' ); ?></button>
        <a class="btn btn-link"href="<?php echo esc_attr( $idatlink->get_prop( 'permalink' ) ); ?>" target="_blank"><?php _e( 'Visit Link' , 'idataffiliates' ); ?></a>
    <!--</span>-->
    
   
    <span class="slug-fields" style="display: none;">
        <input type="text" class="form-control" id="ta_slug" name="post_name" value="<?php echo esc_attr( $idatlink->get_prop( 'slug' ) ); ?>">
        <button type="button" class="save-ta-slug button"><?php _e( 'Save' , 'idataffiliates' ); ?></button>
    </span>
</p>
<?php endif; ?>

<?php if ( get_option( 'ta_show_cat_in_slug' ) === 'yes'  ) : ?>
    <p>
        <label class="info-label" for="ta_category_slug">
            <?php _e( 'Category to show in slug:' , 'idataffiliates' ); ?>
        </label>
        <select name="ta_category_slug" data-home-link-prefix="<?php echo esc_attr( $home_link_prefix ); ?>">
            <option value="" data-slug="<?php echo esc_attr( $default_cat_slug ); ?>" <?php selected( $idatlink->get_prop( 'category_slug' ) , '' ) ?> >
                <?php _e( 'Default' , 'idatlink' ); ?>
            </option>
            <?php foreach( $idatlink->get_prop( 'categories' ) as $category ) : ?>
                <option value="<?php echo $category->term_id; ?>" data-slug="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $idatlink->get_prop( 'category_slug' ) , $category->slug ) ?> >
                    <?php echo $category->name; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
<?php endif; ?>
