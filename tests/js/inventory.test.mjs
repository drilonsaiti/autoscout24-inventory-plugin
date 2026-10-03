/**
 * Smoke test of the inventory filter UI (public/js/inventory.js).
 *
 * Loads server-rendered fixtures (tests/js/fixtures, regenerated with
 * `wp eval-file tests/js/build-fixtures.php`) into jsdom, runs the real
 * script and checks the main interactions with a mocked REST API.
 *
 * Run: npm run test:js
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { JSDOM } from 'jsdom';

const root = new URL( '../../', import.meta.url );
const script = readFileSync( new URL( 'public/js/inventory.js', root ), 'utf8' );
const fixture = ( name ) => readFileSync( new URL( `tests/js/fixtures/${ name }.html`, root ), 'utf8' );

const VEHICLE = ( id ) => `<article class="dinv-vehicle dinv-card" data-vehicle-id="${ id }"><h3 class="dinv-vehicle__title"><a class="dinv-vehicle__link" href="/v/${ id }">Car ${ id }</a></h3></article>`;

/**
 * Build a page with one inventory and a mocked fetch.
 *
 * @param {string}  name   Fixture name.
 * @param {Object}  opts   Options: mobile (bool).
 */
async function setup( name, opts = {} ) {
	const html = fixture( name ).replace( /data-rendered="\d+"/, `data-rendered="${ Math.floor( Date.now() / 1000 ) }"` );
	const dom = new JSDOM( `<!doctype html><html lang="en"><body>${ html }</body></html>`, {
		url: 'https://example.org/cars/',
		runScripts: 'outside-only',
		pretendToBeVisual: true,
	} );
	const { window } = dom;
	const calls = [];

	window.matchMedia = ( query ) => ( {
		matches: Boolean( opts.mobile ) && query.includes( 'max-width' ),
		media: query,
		addEventListener() {},
		removeEventListener() {},
	} );
	window.HTMLElement.prototype.scrollIntoView = () => {};
	window.scrollTo = () => {};
	window.DinvInventory = {
		restBase: 'https://example.org/wp-json/dinv/v1',
		labels: { all_makes: 'All makes', all_models: 'All models', choose_make_first: 'Choose a make first', make: 'Make', model: 'Model', no_matches: 'No matches', reset: 'Reset' },
	};
	window.fetch = async ( url ) => {
		const parsed = new URL( url );
		calls.push( parsed );
		const items = parsed.searchParams.get( 'dinv_part' ) === 'items';
		const body = items
			? { success: true, total: 9, countLabel: 'vehicles', page: 2, totalPages: 3, html: VEHICLE( 101 ) + VEHICLE( 102 ), pagination: '<div class="dinv-loadmore"><a href="?p=3" data-page="3" data-dinv-more>More</a></div>' }
			: {
				success: true,
				total: 2,
				countLabel: 'vehicles',
				page: 1,
				totalPages: 1,
				layout: parsed.searchParams.get( 'dinv_view' ) === 'list' ? 'list' : 'card',
				html: `<div class="dinv-items" data-dinv-items>${ VEHICLE( 1 ) + VEHICLE( 2 ) }</div>`,
				pagination: '',
				facets: { makes: { bmw: 2 }, models: { bmw: { x5: 2 } } },
			};
		return { ok: true, status: 200, json: async () => body };
	};

	window.eval( script );

	// The script initializes on DOMContentLoaded.
	if ( window.document.readyState === 'loading' ) {
		await new Promise( ( resolve ) => window.document.addEventListener( 'DOMContentLoaded', resolve ) );
	}

	const wait = ( ms = 20 ) => new Promise( ( resolve ) => window.setTimeout( resolve, ms ) );
	const change = ( el, value ) => {
		el.value = value;
		el.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
	};

	return { window, document: window.document, calls, wait, change };
}

test( 'initializes without requests when the page is fresh', async () => {
	const { document, calls, wait } = await setup( 'separate' );
	await wait( 50 );

	assert.ok( document.querySelector( '.dinv-inventory' ).classList.contains( 'dinv-is-ready' ) );
	assert.equal( calls.length, 0 );
} );

test( 'choosing a make loads results, fills models and updates URL, count and live region', async () => {
	const { window, document, calls, wait, change } = await setup( 'separate' );
	const make = document.querySelector( '[data-dinv-make]' );
	const model = document.querySelector( '[data-dinv-model]' );

	assert.ok( model.disabled, 'Model is disabled until a make is chosen.' );
	change( make, 'bmw' );
	await wait( 120 );

	assert.equal( calls.length, 1 );
	const params = calls[ 0 ].searchParams;
	assert.equal( calls[ 0 ].pathname, '/wp-json/dinv/v1/vehicles' );
	assert.equal( params.get( 'dinv_make' ), 'bmw' );
	assert.equal( params.get( 'instance' ), 'main' );
	assert.equal( params.get( 'dinv_base' ), '/cars/' );

	assert.ok( ! model.disabled );
	assert.deepEqual( [ ...model.options ].map( ( o ) => o.value ), [ '', 'x5' ], 'Models follow the new facets.' );

	assert.equal( document.querySelectorAll( '[data-dinv-results] .dinv-vehicle' ).length, 2 );
	assert.equal( document.querySelector( '[data-dinv-count]' ).textContent, '2' );
	assert.match( document.querySelector( '[data-dinv-status]' ).textContent, /2 vehicles/ );
	assert.equal( new URL( window.location.href ).searchParams.get( 'dinv_main_make' ), 'bmw' );
	assert.equal( document.querySelector( '[data-dinv-active-count]' ).textContent, '1' );
} );

test( 'reset clears visitor filters', async () => {
	const { document, calls, wait, change } = await setup( 'separate' );
	change( document.querySelector( '[data-dinv-make]' ), 'audi' );
	await wait( 80 );

	document.querySelector( '.dinv-filters__reset' ).click();
	await wait( 80 );

	assert.equal( document.querySelector( '[data-dinv-make]' ).value, '' );
	assert.equal( calls.at( -1 ).searchParams.get( 'dinv_make' ), null );
} );

test( 'mobile drawer opens as a modal dialog and closes with Escape', async () => {
	const { window, document, wait } = await setup( 'separate', { mobile: true } );
	const toggle = document.querySelector( '[data-dinv-filters-toggle]' );
	const panel = document.querySelector( '[data-dinv-panel]' );

	toggle.focus();
	toggle.click();
	await wait( 80 );

	assert.ok( panel.classList.contains( 'is-open' ) );
	assert.equal( panel.getAttribute( 'role' ), 'dialog' );
	assert.equal( panel.getAttribute( 'aria-modal' ), 'true' );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );
	assert.ok( document.activeElement.matches( '[data-dinv-drawer-close]' ), 'Focus moves into the panel.' );

	document.activeElement.dispatchEvent( new window.KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );

	assert.ok( ! panel.classList.contains( 'is-open' ) );
	assert.equal( panel.getAttribute( 'role' ), null );
	assert.equal( document.activeElement, toggle, 'Focus returns to the toggle.' );
} );

test( 'searchable make / model combobox works with the keyboard', async () => {
	const { window, document, calls, wait } = await setup( 'searchable' );
	const input = document.querySelector( '[role="combobox"]' );
	const list = document.getElementById( input.getAttribute( 'aria-controls' ) );

	assert.ok( input, 'Combobox is created.' );
	input.value = 'x5';
	input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );

	assert.equal( input.getAttribute( 'aria-expanded' ), 'true' );
	assert.ok( ! list.hidden );
	const active = document.getElementById( input.getAttribute( 'aria-activedescendant' ) );
	assert.match( active.textContent, /X5/ );

	input.dispatchEvent( new window.KeyboardEvent( 'keydown', { key: 'Enter', bubbles: true } ) );
	await wait( 80 );

	assert.equal( document.querySelector( '[data-dinv-make]' ).value, 'bmw' );
	assert.equal( document.querySelector( '[data-dinv-model]' ).value, 'x5' );
	assert.equal( input.getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( calls.at( -1 ).searchParams.get( 'dinv_model' ), 'x5' );
} );

test( 'load more appends vehicles and moves focus to the first new one', async () => {
	const { document, calls, wait } = await setup( 'searchable' );
	const before = document.querySelectorAll( '[data-dinv-items] .dinv-vehicle' ).length;

	document.querySelector( '[data-dinv-more]' ).click();
	await wait( 80 );

	const params = calls.at( -1 ).searchParams;
	assert.equal( params.get( 'dinv_part' ), 'items' );
	assert.equal( params.get( 'dinv_page' ), '2' );
	assert.equal( document.querySelectorAll( '[data-dinv-items] .dinv-vehicle' ).length, before + 2 );
	assert.equal( document.activeElement.getAttribute( 'href' ), '/v/101' );
	assert.equal( document.querySelector( '[data-dinv-more]' ).dataset.page, '3' );
} );

test( 'view switch reloads in list layout and remembers the choice', async () => {
	const { window, document, calls, wait } = await setup( 'searchable' );
	const list = document.querySelector( '[data-dinv-view="list"]' );

	list.click();
	await wait( 80 );

	assert.equal( list.getAttribute( 'aria-pressed' ), 'true' );
	assert.equal( calls.at( -1 ).searchParams.get( 'dinv_view' ), 'list' );
	assert.ok( document.querySelector( '.dinv-inventory' ).classList.contains( 'dinv-layout-list' ) );
	assert.equal( window.localStorage.getItem( 'dinv_view_side' ), 'list' );
} );

test( 'range slider writes the number field and loads after a pause', async () => {
	const { window, document, calls, wait } = await setup( 'searchable' );
	const range = document.querySelector( '[data-dinv-range="slider"]' );
	const to = range.querySelector( '[data-dinv-slider="to"]' );
	const toInput = range.querySelector( '[data-dinv-input="to"]' );

	assert.ok( ! range.querySelector( '[data-dinv-sliders]' ).hidden, 'Sliders are shown with JavaScript.' );

	const value = String( Number( range.dataset.min ) + Number( range.dataset.step ) );
	to.value = value;
	to.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );

	assert.equal( toInput.value, value );
	assert.ok( to.getAttribute( 'aria-valuetext' ) );
	await wait( 600 );

	const param = toInput.name.replace( /^dinv_side_/, 'dinv_' );
	assert.equal( calls.at( -1 ).searchParams.get( param ), value );
} );
