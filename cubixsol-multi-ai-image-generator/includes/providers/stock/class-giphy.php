<?php
/**
 * Giphy stock source (animated GIFs).
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the Giphy API.
 */
class Giphy extends Stock_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'giphy';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Giphy', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://developers.giphy.com/dashboard/';
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
					'api_key' => $this->get_api_key(),
					'q'       => $query,
					'limit'   => $per_page,
				),
				'https://api.giphy.com/v1/gifs/search'
			)
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: a real search result always carries this field; a
		// 200 without it means the key was not actually accepted.
		$shape = $this->require_field( $data, 'data' );
		if ( is_wp_error( $shape ) ) {
			return $shape;
		}

		$items = array();
		foreach ( (array) ( isset( $data['data'] ) ? $data['data'] : array() ) as $row ) {
			// Check: skip rows without image renditions.
			if ( empty( $row['images'] ) || ! is_array( $row['images'] ) ) {
				continue;
			}
			$items[] = $this->item(
				isset( $row['id'] ) ? $row['id'] : '',
				isset( $row['images']['fixed_width_small']['url'] ) ? $row['images']['fixed_width_small']['url'] : '',
				isset( $row['images']['original']['url'] ) ? $row['images']['original']['url'] : '',
				( isset( $row['username'] ) && '' !== $row['username'] ? $row['username'] : 'Giphy' ) . ' / Giphy'
			);
		}
		return $items;
	}
}
