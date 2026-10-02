/*
 * Admin screens: choice lists (drag and drop order), color pickers,
 * presets and the live preview.
 */
( function ( $ ) {
	'use strict';

	var data = window.DinvAdmin || {};

	/* Choice lists ------------------------------------------------------ */

	function syncChoices( $box ) {
		var values = [];
		$box.find( 'li' ).each( function () {
			var $input = $( this ).find( 'input[type="checkbox"]' );
			if ( $input.prop( 'checked' ) ) {
				values.push( $input.val() );
			}
		} );
		$box.find( '[data-dinv-choices-value]' ).val( values.length ? values.join( ',' ) : 'none' ).trigger( 'change' );
	}

	function initChoices( $box ) {
		var $list = $box.find( 'ul' );
		if ( $box.hasClass( 'dinv-choices--list' ) && $.fn.sortable ) {
			$list.sortable( {
				handle: '.dinv-choices__handle',
				axis: 'y',
				update: function () {
					syncChoices( $box );
				},
			} );
		}
		$box.on( 'change', 'input[type="checkbox"]', function () {
			syncChoices( $box );
		} );
		if ( $box.hasClass( 'dinv-choices--list' ) ) {
			$box.on( 'keydown', 'input[type="checkbox"]', function ( event ) {
				if ( ! event.altKey || ( event.key !== 'ArrowUp' && event.key !== 'ArrowDown' ) ) {
					return;
				}
				event.preventDefault();
				var $item = $( this ).closest( 'li' );
				if ( event.key === 'ArrowUp' ) {
					$item.prev().before( $item );
				} else {
					$item.next().after( $item );
				}
				$( this ).trigger( 'focus' );
				syncChoices( $box );
			} );
		}
	}

	/* Live preview ------------------------------------------------------ */

	function Preview( $frame ) {
		this.$frame = $frame;
		this.iframe = $frame.find( 'iframe' )[ 0 ];
		this.viewport = $frame.find( '.dinv-preview__viewport' )[ 0 ];
		this.width = 1200;
		this.fixed = $frame.data( 'atts' ) || {};
		this.atts = {};
		this.settings = {};
		var self = this;

		$frame.on( 'click', '[data-dinv-device]', function () {
			self.width = Number( $( this ).data( 'dinv-device' ) );
			$frame.find( '[data-dinv-device]' ).attr( 'aria-pressed', 'false' );
			$( this ).attr( 'aria-pressed', 'true' );
			self.fit();
		} );
		$( window ).on( 'resize', function () {
			self.fit();
		} );
		this.reload = debounce( function () {
			self.load();
		}, 500 );
		this.fit();
	}

	Preview.prototype.fit = function () {
		var available = this.viewport.clientWidth || 600;
		var scale = Math.min( 1, available / this.width );
		var height = 760;
		this.iframe.style.width = this.width + 'px';
		this.iframe.style.height = height / scale + 'px';
		this.iframe.style.transform = 'scale(' + scale + ')';
		this.viewport.style.height = height + 'px';
	};

	Preview.prototype.load = function () {
		if ( ! data.previewUrl ) {
			return;
		}
		var url = new URL( data.previewUrl );
		var add = function ( group, values ) {
			Object.keys( values ).forEach( function ( key ) {
				if ( values[ key ] !== '' && values[ key ] !== undefined ) {
					url.searchParams.set( group + '[' + key + ']', values[ key ] );
				}
			} );
		};
		add( 'atts', $.extend( {}, this.fixed, this.atts ) );
		add( 'settings', this.settings );
		this.iframe.src = url.toString();
	};

	Preview.prototype.update = function ( atts, settings ) {
		if ( atts ) {
			this.atts = atts;
		}
		if ( settings ) {
			this.settings = settings;
		}
		this.reload();
	};

	function debounce( fn, delay ) {
		var timer;
		return function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( fn, delay );
		};
	}

	/* Design screen ----------------------------------------------------- */

	function designSettings( $form ) {
		var settings = {};
		$form.find( '[name^="design_"], [name="use_theme_styles"]' ).each( function () {
			var $field = $( this );
			if ( $field.attr( 'type' ) === 'checkbox' ) {
				if ( $field.prop( 'checked' ) ) {
					settings[ this.name ] = '1';
				}
				return;
			}
			if ( $field.attr( 'type' ) === 'hidden' && $form.find( '[type="checkbox"][name="' + this.name + '"]' ).length ) {
				settings[ this.name ] = settings[ this.name ] || '0';
				return;
			}
			settings[ this.name ] = $field.val();
		} );
		return settings;
	}

	function applyPreset( $form, preview ) {
		var preset = ( data.presets || {} )[ $( '#dinv-design-preset' ).val() ];
		if ( ! preset ) {
			return;
		}
		Object.keys( preset ).forEach( function ( name ) {
			if ( name === 'label' ) {
				return;
			}
			var $field = $form.find( '[name="' + name + '"]' ).not( '[type="hidden"]' );
			if ( $field.hasClass( 'dinv-color-field' ) && typeof $field.wpColorPicker === 'function' ) {
				$field.wpColorPicker( 'color', preset[ name ] );
			} else {
				$field.val( preset[ name ] );
			}
		} );
		if ( preview ) {
			preview.update( null, designSettings( $form ) );
		}
	}

	$( function () {
		$( '[data-dinv-choices]' ).each( function () {
			initChoices( $( this ) );
		} );

		var previews = [];
		$( '[data-dinv-preview-frame]' ).each( function () {
			previews.push( new Preview( $( this ) ) );
		} );
		window.DinvPreview = previews[ 0 ] || null;

		var $design = $( '#dinv-design-form' );
		if ( $design.length ) {
			var preview = previews[ 0 ];
			var refresh = function () {
				if ( preview ) {
					preview.update( null, designSettings( $design ) );
				}
			};
			if ( typeof $.fn.wpColorPicker === 'function' ) {
				$( '.dinv-color-field' ).wpColorPicker( {
					change: function () {
						window.setTimeout( refresh, 0 );
					},
					clear: function () {
						window.setTimeout( refresh, 0 );
					},
				} );
			}
			$design.on( 'input change', 'input, select', refresh );
			$( '#dinv-apply-preset' ).on( 'click', function ( event ) {
				event.preventDefault();
				applyPreset( $design, preview );
			} );
			refresh();
		}

		$( '[data-dinv-confirm]' ).on( 'click', function ( event ) {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( $( this ).data( 'dinv-confirm' ) ) ) {
				event.preventDefault();
			}
		} );
	} );
} )( jQuery );
