<?php
/**
 * Content rules checked against the rendered HTML of a post.
 *
 * Covers only what authors control. Theme issues (header contrast,
 * landmarks, menus) belong to a per-template audit, not to this plugin.
 */

defined( 'ABSPATH' ) || exit;

class A11yCC_Checker {

	const FILE_EXTENSIONS = 'pdf|docx?|xlsx?|pptx?|odt|ods|odp|csv|zip';

	const GENERIC_LINK_TEXTS = array(
		// English.
		'here', 'click here', 'click', 'read more', 'learn more', 'more', 'link', 'download', 'see more',
		// Portuguese.
		'aqui', 'clique aqui', 'clique', 'saiba mais', 'leia mais', 'veja mais', 'mais', 'acesse', 'confira', 'veja', 'baixe',
	);

	/**
	 * Rule catalog: level, WCAG success criterion, ABNT NBR 17225 item, message.
	 */
	public static function rules() {
		$rules = array(
			'img-missing-alt'    => array( 'error', '1.1.1', '', __( 'Image without alternative text. Describe the image or mark it as decorative.', 'a11y-content-checker' ) ),
			'alt-is-filename'    => array( 'error', '1.1.1', '', __( 'The alternative text looks like a file name. Describe what the image shows.', 'a11y-content-checker' ) ),
			'link-no-name'       => array( 'error', '2.4.4', '', __( 'Link without text. Screen reader users cannot tell where it goes.', 'a11y-content-checker' ) ),
			'link-generic'       => array( 'warning', '2.4.4', '', __( 'Generic link text. Use the destination as the text, for example "VPN request form".', 'a11y-content-checker' ) ),
			'link-file-no-info'  => array( 'warning', '2.4.4', '', __( 'Link to a file without its format and size in the text, for example "User guide (PDF, 2 MB)".', 'a11y-content-checker' ) ),
			'heading-skipped'    => array( 'warning', '1.3.1', '', __( 'Skipped heading level. Headings should follow their order, without jumping from H2 to H4.', 'a11y-content-checker' ) ),
			'heading-multi-h1'   => array( 'warning', '1.3.1', '', __( 'More than one H1 in the content. A page should have a single main heading.', 'a11y-content-checker' ) ),
			'heading-fake'       => array( 'warning', '1.3.1', '', __( 'Bold paragraph used as a heading. Use a heading style (H2, H3) from the editor.', 'a11y-content-checker' ) ),
			'text-all-caps'      => array( 'warning', '3.1.5', '', __( 'Long passage in capital letters, which is harder to read and may be spelled out by some screen readers.', 'a11y-content-checker' ) ),
			'table-no-header'    => array( 'error', '1.3.1', '', __( 'Table without a header row or column.', 'a11y-content-checker' ) ),
			'iframe-no-title'    => array( 'error', '4.1.2', '', __( 'Embedded content without a title. Say what it contains.', 'a11y-content-checker' ) ),
			'video-captions'     => array( 'warning', '1.2.2', '', __( 'Embedded video. Check that it has reviewed captions, not only automatic ones.', 'a11y-content-checker' ) ),
			'image-little-text'  => array( 'warning', '1.1.1', '', __( 'Post with an image and little text. If the image is a poster, repeat its text in the post.', 'a11y-content-checker' ) ),
		);

		return apply_filters( 'a11ycc_rules', $rules );
	}

	/**
	 * @param string $html Rendered content (without the theme header and footer).
	 * @return array List of issues: rule, level, wcag, nbr, message, snippet.
	 */
	public static function check( $html ) {
		$issues = array();
		if ( '' === trim( (string) $html ) ) {
			return $issues;
		}

		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		$xp = new DOMXPath( $doc );

		self::images( $xp, $issues );
		self::links( $xp, $issues );
		self::headings( $xp, $issues );
		self::texts( $xp, $issues );
		self::tables( $xp, $issues );
		self::embeds( $xp, $issues );

		return apply_filters( 'a11ycc_issues', $issues, $html );
	}

	private static function issue( array &$issues, $rule, DOMNode $node = null ) {
		$rules = self::rules();
		if ( ! isset( $rules[ $rule ] ) ) {
			return;
		}
		list( $level, $wcag, $nbr, $message ) = $rules[ $rule ];
		$issues[] = array(
			'rule'    => $rule,
			'level'   => $level,
			'wcag'    => $wcag,
			'nbr'     => $nbr,
			'message' => $message,
			'snippet' => $node ? self::snippet( $node ) : '',
		);
	}

	private static function snippet( DOMNode $node ) {
		$html = $node->ownerDocument->saveHTML( $node );
		return mb_strlen( $html ) > 200 ? mb_substr( $html, 0, 200 ) . '…' : $html;
	}

	private static function text( DOMNode $node ) {
		return trim( preg_replace( '/\s+/u', ' ', $node->textContent ) );
	}

	private static function images( DOMXPath $xp, array &$issues ) {
		foreach ( $xp->query( '//img' ) as $img ) {
			if ( ! $img->hasAttribute( 'alt' ) ) {
				self::issue( $issues, 'img-missing-alt', $img );
				continue;
			}
			$alt = trim( $img->getAttribute( 'alt' ) );
			if ( preg_match( '/\.(jpe?g|png|gif|webp|svg)$/i', $alt )
				|| preg_match( '/^(img|dsc|image|imagem|photo|foto|screenshot|captura)[\s_-]*\d*$/i', $alt ) ) {
				self::issue( $issues, 'alt-is-filename', $img );
			}
		}

		$words = preg_match_all( '/\p{L}+/u', self::text( $xp->document->documentElement ) );
		if ( $xp->query( '//img' )->length > 0 && $words < 30 ) {
			self::issue( $issues, 'image-little-text' );
		}
	}

	private static function links( DOMXPath $xp, array &$issues ) {
		foreach ( $xp->query( '//a[@href]' ) as $a ) {
			$name = self::text( $a );
			if ( '' === $name ) {
				$name = trim( $a->getAttribute( 'aria-label' ) );
			}
			if ( '' === $name ) {
				foreach ( $xp->query( './/img[@alt]', $a ) as $img ) {
					$name .= trim( $img->getAttribute( 'alt' ) );
				}
			}

			if ( '' === $name ) {
				self::issue( $issues, 'link-no-name', $a );
				continue;
			}
			if ( in_array( mb_strtolower( rtrim( $name, '.:!' ) ), self::GENERIC_LINK_TEXTS, true ) ) {
				self::issue( $issues, 'link-generic', $a );
			}

			$path = (string) wp_parse_url( $a->getAttribute( 'href' ), PHP_URL_PATH );
			if ( preg_match( '/\.(' . self::FILE_EXTENSIONS . ')$/i', $path, $m )
				&& ! preg_match( '/\b' . preg_quote( $m[1], '/' ) . '\b/i', $name ) ) {
				self::issue( $issues, 'link-file-no-info', $a );
			}
		}
	}

	private static function headings( DOMXPath $xp, array &$issues ) {
		$previous = (int) apply_filters( 'a11ycc_first_heading_level', 1 );
		$h1       = 0;

		foreach ( $xp->query( '//h1|//h2|//h3|//h4|//h5|//h6' ) as $h ) {
			$level = (int) substr( $h->nodeName, 1 );
			if ( 1 === $level && ++$h1 > 1 ) {
				self::issue( $issues, 'heading-multi-h1', $h );
			}
			if ( $level > $previous + 1 ) {
				self::issue( $issues, 'heading-skipped', $h );
			}
			$previous = $level;
		}

		// A lone <p><strong>Short text</strong></p> is usually a visual heading.
		foreach ( $xp->query( '//p[count(*)=1][strong or b]' ) as $p ) {
			$text  = self::text( $p );
			$child = $xp->query( './*[1]', $p )->item( 0 );
			if ( $text !== '' && $text === self::text( $child )
				&& mb_strlen( $text ) <= 80 && ! preg_match( '/[.!?]$/u', $text ) ) {
				self::issue( $issues, 'heading-fake', $p );
			}
		}
	}

	private static function texts( DOMXPath $xp, array &$issues ) {
		foreach ( $xp->query( '//p|//li|//h1|//h2|//h3|//h4|//h5|//h6' ) as $node ) {
			$letters = preg_replace( '/[^\p{L}]/u', '', self::text( $node ) );
			if ( mb_strlen( $letters ) >= 20 && mb_strtoupper( $letters ) === $letters ) {
				self::issue( $issues, 'text-all-caps', $node );
			}
		}
	}

	private static function tables( DOMXPath $xp, array &$issues ) {
		foreach ( $xp->query( '//table' ) as $table ) {
			if ( $xp->query( './/th', $table )->length === 0
				&& $xp->query( './/tr', $table )->length > 1 ) {
				self::issue( $issues, 'table-no-header', $table );
			}
		}
	}

	private static function embeds( DOMXPath $xp, array &$issues ) {
		foreach ( $xp->query( '//iframe' ) as $iframe ) {
			if ( '' === trim( $iframe->getAttribute( 'title' ) ) ) {
				self::issue( $issues, 'iframe-no-title', $iframe );
			}
			if ( preg_match( '#(youtube\.com|youtu\.be|vimeo\.com)#i', $iframe->getAttribute( 'src' ) ) ) {
				self::issue( $issues, 'video-captions', $iframe );
			}
		}
		foreach ( $xp->query( '//video' ) as $video ) {
			self::issue( $issues, 'video-captions', $video );
		}
	}
}
