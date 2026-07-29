<?php
/**
 * Daily usage counter (site-wide), stored in a transient that
 * expires at local midnight.
 *
 * @package AIISP
 */

namespace AIISP\Core;

// Security check: block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks generations-per-day against the configured limit.
 */
class Usage {

	/** Transient key. */
	const KEY = 'aiisp_daily_usage';

	/**
	 * Images generated so far today.
	 *
	 * @return int
	 */
	public function today() {
		$value = get_transient( self::KEY );

		// Check: a transient can hold anything — coerce defensively.
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Increment today's counter.
	 *
	 * @return void
	 */
	public function increment() {
		$expires = strtotime( 'tomorrow midnight' ) - time();

		// Check: guarantee a positive TTL even around midnight edges.
		set_transient( self::KEY, $this->today() + 1, max( $expires, HOUR_IN_SECONDS ) );
	}

	/**
	 * Whether the daily limit has been reached. A limit of 0 means
	 * unlimited.
	 *
	 * @return bool
	 */
	public function limit_reached() {
		$limit = (int) aiisp()->options()->get( 'daily_limit', 100 );

		return $limit > 0 && $this->today() >= $limit;
	}
}
