<?php
/**
 * Pixabay stock source.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the Pixabay API.
 */
class Pixabay extends Stock_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'pixabay';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Pixabay', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://pixabay.com/api/docs/';
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
					'key'      => $this->get_api_key(),
					'q'        => $query,
					'per_page' => $per_page,
				),
				'https://pixabay.com/api/'
			)
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: a real search result always carries this field; a
		// 200 without it means the key was not actually accepted.
		$shape = $this->require_field( $data, 'hits' );
		if ( is_wp_error( $shape ) ) {
			return $shape;
		}

		$items = array();
		foreach ( (array) ( isset( $data['hits'] ) ? $data['hits'] : array() ) as $row ) {
			// Check: skip rows without a large image URL.
			if ( empty( $row['largeImageURL'] ) ) {
				continue;
			}
			$items[] = $this->item(
				isset( $row['id'] ) ? $row['id'] : '',
				isset( $row['previewURL'] ) ? $row['previewURL'] : '',
				$row['largeImageURL'],
				( isset( $row['user'] ) ? $row['user'] : '' ) . ' / Pixabay'
			);
		}
		return $items;
	}
}
