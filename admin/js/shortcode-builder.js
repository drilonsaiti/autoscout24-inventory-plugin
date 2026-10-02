/*
 * Shortcode builder.
 *
 * Every field carries the site-wide value in data-default. Only attributes
 * that differ from it are written into the shortcode, so the result stays
 * short and keeps following later changes of the site-wide settings. The
 * live preview shows the result with the theme.
 */
( function () {
	'use strict';

	var config = window.DinvBuilder || { tag: 'dealer_inventory', copied: 'Copied' };

	function clean( value ) {
		return String( value ).replace( /["\[\]]/g, '' ).trim();
	}

	function fieldValue( field ) {
		if ( field.type === 'checkbox' ) {
			return field.checked ? 'yes' : 'no';
		}
		return String( field.value || '' ).trim();
	}

	function collect( form ) {
		var atts = {};
		form.querySelectorAll( '[name][data-default]' ).forEach( function ( field ) {
			var current = fieldValue( field );
			var initial = String( field.dataset.default || '' );
			if ( current === initial || ( current === '' && field.type !== 'checkbox' ) ) {
				return;
			}
			atts[ field.name ] = clean( current );
		} );
		return atts;
	}

	function build( atts ) {
		var parts = [ '[' + config.tag ];
		Object.keys( atts ).forEach( function ( name ) {
			parts.push( name + '="' + atts[ name ] + '"' );
		} );
		return parts.join( ' ' ) + ']';
	}

	function init( form ) {
		var output = form.querySelector( '[data-dinv-shortcode-output]' );
		var status = form.querySelector( '[data-dinv-copy-status]' );

		var update = function () {
			var atts = collect( form );
			output.value = build( atts );
			if ( window.DinvPreview ) {
				window.DinvPreview.update( atts );
			}
		};

		form.addEventListener( 'input', update );
		form.addEventListener( 'change', update );
		if ( window.jQuery ) {
			// Choice lists report changes through jQuery (drag and drop).
			window.jQuery( form ).on( 'change', '[data-dinv-choices-value]', update );
		}

		form.querySelector( '[data-dinv-builder-reset]' ).addEventListener( 'click', function () {
			form.querySelectorAll( '[name][data-default]' ).forEach( function ( field ) {
				if ( field.type === 'checkbox' ) {
					field.checked = field.dataset.default === 'yes';
				} else if ( field.hasAttribute( 'data-dinv-choices-value' ) ) {
					var selected = field.dataset.default === 'none' ? [] : field.dataset.default.split( ',' );
					var box = field.closest( '[data-dinv-choices]' );
					var list = box.querySelector( 'ul' );
					selected.forEach( function ( value ) {
						var item = list.querySelector( 'li[data-value="' + value + '"]' );
						if ( item ) {
							list.appendChild( item );
						}
					} );
					list.querySelectorAll( 'li' ).forEach( function ( item ) {
						if ( selected.indexOf( item.dataset.value ) === -1 ) {
							list.appendChild( item );
						}
						item.querySelector( 'input' ).checked = selected.indexOf( item.dataset.value ) !== -1;
					} );
					field.value = field.dataset.default;
				} else {
					field.value = field.dataset.default || '';
				}
			} );
			update();
		} );

		form.querySelector( '[data-dinv-copy-shortcode]' ).addEventListener( 'click', function () {
			var done = function () {
				status.textContent = config.copied;
				window.setTimeout( function () {
					status.textContent = '';
				}, 2000 );
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( output.value ).then( done );
			} else {
				output.select();
				document.execCommand( 'copy' );
				done();
			}
		} );

		update();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			document.querySelectorAll( '[data-dinv-shortcode-builder]' ).forEach( init );
		} );
	} else {
		document.querySelectorAll( '[data-dinv-shortcode-builder]' ).forEach( init );
	}
} )();
