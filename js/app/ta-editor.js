var IDatLinkPicker;

jQuery( document ).ready( function($) {

    IDatLinkPicker = {
        editor     : null,
        editorinit : false,
        linkNode   : null,
        inputInstance : null,
        close_thickbox : function() {
            tb_remove();
        },

        /**
         * Get selected text on the HTML editor
         *
         * @since 3.0.0
         */
        get_html_editor_selection : function() {

            var text_component,
                selected_text = {},
                sel, startPos, endPos;

    		text_component = document.getElementById( "replycontent" );
    		if (typeof text_component == "undefined" || ! jQuery(text_component).is( ":visible" ) ) // is not a comment reply
    			text_component = document.getElementById( "content" );

    		// IE version
    		if (parent.document.selection != undefined) {
    			text_component.focus();
    			sel = parent.document.selection.createRange();
    			selected_text.text  = sel.text;
    			selected_text.start = sel.start;
    			selected_text.end   = sel.end;
    		}

    		// Mozilla version
    		else if (text_component.selectionStart != undefined) {
    			startPos = text_component.selectionStart;
    			endPos = text_component.selectionEnd;
    			selected_text.text = text_component.value.substring(startPos, endPos)
    			selected_text.start = startPos;
    			selected_text.end = endPos;
    		}

    		return selected_text;
        },

        /**
         * Replace selected text on the HTML editor.
         *
         * @since 3.0.0
         */
        replace_html_editor_selected_text : function( text , append = false ) {

            var el  = parent.document.getElementById("replycontent"),
                sel = IDatLinkPicker.get_html_editor_selection(),
                val;

            if ( typeof el == "undefined" || ! $( el ).is( ":visible" ) ) // is not a comment reply
                el = parent.document.getElementById( "content" );

            val      = el.value;
            content  = append ? sel.text + text : text;
            el.value = val.slice( 0 , sel.start ) + content + val.slice( sel.end );

	        jQuery( el ).trigger('change'); // some addons require notice that something has changed
        }
    };

    /**
     * Event: Simple insert link search results selection.
     * This event runs when a result selection is clicked. The purpose of this is to transfer the required data
     * from the selection to the text input (inputInstance) so it can be processed in the idatlink_apply custom tinymce command.
     *
     * @since 3.0.0
     */
    $( 'body' ).on( 'click' , '.affiliate-link-list li' , function( event ) {

        event.preventDefault();

        var $link = $(this),
            $input = $link.closest( '.wp-idatlink-input' ).find( 'input' );

        $input.val( $link.data( 'href' ) )
              .attr( 'data-aff-content' , $link.find( 'span' ).text() )
              .attr( 'data-aff-class' , $link.data( 'class' ) )
              .attr( 'data-aff-title' , $link.data( 'title' ) )
              .attr( 'data-aff-rel' , $link.data( 'rel' ) )
              .attr( 'data-aff-target' , $link.data( 'target' ) )
              .attr( 'data-aff-link-insertion-type' , $link.data( 'link-insertion-type' ) )
              .attr( 'data-aff-link-id' , $link.data( 'link-id' ) )
              .attr( 'data-aff-other-atts' , $link.attr( 'data-other-atts' ) );
    });

    /**
     * Register Text/Quicktags editor buttons.
     *
     * @since 3.0.0
     */
    if ( typeof QTags != 'undefined' && ta_editor_var.disable_qtag_buttons !== 'yes' ) {

        QTags.addButton( "idataffiliates_aff_link", "affiliate link", ta_display_affiliate_link_thickbox , "" , "" , ta_editor_var.html_editor_affiliate_link_title , 30 );
        QTags.addButton( "idataffiliates_quick_add_aff_Link", "quick add affiliate link", ta_display_quick_add_affiliate_thickbox , "" , "" , ta_editor_var.html_editor_quick_add_title , 31 );
    }

    /**
     * Function to display the affiliate link thickbox for the HTML editor.
     *
     * @since 3.0.0
     */
    function ta_display_affiliate_link_thickbox() {

        var post_id = $( '#post_ID' ).val();
        tb_show( 'Add Affiliate Link' , window.ajaxurl + '?action=ta_advanced_add_affiliate_link&post_id=' + post_id + '&height=640&width=640&html_editor=true&TB_iframe=false' );
    }

    /**
     * Function to display the quick add affiliate link thickbox for the HTML editor.
     */
    function ta_display_quick_add_affiliate_thickbox() {

        var selection = IDatLinkPicker.get_html_editor_selection().text,
            post_id   = $( '#post_ID' ).val();

        tb_show( 'Quick Add Affiliate Link' , window.ajaxurl + '?action=ta_quick_add_affiliate_link_thickbox&post_id=' + post_id + '&height=500&width=500&selection=' + selection + '&html_editor=true&TB_iframe=false' );
    }
});
