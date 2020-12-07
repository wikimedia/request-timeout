<?php

namespace Wikimedia\RequestTimeout;

use Wikimedia\RequestTimeout\Detail\BasicRequestTimeout;
use Wikimedia\RequestTimeout\Detail\ExcimerRequestTimeout;

abstract class RequestTimeout {
	/** @var RequestTimeout|null The singleton instance */
	private static $instance;

	/**
	 * Get a singleton instance of RequestTimeout. If the Excimer extension is
	 * loaded, this will return a fully functional implementation. If it is
	 * not loaded, a fallback implementation will be returned.
	 *
	 * @return RequestTimeout
	 */
	public static function singleton() {
		if ( !self::$instance ) {
			self::$instance = self::factory();
		}
		return self::$instance;
	}

	/**
	 * Set the instance to be returned by singleton(), or null to clear the
	 * instance so that it will be recreated.
	 *
	 * @param RequestTimeout|null $instance
	 */
	public static function setInstance( ?RequestTimeout $instance ) {
		self::$instance = $instance;
	}

	/**
	 * Create a new instance of RequestTimeout. If the Excimer extension is
	 * loaded, this will return a fully functional implementation. If it is
	 * not loaded, a fallback implementation will be returned.
	 *
	 * @return RequestTimeout
	 */
	public static function factory() {
		if ( extension_loaded( 'excimer' ) ) {
			return new ExcimerRequestTimeout;
		} else {
			return new BasicRequestTimeout;
		}
	}

	/**
	 * Create a CriticalSectionProvider with the specified configuration.
	 *
	 * @param float $emergencyLimit The emergency timeout in seconds
	 * @param callable|null $emergencyCallback A callback to call when the
	 *   emergency timeout expires. If null, an exception will be thrown.
	 * @return CriticalSectionProvider
	 */
	public function createCriticalSectionProvider(
		$emergencyLimit, $emergencyCallback = null
	) {
		return new CriticalSectionProvider( $this, $emergencyLimit, $emergencyCallback );
	}

	/**
	 * @internal For use by CriticalSectionProvider
	 *
	 * @param string $name
	 * @param float $emergencyLimit
	 * @param callable|null $emergencyCallback
	 * @return int
	 */
	abstract public function enterCriticalSection( $name, $emergencyLimit, $emergencyCallback );

	/**
	 * @internal For use by CriticalSectionProvider
	 *
	 * @throws TimeoutException
	 * @param int $id
	 */
	abstract public function exitCriticalSection( $id );

	/**
	 * Set the wall time limit. If excimer is available, an exception will be
	 * thrown after the specified number of seconds.
	 *
	 * If excimer is not available, this falls back to set_time_limit(), which
	 * causes a fatal error after the CPU time (not wall clock time) exceeds
	 * the given number of seconds, which is rounded to an integer.
	 *
	 * @param float $limit The limit in seconds
	 */
	abstract public function setWallTimeLimit( $limit );

	/**
	 * Get the amount of time remaining of the limit.
	 *
	 * @return float
	 */
	abstract public function getWallTimeRemaining();
}
