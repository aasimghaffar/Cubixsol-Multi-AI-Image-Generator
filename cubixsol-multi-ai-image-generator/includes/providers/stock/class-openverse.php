<?php
/**
 * Openverse stock source — openly licensed media, no API key.
 *
 * @package AIISP
 */

namespace AIISP\Providers\Stock;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Searches the Openverse images API.
 */
class Openverse extends Stock_Base {

	/** {@inheritDoc} */
	public function get_slug() {
		return 'openverse';
	}

	/** {@inheritDoc} */
	public function get_label() {
		return __( 'Openverse', 'cubixsol-multi-ai-image-generator' );
	}

	/** Openverse never needs a key. {@inheritDoc} */
	public function requires_api_key() {
		return false;
	}

	/** {@inheritDoc} */
	public function search( $query, $per_page ) {
		$data = $this->get_json(
			add_query_arg(
				array(
					'q'         => $query,
					'page_size' => $per_page,
				),
				'https://api.openverse.org/v1/images/'
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
			// Check: skip rows missing a usable full-size URL.
			if ( empty( $row['url'] ) ) {
				continue;
			}
			$items[] = $this->item(
				isset( $row['id'] ) ? $row['id'] : '',
				isset( $row['thumbnail'] ) ? $row['thumbnail'] : '',
				$row['url'],
				( isset( $row['creator'] ) ? $row['creator'] : '' ) . ' / Openverse (' . ( isset( $row['license'] ) ? strtoupper( $row['license'] ) : 'CC' ) . ')'
			);
		}
		return $items;
	}
}
