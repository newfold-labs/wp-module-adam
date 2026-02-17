<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Response;

/**
 * Sanitizes Adam API response items (productMarkup HTML).
 */
class AdamResponseSanitizer {

	/**
	 * Sanitize each item's productMarkup with wp_kses and strip Cloudflare scripts.
	 *
	 * @param array<int, array> $items Raw response items from Adam.
	 * @return array<int, array> Items with sanitized bodyContent/htmlString.
	 */
	public function sanitize( array $items ) {
		$allowed_html = $this->get_allowed_html_for_frag();
		$out          = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['adDetails']['productMarkup'] ) ) {
				continue;
			}
			$markup = &$item['adDetails']['productMarkup'];
			if ( ! empty( $markup['bodyContent'] ) && is_string( $markup['bodyContent'] ) ) {
				$markup['bodyContent'] = $this->strip_cloudflare_scripts( $markup['bodyContent'] );
				$markup['bodyContent'] = wp_kses( $markup['bodyContent'], $allowed_html );
			}
			if ( ! empty( $markup['htmlString'] ) && is_string( $markup['htmlString'] ) ) {
				$markup['htmlString'] = $this->strip_cloudflare_scripts( $markup['htmlString'] );
				$markup['htmlString'] = wp_kses( $markup['htmlString'], $allowed_html );
			}
			$out[] = $item;
		}

		return $out;
	}

	/**
	 * Remove Cloudflare challenge script blocks from HTML.
	 *
	 * @param string $html Raw HTML string.
	 * @return string HTML with CF challenge scripts removed.
	 */
	private function strip_cloudflare_scripts( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		return preg_replace_callback(
			'/<script\b[^>]*>(.*?)<\/script>/si',
			function ( $matches ) {
				$full = $matches[0];
				if (
					false !== strpos( $full, '__CF$cv$params' ) ||
					false !== strpos( $full, 'challenge-platform' ) ||
					false !== strpos( $full, 'cdn-cgi/challenge-platform' )
				) {
					return '';
				}
				return $full;
			},
			$html
		);
	}

	/**
	 * Allowed HTML for fragment content (wp_kses).
	 *
	 * @return array<string, array<string, bool>>
	 */
	private function get_allowed_html_for_frag() {
		return array(
			'div'    => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'span'   => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'a'      => array(
				'class'              => true,
				'href'               => true,
				'target'             => true,
				'rel'                => true,
				'data-element-type'  => true,
				'data-outcome'       => true,
				'data-description'   => true,
				'adtrackingbannerid' => true,
			),
			'img'    => array(
				'src'    => true,
				'alt'    => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			),
			'p'      => array(
				'class' => true,
				'style' => true,
			),
			'style'  => array(),
			'link'   => array(
				'rel'  => true,
				'href' => true,
			),
			'script' => array(
				'type' => true,
				'src'  => true,
			),
		);
	}
}
