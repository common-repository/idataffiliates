( function( tinymce ) {

    // IDatLinkInput input type object.
    IDatLinkInputObj = {
		renderHtml: function() {
			return (
				'<div id="' + this._id + '" class="wp-idatlink-input">' +
					'<input type="text" value="" placeholder="' + parent.ta_editor_var.simple_search_placeholder + '" data-aff-content="" data-aff-title="" data-aff-class="" data-aff-rel="" data-aff-target="" data-aff-link-insertion-type="" data-aff-link-id="" />' +
					'<ul class="affiliate-link-list" style="display: none;"></ul>' +
				'</div>'
			);
		},
        getURL: function() {
			return tinymce.trim( this.getEl().firstChild.value );
		},
        getData: function( attrib ) {
            return tinymce.trim( this.getEl().firstChild.getAttribute( 'data-aff-' + attrib ) );
        },
        reset: function() {
			var input = this.getEl().firstChild;

			input.value = '';
			input.nextSibling.innerHTML = '';
		}
    };

    // register IDatLinkInput input type to tinymce.
    if ( tinymce.ui.Factory )
        tinymce.ui.Factory.add( 'IDatLinkInput' , tinymce.ui.Control.extend( IDatLinkInputObj ) );
    else
        tinymce.ui.IDatLinkInput = tinymce.ui.Control.extend( IDatLinkInputObj );

    // add idataffiliates to the tinymce plugin manager.
    tinymce.PluginManager.add( 'idataffiliates', function( editor , url ) {

        var idatToolbar,
            $ = window.jQuery,
            linkNode,
            inputInstance,
            idatlink_apply_key_command     = navigator.platform.match(/Mac/i) ? '⌘⌥K' : 'Ctrl+Alt+K',
            idatlink_quick_add_key_command = navigator.platform.match(/Mac/i) ? '⌘⇧K' : 'Ctrl+Shift+K';

        // get the selected link
        function getSelectedLink() {
			var href, html,
				node = editor.selection.getNode(),
				link = editor.dom.getParent( node, 'a[href]' );

			if ( ! link ) {
				html = editor.selection.getContent({ format: 'raw' });

				if ( html && html.indexOf( '</a>' ) !== -1 ) {
					href = html.match( /href="([^">]+)"/ );

					if ( href && href[1] ) {
						link = editor.$( 'a[href="' + href[1] + '"]', node )[0];
					}

					if ( link ) {
						editor.selection.select( link );
					}
				}
			}

			return link;
		}

        // remove affiliate link placeholder
        function removePlaceholders() {
			editor.$( 'a' ).each( function( i, element ) {
				var $element = editor.$( element );

				if ( $element.attr( 'href' ) === '_wp_idatlink_placeholder' ) {
					editor.dom.remove( element, true );
				} else if ( $element.attr( 'data-idatlink-edit' ) ) {
					$element.attr( 'data-idatlink-edit', null );
				}
			});
		}

        // Register custom inline toolbar
        editor.on( 'preinit', function() {

            if ( editor.wp && editor.wp._createToolbar ) {

                idatToolbar = editor.wp._createToolbar( [
					'idataffiliates_search_input',
                    'idataffiliates_apply_affiliate_link',
                    'idataffiliates_advance_affiliate_link'
				], true );

                idatToolbar.on( 'hide', function() {
					if ( ! idatToolbar.scrolling && idatToolbar.tempHide != true ) {
						editor.execCommand( 'idatlink_cancel' );
					}
				} );

                idatToolbar.on( 'show', function() {

                    var element = idatToolbar.$el.find( 'input' );

                    $( element ).focus();
                });

                IDatLinkPicker.editorinit = true;
            }
        });

        // assign event nodes on when the toolbar will need to show up
        editor.on( 'wptoolbar', function( event ) {

            var linkNode = editor.dom.getParent( event.element, 'a' ),
                $linkNode, href, edit;

            if ( linkNode ) {

                $linkNode = editor.$( linkNode );
                href      = $linkNode.attr( 'href' );
                edit      = $linkNode.attr( 'data-idatlink-edit' );

                if ( href === '_wp_idatlink_placeholder' || edit ) {

                    event.element = linkNode;
                    event.toolbar = idatToolbar;
                }
            }
        });

        /*
        |--------------------------------------------------------------------------
        | TinyMCE Custom Commands
        |--------------------------------------------------------------------------
        */

        // insert affiliate link
        editor.addCommand( 'idatlink_insert' , function() {

            var node     = editor.selection.getNode();
                linkNode = getSelectedLink();

            idatToolbar.tempHide = false;

            if ( linkNode ) {

                // TODO: edit inserted link

            } else {

                var style = parent.ta_editor_var.insertion_type === "shortcode" ? 'box-shadow: none; border: 1px solid #999; text-decoration: none; color: inherit;' : '';

                removePlaceholders();
                editor.execCommand( 'mceInsertLink', false, {
                    class  : 'idatlink',
                    title  : '_title_placeholder',
                    style  : style,
                    href   : '_wp_idatlink_placeholder',
                    rel    : '_rel_placeholder',
                    target : '_target_placeholder' }
                );

                linkNode = editor.$( 'a[href="_wp_idatlink_placeholder"]' )[0];
                editor.nodeChanged();

            }
        } );

        // cancel insert affiliate link
        editor.addCommand( 'idatlink_cancel', function() {

			if ( ! idatToolbar.tempHide ) {
				inputInstance.reset();
				removePlaceholders();
			}
		} );

        editor.addCommand( 'idatlink_apply' , function() {

            // if ( idatToolbar.scrolling )
			// 	return;

            if ( linkNode ) {

                var href                = inputInstance.getURL(),
                    content             = inputInstance.getData( 'content' ),
                    class_name          = inputInstance.getData( 'class' ),
                    title               = inputInstance.getData( 'title' )
                    rel                 = inputInstance.getData( 'rel' ),
                    target              = inputInstance.getData( 'target' ),
                    link_id             = inputInstance.getData( 'link-id' ),
                    link_insertion_type = inputInstance.getData( 'link-insertion-type' ),
                    other_atts          = JSON.parse( inputInstance.getData( 'other-atts' ) );

                if ( link_insertion_type == 'shortcode' ) {

                    var shortcode_text   = tinymce.trim( linkNode.innerHTML ) ? tinymce.trim( linkNode.innerHTML ) : title,
                        shortcode_markup = "[idatlink ids=\"" + link_id + "\"]" + shortcode_text + "[/idatlink]";

                    editor.selection.setContent( shortcode_markup );

                    removePlaceholders();

                } else {

                    if ( ! /^(?:[a-z]+:|#|\?|\.|\/)/.test( href ) )
                        return;

                    var link_attributes = {
                        href   : href,
                        class  : class_name,
                        title  : title,
                        rel    : rel,
                        target : target,
                        'data-wplink-edit': null,
                        'data-idatlink-edit' : null,
                    };

                    if ( typeof other_atts == 'object' && Object.keys( other_atts ).length > 0 ) {

                        for ( var x in other_atts )
                            link_attributes[ x ] = other_atts[ x ];
                    }

                    editor.dom.setAttribs( linkNode , link_attributes );

                    if ( ! tinymce.trim( linkNode.innerHTML ) )
                        editor.$( linkNode ).text( content );

                }

            }

            inputInstance.reset();
			editor.nodeChanged();

        } );

        editor.addCommand( 'idatlink_advance' , function() {

            var post_id = $( '#post_ID' ).val();

            idatToolbar.tempHide = true;

            IDatLinkPicker.editor        = editor;
            IDatLinkPicker.linkNode      = linkNode;
            IDatLinkPicker.inputInstance = inputInstance;

            editor.execCommand( "Unlink" , false , false );

            tb_show( 'Add Affiliate Link' , window.ajaxurl + '?action=ta_advanced_add_affiliate_link&post_id=' + post_id + '&height=640&width=640&TB_iframe=false' );

            inputInstance.reset();
            idatToolbar.tempHide = false;
        } );

        editor.addCommand( 'idatlink_quick_add' , function() {

            var selection = editor.selection.getContent(),
                post_id   = $( '#post_ID' ).val();

            IDatLinkPicker.editor = editor;

            tb_show( 'Quick Add Affiliate Link' , window.ajaxurl + '?action=ta_quick_add_affiliate_link_thickbox&post_id=' + post_id + '&height=500&width=500&selection=' + selection + '&TB_iframe=false' );
        } );

        /*
        |--------------------------------------------------------------------------
        | TinyMCE Custom Buttons
        |--------------------------------------------------------------------------
        */

        // add affiliate link button
        editor.addButton( 'idataffiliates_button' , {
            title : 'Add Affiliate Link (' + idatlink_apply_key_command + ')',
            image : url + '/img/aff.gif',
            icon  : 'test_icon',
            cmd   : 'idatlink_insert',
            onpostrender: function() {
                this.$el.addClass( 'ta-add-link-button' );
                $('body').trigger('ta_reinit_tour_pointer');
            }
        });

        // quick add affiliate link post
        editor.addButton( 'idataffiliates_quickaddlink_button' , {
            title : 'Quick Add Affiliate Link (' + idatlink_quick_add_key_command + ')',
            image : url + '/img/aff-new.gif',
            cmd   : 'idatlink_quick_add'
        });

        // search affiliate link input
        editor.addButton( 'idataffiliates_search_input' , {
            type  : 'IDatLinkInput',
            onPostRender: function() {

                var element     = this.getEl(),
					input       = element.firstChild,
                    resultList  = element.getElementsByTagName( 'ul' ),
                    cache, last;

				inputInstance = this;

                // search affiliate link event
                tinymce.$( input ).on( 'keyup' , function() {

                    var $input = $(this),
                        $resultList = $input.next();

                    // clear results list
                    $resultList.html('').hide();

                    if ( $input.val().length < 3 )
                        return;

                    if ( last === $input.val() ) {

                        $resultList.html( cache ).show();
                        return;
                    }

                    last = $input.val();

                    $.post( window.ajaxurl, {
                        action  : 'search_affiliate_links_query',
                        keyword : $input.val(),
                        post_id : $( '#post_ID' ).val()
                    }, function( response ) {

                        if ( response.status == 'success' ) {

                            cache = response.search_query_markup;
                            $resultList.html( response.search_query_markup ).show();

                        } else {
                            // TODO: Handle error here
                        }

                    } , 'json' );
                } );


            }
        });

        editor.addButton( 'idataffiliates_apply_affiliate_link' , {
            title   : 'Apply Affiliate Link',
            icon    : 'dashicon dashicons-editor-break',
            classes : 'widget btn primary',
            cmd     : 'idatlink_apply'
        });

        editor.addButton( 'idataffiliates_advance_affiliate_link' , {
            title   : 'Advanced Options',
            icon    : 'dashicon dashicons-admin-generic',
            cmd     : 'idatlink_advance'
        });

        /*
        |--------------------------------------------------------------------------
        | TinyMCE Custom keyboard shortcuts
        |--------------------------------------------------------------------------
        */

        editor.addShortcut( 'meta+alt+k' , 'Add Affiliate Link' , 'idatlink_insert' );
        editor.addShortcut( 'meta+shift+k' , 'Quick Add Affiliate Link' , 'idatlink_quick_add' );
    });

} )( window.tinymce );
