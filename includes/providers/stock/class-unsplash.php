<?php
/**
 * Unsplash stock source.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the Unsplash photos API.
 */
class Unsplash extends Stock_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'unsplash';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Unsplash', 'cubixsol-multi-ai-image-generator' );
	}

	/** {@inheritDoc} */
	public function get_key_url() {
		return 'https://unsplash.com/developers';
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
				'https://api.unsplash.com/search/photos'
			),
			array(
				'Authorization'  => 'Client-ID ' . $this->get_api_key(),
				'Accept-Version' => 'v1',
			)
		);

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Check: a real search result always carries this field; a
		// 200 without it means the key was not actually accepted.
		$shape = $this->require_field( $data, 'results' );
		if ( is_wp_error( $shape ) ) {
			return $shape;
		}

		$items = array();
		foreach ( (array) ( isset( $data['results'] ) ? $data['results'] : array() ) as $row ) {
			// Check: skip rows without URL data.
			if ( empty( $row['urls'] ) || ! is_array( $row['urls'] ) ) {
				continue;
			}
			$items[] = $this->item(
				isset( $row['id'] ) ? $row['id'] : '',
				isset( $row['urls']['small'] ) ? $row['urls']['small'] : '',
				isset( $row['urls']['full'] ) ? $row['urls']['full'] : '',
				( isset( $row['user']['name'] ) ? $row['user']['name'] : '' ) . ' / Unsplash'
			);
		}
		return $items;
	}
}
