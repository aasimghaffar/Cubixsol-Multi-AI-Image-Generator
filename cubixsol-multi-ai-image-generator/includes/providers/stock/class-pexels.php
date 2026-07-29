<?php
/**
 * Pexels stock source.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the Pexels photos API.
 */
class Pexels extends Stock_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'pexels';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Pexels', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://www.pexels.com/api/';
	}

	/** {@inheritDoc} */
	public function search( $query, $per_page ) {
		// Check: key must exist before any request is made.
		if ( ! $this->is_configured() ) {
			return $this->missing_key_error();
		}

		$data = $this->get_json(
			add_query_arg(
				array(
					'query'    => $query,
					'per_page' => $per_page,
				),
				'https://api.pexels.com/v1/search'
			),
			array( 'Authorization' => $this->get_api_key() )
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: a real search result always carries this field; a
		// 200 without it means the key was not actually accepted.
		$shape = $this->require_field( $data, 'photos' );
		if ( is_wp_error( $shape ) ) {
			return $shape;
		}

		$items = array();
		foreach ( (array) ( isset( $data['photos'] ) ? $data['photos'] : array() ) as $row ) {
			// Check: skip rows without image sources.
			if ( empty( $row['src'] ) || ! is_array( $row['src'] ) ) {
				continue;
			}
			$full = ! empty( $row['src']['large2x'] ) ? $row['src']['large2x'] : ( isset( $row['src']['original'] ) ? $row['src']['original'] : '' );
			$items[] = $this->item(
				isset( $row['id'] ) ? $row['id'] : '',
				isset( $row['src']['medium'] ) ? $row['src']['medium'] : '',
				$full,
				( isset( $row['photographer'] ) ? $row['photographer'] : '' ) . ' / Pexels'
			);
		}
		return $items;
	}
}
