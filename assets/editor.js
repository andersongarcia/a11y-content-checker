/* Pre-publish panel in the block editor. It informs; it does not block. */
( function ( wp ) {
	'use strict';

	var h = wp.element.createElement;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var Panel = wp.editPost.PluginPrePublishPanel;
	var data = window.a11ycc || {};

	function AccessibilityPanel() {
		var state = useState( null );
		var issues = state[ 0 ];
		var setIssues = state[ 1 ];

		useEffect( function () {
			var content = wp.data.select( 'core/editor' ).getEditedPostContent();
			wp.apiFetch( { path: '/a11y-content-checker/v1/check', method: 'POST', data: { content: content } } )
				.then( function ( r ) { setIssues( r.issues ); } )
				.catch( function () { setIssues( false ); } );
		}, [] );

		var body;
		if ( issues === null ) {
			body = h( 'p', { role: 'status' }, __( 'Checking…', 'a11y-content-checker' ) );
		} else if ( issues === false ) {
			body = h( 'p', { role: 'status' }, __( 'The check could not run right now.', 'a11y-content-checker' ) );
		} else if ( ! issues.length ) {
			body = h( 'p', { role: 'status' }, __( 'No automatic issues. Review the checklist too.', 'a11y-content-checker' ) );
		} else {
			body = h( 'div', null,
				/* translators: %d: number of issues */
				h( 'p', { role: 'status' }, sprintf( __( '%d issue(s) found:', 'a11y-content-checker' ), issues.length ) ),
				h( 'ul', null, issues.map( function ( issue, i ) {
					return h( 'li', { key: i },
						h( 'strong', null, issue.level === 'error'
							? __( 'Error:', 'a11y-content-checker' ) + ' '
							: __( 'Warning:', 'a11y-content-checker' ) + ' ' ),
						issue.message );
				} ) )
			);
		}

		return h( Panel, { title: __( 'Accessibility', 'a11y-content-checker' ), initialOpen: true },
			body,
			data.checklist ? h( 'p', null, h( 'a', { href: data.checklist, target: '_blank', rel: 'noopener' },
				__( 'Publishing checklist (opens in a new tab)', 'a11y-content-checker' ) ) ) : null
		);
	}

	wp.plugins.registerPlugin( 'a11y-content-checker', { render: AccessibilityPanel } );
}( window.wp ) );
