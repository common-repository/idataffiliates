<html>

<head>
<?php
    do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
?>
</head>

<body>

    <div id="advanced_add_affiliate_link">

        <div class="search-panel">
            <label>
                <span class="search-label"><?php _e( 'Search:' , 'idataffiliates' ); ?></span>
                <input type="text" id="idatlink-search" class="idatlink-search-field form-control" placeholder="<?php esc_attr_e( 'Please enter 3 or more characters...' ) ?>" autocomplete="off">
                <span class="spinner"></span>
            </label>
        </div>

        <div class="results-panel">
            <ul class="results-list" data-htmleditor="<?php echo $html_editor; ?>">
                <?php echo $result_markup; ?>
            </ul>
            <a class="load-more-results" href="#">
                <span class="spinner"><i style="background-image: url(<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>)"></i> <?php _e( 'Fetching...' , 'idataffiliates' ); ?></span>
                <span class="button-text"><i class="dashicons dashicons-update"></i> <?php _e( 'Load more' , 'idataffiliates' ); ?></span>
            </a>
        </div>
    </div>
    <script>

    // global var
    Options = {
        post_id        : <?php echo $post_id; ?>,
        searching_text : '<?php _e( 'Searching...' , 'idataffiliates' ); ?>',
        spinner_image  : '<?php echo $this->_constants->IMAGES_ROOT_URL() . 'spinner.gif'; ?>'
    };

    jQuery( document ).ready( function($) {

        $( "body" ).on( 'DOMNodeInserted' , function() {

            $( "#advanced_add_affiliate_link .actions .button" ).tipTip({
                "attribute"       : "data-tip",
                "defaultPosition" : "bottom",
                "fadeIn"          : 50,
                "fadeOut"         : 50,
                "delay"           : 200
            });

        } ).trigger( 'DOMNodeInserted' );

    });
    </script>

</body>

</html>
