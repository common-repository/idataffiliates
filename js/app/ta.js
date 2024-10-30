var idatFunctions;

jQuery( document ).ready( function($) {

    idatFunctions = {

        /**
         * Record link clicks AJAX event trigger.
         *
         * @since 3.0.0
         */
        recordLinkStat : function() {

            $( 'body' ).delegate( 'a' , 'click' , function(e) {

                var $link   = $(this),
                    href    = $link.attr( 'href' ),
                    linkID  = $(this).data( 'linkid' ),
                    href    = idatFunctions.isIDatLink( href );

                if ( href || linkID ) {

                    $.post( idat_global_vars.ajax_url , {
                        action  : 'ta_click_data_redirect',
                        href    : href,
                        page    : window.location.href,
                        link_id : linkID
                    } );
                }

            });
        },

        /**
         * Function to check if the loaded link is a IDatAffiliates link or not.
         *
         * @since 3.0.0
         */
        isIDatLink : function( href ) {

            if ( ! href )
                return;

            var link_uri    = href.replace( idat_global_vars.home_url , '' ),
                link_prefix = link_uri.substr( 0 , link_uri.indexOf( '/' ) ),
                new_href    = href.replace( '/' + link_prefix + '/' , '/' + idat_global_vars.link_prefix + '/' );

            return ( link_prefix && $.inArray( link_prefix , link_prefixes ) > -1 ) ? new_href : false;
        },

        /**
         * Function to check if the loaded link is a IDatAffiliates link or not.
         *
         * @since 3.0.0
         */
        linkFixer : function() {

            if ( idat_global_vars.link_fixer_enabled !== 'yes' )
                return;

            var $allLinks = $( 'body a' ),
                hrefs     = [],
                href, linkClass, isShortcode, isImage, content , key;

            // fetch all links that are idatlinks
            for ( key = 0; key < $allLinks.length; key++ ) {

                href        = $( $allLinks[ key ] ).attr( 'href' );
                linkClass   = $( $allLinks[ key ] ).attr( 'class' );
                isShortcode = $( $allLinks[ key ] ).data( 'shortcode' );
                isImage     = $( $allLinks[ key ] ).has( 'img' ).length;
                href        = idatFunctions.isIDatLink( href );

                if ( href && ! isShortcode )
                    hrefs.push({ key : key , class : linkClass , href : href , is_image : isImage });

                $( $allLinks[ key ] ).removeAttr( 'data-shortcode' );
            }

            // skip if there are no affiliate links
            if ( hrefs.length < 1 )
                return;

            $.post( idat_global_vars.ajax_url , {
                action  : 'ta_link_fixer',
                hrefs   : hrefs,
                post_id : idat_global_vars.post_id
            }, function( response ) {

                if ( response.status == 'success' ) {

                    for ( x in response.data ) {

                        var key       = response.data[ x ][ 'key' ],
                            qs        = $( $allLinks[ key ] ).prop( 'href' ).split('?')[1], // get the url query strings
                            href      = ( qs ) ? response.data[ x ][ 'href' ] + '?' + qs : response.data[ x ][ 'href' ],
                            title     = response.data[ x ][ 'title' ],
                            className = response.data[ x ][ 'class' ];

                        // add the title if present, if not then remove the attribute entirely.
                        if ( title )
                            $( $allLinks[ key ] ).prop( 'title' , title );
                        else
                            $( $allLinks[ key ] ).removeAttr( 'title' );

                        // if disable_idatlink_class is set to yes then remove the idatlink and idatlinkimg classes.
                        if ( idat_global_vars.disable_idatlink_class == 'yes' )
                            className = className.replace( 'idatlinkimg' , '' ).replace( 'idatlink' , '' ).trim();

                        if ( className )
                            $( $allLinks[ key ] ).prop( 'class' , className );
                        else
                            $( $allLinks[ key ] ).removeAttr( 'class' );

                        // map the other attributes.
                        $( $allLinks[ key ] ).prop( 'href' , href )
                          .prop( 'rel' , response.data[ x ][ 'rel' ] )
                          .prop( 'target' , response.data[ x ][ 'target' ] )
                          .attr( 'data-linkid' , response.data[ x ][ 'link_id' ] );

                    }
                }
            }, 'json' );
        }
    }

    var link_prefixes = $.map( idat_global_vars.link_prefixes , function(value , index) {
        return [value];
    });

    // Initiate record link click stat function
    idatFunctions.recordLinkStat();

    // Initialize uncloak links function
    idatFunctions.linkFixer();
});
