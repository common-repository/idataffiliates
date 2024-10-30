jQuery( document ).ready( function($){

    // Functions
    idatSettings = {
        customLinkPrefix : function() {

            $settingsBlock.on( 'change' , '#ta_link_prefix' , function() {

                var linkPrefix           = $(this).val(),
                    $customLinkPrefixRow = $settingsBlock.find( '.ta_link_prefix_custom-row' );

                if ( linkPrefix == 'custom' ) {

                    $customLinkPrefixRow.show();
                    $customLinkPrefixRow.find( '#ta_link_prefix_custom' ).prop( 'disabled' , false );

                } else {

                    $customLinkPrefixRow.hide();
                    $customLinkPrefixRow.find( '#ta_link_prefix_custom' ).prop( 'disabled' , true );

                }

            } );

            $settingsBlock.find( '#ta_link_prefix' ).trigger( 'change' );

        },
        validLinkPrefix : function() {

            if ( $settingsBlock.find( '#ta_link_prefix' ).length > 0 ) {

                $settingsBlock.on( 'click' , '#submit' , function() {

                    var linkPrefix           = $settingsBlock.find( '#ta_link_prefix' ).val(),
                        $customLinkPrefixRow = $settingsBlock.find( '.ta_link_prefix_custom-row' );

                    if ( linkPrefix == 'custom' && $.trim( $customLinkPrefixRow.find( '#ta_link_prefix_custom' ).val() ) === '' ) {

                            alert( ta_settings_var.i18n_custom_link_prefix_valid_val );

                            $( 'html, body' ).animate( {
                                scrollTop : $customLinkPrefixRow.find( '#ta_link_prefix_custom' ).offset().top - 50
                            } , 500 );

                            $customLinkPrefixRow.find( '#ta_link_prefix_custom' ).focus();

                            return false;

                    }

                } );

            }

        }
    };

    var $settingsBlock = $( '.ta-settings.wrap' );

    // initialize custom link prefix settings display
    idatSettings.customLinkPrefix();
    idatSettings.validLinkPrefix();

});
