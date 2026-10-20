/* Review mode: runs axe-core on the page and lists the issues in a panel. */
( function () {
	'use strict';

	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var data = window.a11ycc || { issues: [], checklist: '', scope: 'main' };
	var TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];
	var panel, highlighted;

	function el( tag, attrs, text ) {
		var node = document.createElement( tag );
		Object.keys( attrs || {} ).forEach( function ( k ) { node.setAttribute( k, attrs[ k ] ); } );
		if ( text ) { node.textContent = text; }
		return node;
	}

	function show( selector ) {
		var target = document.querySelector( selector );
		if ( ! target ) { return; }
		if ( highlighted ) { highlighted.classList.remove( 'a11ycc-highlight' ); }
		highlighted = target;
		target.classList.add( 'a11ycc-highlight' );
		target.scrollIntoView( { block: 'center' } );
	}

	function section( title, items, build ) {
		var s = el( 'section' );
		s.appendChild( el( 'h3', {}, sprintf( '%s (%d)', title, items.length ) ) );
		if ( ! items.length ) {
			s.appendChild( el( 'p', {}, __( 'Nothing found.', 'a11y-content-checker' ) ) );
			return s;
		}
		var list = el( 'ul' );
		items.forEach( function ( item ) { list.appendChild( build( item ) ); } );
		s.appendChild( list );
		return s;
	}

	function storedItem( issue ) {
		var li = el( 'li', { 'class': 'a11ycc-' + issue.level } );
		li.appendChild( el( 'strong', {}, issue.level === 'error'
			? __( 'Error:', 'a11y-content-checker' ) + ' '
			: __( 'Warning:', 'a11y-content-checker' ) + ' ' ) );
		li.appendChild( document.createTextNode( issue.message ) );
		if ( issue.snippet ) { li.appendChild( el( 'code', {}, issue.snippet ) ); }
		return li;
	}

	function axeItem( rule ) {
		var li = el( 'li' );
		li.appendChild( el( 'strong', {}, rule.help ) );
		var nodes = el( 'ul' );
		rule.nodes.slice( 0, 10 ).forEach( function ( node, i ) {
			/* translators: %d: occurrence number */
			var b = el( 'button', { type: 'button' }, sprintf( __( 'Show occurrence %d', 'a11y-content-checker' ), i + 1 ) );
			b.addEventListener( 'click', function () { show( node.target[ 0 ] ); } );
			var n = el( 'li' );
			n.appendChild( b );
			nodes.appendChild( n );
		} );
		li.appendChild( nodes );
		li.appendChild( el( 'a', { href: rule.helpUrl, target: '_blank', rel: 'noopener' },
			__( 'How to fix (opens in a new tab)', 'a11y-content-checker' ) ) );
		return li;
	}

	function close() {
		if ( highlighted ) { highlighted.classList.remove( 'a11ycc-highlight' ); }
		panel.close();
		document.querySelector( '#wp-admin-bar-a11ycc > a' ).focus();
	}

	function open() {
		if ( ! panel ) {
			panel = el( 'dialog', { 'class': 'a11ycc-panel', 'aria-labelledby': 'a11ycc-title' } );
			document.body.appendChild( panel );
			panel.addEventListener( 'keydown', function ( e ) { if ( e.key === 'Escape' ) { close(); } } );
		}
		panel.textContent = '';
		var title = el( 'h2', { id: 'a11ycc-title', tabindex: '-1' }, __( 'Accessibility of this page', 'a11y-content-checker' ) );
		var closeBtn = el( 'button', { type: 'button', 'class': 'a11ycc-close' }, __( 'Close', 'a11y-content-checker' ) );
		closeBtn.addEventListener( 'click', close );
		var status = el( 'p', { role: 'status' }, __( 'Checking…', 'a11y-content-checker' ) );
		panel.append( title, closeBtn, status );
		panel.show();
		title.focus();

		var context = { include: [ data.scope ], exclude: [ '#wpadminbar', '.a11ycc-panel' ] };
		if ( ! document.querySelector( data.scope ) ) { context.include = [ 'body' ]; }

		window.axe.run( context, {
			runOnly: { type: 'tag', values: TAGS },
			resultTypes: [ 'violations', 'incomplete' ]
		} ).then( function ( r ) {
			/* translators: 1: number of problems, 2: number of items to review manually */
			status.textContent = sprintf( __( '%1$d problem(s) and %2$d item(s) to review manually.', 'a11y-content-checker' ),
				r.violations.length, r.incomplete.length );
			panel.appendChild( section( __( 'Content, checked on save', 'a11y-content-checker' ), data.issues, storedItem ) );
			panel.appendChild( section( __( 'Page, problems found now', 'a11y-content-checker' ), r.violations, axeItem ) );
			panel.appendChild( section( __( 'Review manually', 'a11y-content-checker' ), r.incomplete, axeItem ) );
			var note = el( 'p', {}, __( 'Automatic checks find only part of the problems.', 'a11y-content-checker' ) + ' ' );
			if ( data.checklist ) {
				note.appendChild( el( 'a', { href: data.checklist }, __( 'Use the publishing checklist too.', 'a11y-content-checker' ) ) );
			}
			panel.appendChild( note );
		} ).catch( function () {
			status.textContent = __( 'The check could not run on this page.', 'a11y-content-checker' );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '#wp-admin-bar-a11ycc > a' ) ) {
			e.preventDefault();
			open();
		}
	} );
}() );
