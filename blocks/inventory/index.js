/**
 * Vehicle Inventory block (no build step: plain wp.element calls).
 *
 * Controls are generated from the settings schema (window.DinvBlock), so
 * the block offers exactly the options of the shortcode. Empty values
 * inherit the site-wide settings.
 */
( function ( wp, data ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! data ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;

	function optionLabel( field, value ) {
		var options = field.options || [];
		for ( var i = 0; i < options.length; i++ ) {
			if ( options[ i ].value === value ) {
				return options[ i ].label;
			}
		}
		return value;
	}

	function globalText( field ) {
		var value = field.global;
		if ( field.type === 'bool' ) {
			return value === 'yes' ? __( 'Yes', 'dealer-inventory-for-autoscout24' ) : __( 'No', 'dealer-inventory-for-autoscout24' );
		}
		if ( field.type === 'enum' ) {
			return optionLabel( field, value );
		}
		if ( field.type === 'multi' || field.type === 'list' ) {
			if ( value === 'none' || value === '' ) {
				return __( 'None', 'dealer-inventory-for-autoscout24' );
			}
			return value.split( ',' ).map( function ( item ) {
				return optionLabel( field, item );
			} ).join( ', ' );
		}
		return value;
	}

	function defaultLabel( field ) {
		if ( field.group === 'preset' ) {
			return __( 'Any', 'dealer-inventory-for-autoscout24' );
		}
		/* translators: %s: current site-wide value. */
		return sprintf( __( 'Default (%s)', 'dealer-inventory-for-autoscout24' ), globalText( field ) );
	}

	/**
	 * Ordered / unordered choice list with an "override" switch.
	 */
	function ChoiceList( props ) {
		var field = props.field;
		var value = props.value;
		var overridden = value !== '';
		var current = overridden ? value : field.global;
		var selected = current === 'none' || current === '' ? [] : current.split( ',' );
		var options = field.options || [];

		var ordered = options.slice();
		if ( field.type === 'list' ) {
			ordered.sort( function ( a, b ) {
				var ia = selected.indexOf( a.value );
				var ib = selected.indexOf( b.value );
				if ( ia === -1 && ib === -1 ) {
					return 0;
				}
				if ( ia === -1 ) {
					return 1;
				}
				if ( ib === -1 ) {
					return -1;
				}
				return ia - ib;
			} );
		}

		function save( list ) {
			props.onChange( list.length ? list.join( ',' ) : 'none' );
		}

		function toggle( item, checked ) {
			var list = selected.filter( function ( v ) {
				return v !== item;
			} );
			if ( checked ) {
				if ( field.type === 'multi' ) {
					list = options.map( function ( o ) {
						return o.value;
					} ).filter( function ( v ) {
						return v === item || list.indexOf( v ) !== -1;
					} );
				} else {
					list.push( item );
				}
			}
			save( list );
		}

		function move( item, step ) {
			var list = selected.slice();
			var index = list.indexOf( item );
			var target = index + step;
			if ( index === -1 || target < 0 || target >= list.length ) {
				return;
			}
			list.splice( index, 1 );
			list.splice( target, 0, item );
			save( list );
		}

		return el(
			'div',
			{ className: 'dinv-block-choices' },
			el( C.ToggleControl, {
				label: field.label,
				help: overridden ? field.help : defaultLabel( field ),
				checked: overridden,
				onChange: function ( on ) {
					props.onChange( on ? field.global || 'none' : '' );
				},
				__nextHasNoMarginBottom: true,
			} ),
			overridden &&
				el(
					'div',
					{ style: { margin: '8px 0 16px' } },
					ordered.map( function ( option ) {
						var isOn = selected.indexOf( option.value ) !== -1;
						return el(
							'div',
							{ key: option.value, style: { display: 'flex', alignItems: 'center', gap: '4px' } },
							el( 'div', { style: { flex: 1 } }, el( C.CheckboxControl, {
								label: option.label,
								checked: isOn,
								onChange: function ( checked ) {
									toggle( option.value, checked );
								},
								__nextHasNoMarginBottom: true,
							} ) ),
							field.type === 'list' && isOn &&
								el( C.Button, {
									icon: 'arrow-up-alt2',
									label: __( 'Move up', 'dealer-inventory-for-autoscout24' ),
									size: 'small',
									onClick: function () {
										move( option.value, -1 );
									},
								} ),
							field.type === 'list' && isOn &&
								el( C.Button, {
									icon: 'arrow-down-alt2',
									label: __( 'Move down', 'dealer-inventory-for-autoscout24' ),
									size: 'small',
									onClick: function () {
										move( option.value, 1 );
									},
								} )
						);
					} )
				)
		);
	}

	function control( field, value, onChange ) {
		var common = {
			key: field.attr,
			label: field.label,
			help: field.help || undefined,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		};

		if ( field.type === 'multi' || field.type === 'list' ) {
			return el( ChoiceList, { key: field.attr, field: field, value: value, onChange: onChange } );
		}

		if ( field.type === 'bool' ) {
			return el( C.SelectControl, Object.assign( {}, common, {
				value: value,
				options: [
					{ value: '', label: defaultLabel( field ) },
					{ value: 'yes', label: __( 'Yes', 'dealer-inventory-for-autoscout24' ) },
					{ value: 'no', label: __( 'No', 'dealer-inventory-for-autoscout24' ) },
				],
				onChange: onChange,
			} ) );
		}

		if ( field.options && ( field.type === 'enum' || field.type === 'filter_value' ) ) {
			return el( C.SelectControl, Object.assign( {}, common, {
				value: value,
				options: [ { value: '', label: defaultLabel( field ) } ].concat( field.options ),
				onChange: onChange,
			} ) );
		}

		var numeric = field.type === 'int' || field.type === 'number';
		return el( C.TextControl, Object.assign( {}, common, {
			type: numeric ? 'number' : 'text',
			min: field.min,
			max: field.max,
			value: value,
			placeholder: field.global || ( field.group === 'preset' ? __( 'Any', 'dealer-inventory-for-autoscout24' ) : '' ),
			onChange: onChange,
		} ) );
	}

	wp.blocks.registerBlockType( 'dealer-inventory/inventory', {
		edit: function ( props ) {
			var settings = props.attributes.settings || {};
			var blockProps = useBlockProps();

			function set( attr, value ) {
				var next = Object.assign( {}, settings );
				if ( value === '' || value === undefined || value === null ) {
					delete next[ attr ];
				} else {
					next[ attr ] = String( value );
				}
				props.setAttributes( { settings: next } );
			}

			var groups = data.schema.groups;
			var panels = Object.keys( groups ).map( function ( group, index ) {
				var fields = data.schema.fields.filter( function ( field ) {
					return field.group === group;
				} );
				var lastSection = null;
				var children = [];
				fields.forEach( function ( field ) {
					if ( field.section && field.section !== lastSection ) {
						lastSection = field.section;
						children.push( el( 'h3', { key: 'section-' + field.section, className: 'dinv-block-section', style: { fontSize: '11px', textTransform: 'uppercase', margin: '20px 0 8px', color: '#757575' } }, field.section ) );
					}
					children.push(
						control( field, settings[ field.attr ] === undefined ? '' : String( settings[ field.attr ] ), function ( value ) {
							set( field.attr, value );
						} )
					);
				} );
				return el( C.PanelBody, { key: group, title: groups[ group ], initialOpen: index === 0 }, children );
			} );

			panels.push(
				el(
					C.PanelBody,
					{ key: 'site', title: __( 'Site-wide settings', 'dealer-inventory-for-autoscout24' ), initialOpen: false },
					el( 'p', null, __( 'Empty fields use the site-wide settings. Colors and fonts are set under Dealer Inventory → Design.', 'dealer-inventory-for-autoscout24' ) ),
					el( C.ExternalLink, { href: data.settingsUrl }, __( 'Open the settings', 'dealer-inventory-for-autoscout24' ) )
				)
			);

			return el(
				Fragment,
				null,
				el( InspectorControls, null, panels ),
				el(
					'div',
					blockProps,
					el( ServerSideRender, {
						block: 'dealer-inventory/inventory',
						attributes: { settings: settings },
						skipBlockSupportAttributes: true,
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp, window.DinvBlock );
