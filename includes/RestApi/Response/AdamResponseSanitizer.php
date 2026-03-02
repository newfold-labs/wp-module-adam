<?php

namespace NewfoldLabs\WP\Module\Adam\RestApi\Response;

/**
 * Sanitizes Adam API response items (productMarkup HTML).
 *
 * Security measures:
 * - <script> and <link> are not in the allowlist: any script/link in the HTML is stripped by wp_kses,
 *   preventing arbitrary JavaScript or external stylesheet injection if the Adam API or NFD_ADAM_URL
 *   were compromised. Cloudflare scripts are also removed before wp_kses (strip_cloudflare_scripts).
 * - Links are injected by the frontend from the linkTags array (structured data), not from HTML.
 * - <style> is allowed only because card layout and theming require inline CSS; it cannot execute
 *   JavaScript. Residual risk (e.g. CSS-based exfiltration) is accepted; the Adam API URL is
 *   validated in Config (allowed-hosts list and HTTPS). Consider CSP headers as defense-in-depth.
 * - Inline style attributes are sanitized with safecss_filter_attr() (WordPress core) to prevent
 *   CSS injection (clickjacking, hiding content, UI manipulation). Only whitelisted CSS properties
 *   are kept; dangerous rules (e.g. expression(), url() to arbitrary hosts) are stripped.
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
				$markup['bodyContent'] = $this->sanitize_style_attributes( $markup['bodyContent'] );
			}
			if ( ! empty( $markup['htmlString'] ) && is_string( $markup['htmlString'] ) ) {
				$markup['htmlString'] = $this->strip_cloudflare_scripts( $markup['htmlString'] );
				$markup['htmlString'] = wp_kses( $markup['htmlString'], $allowed_html );
				$markup['htmlString'] = $this->sanitize_style_attributes( $markup['htmlString'] );
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
	 * Sanitize inline style attribute values to prevent CSS injection (clickjacking, content hiding, UI manipulation).
	 * Uses WordPress safecss_filter_attr() which allows only a whitelist of CSS properties and strips
	 * dangerous values (e.g. expression(), javascript:, url() to untrusted hosts). Safe for card styling
	 * (color, margin, padding, display, etc.) seen in Adam API responses.
	 *
	 * @param string $html HTML string (after wp_kses) that may contain style="...".
	 * @return string HTML with style attribute values filtered; attributes removed if value becomes empty.
	 */
	private function sanitize_style_attributes( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! function_exists( 'safecss_filter_attr' ) ) {
			return $html;
		}
		return preg_replace_callback(
			'/\s+style\s*=\s*(["\'"])(.*?)\1/s',
			function ( $matches ) {
				$filtered = safecss_filter_attr( $matches[2] );
				if ( '' === $filtered ) {
					return '';
				}
				return ' style="' . esc_attr( $filtered ) . '"';
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
			'html'  => array(
				'lang' => true,
			),
			'head'  => array(),
			'body'  => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'title' => array(),
			'div'   => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'span'  => array(
				'class' => true,
				'id'    => true,
				'style' => true,
			),
			'b'     => array(
				'class' => true,
				'style' => true,
			),
			'a'     => array(
				'class'              => true,
				'href'               => true,
				'target'             => true,
				'rel'                => true,
				'aria-label'         => true,
				'data-ctb-id'        => true,
				'data-action'        => true,
				'data-ctb-action'    => true,
				'data-test-id'       => true,
				'data-element-type'  => true,
				'data-outcome'       => true,
				'data-description'   => true,
				'adtrackingbannerid' => true,
			),
			'img'   => array(
				'src'    => true,
				'alt'    => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			),
			'p'     => array(
				'class' => true,
				'style' => true,
			),
			'h2'    => array(
				'class' => true,
				'style' => true,
			),
			'h3'    => array(
				'class' => true,
				'style' => true,
			),
			'h6'    => array(
				'class' => true,
				'style' => true,
			),
			'ul'    => array(
				'class' => true,
				'style' => true,
			),
			'li'    => array(
				'class' => true,
				'style' => true,
			),
			'meta'  => array(
				'charset' => true,
				'name'    => true,
				'content' => true,
			),
			// Inline style required for card CSS; values sanitized via safecss_filter_attr(). script/link omitted.
			'style' => array(),
		);
	}
}
