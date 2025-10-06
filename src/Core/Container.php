<?php
/**
 * Simple dependency injection container.
 *
 * @package FluxMedia
 * @since 1.0.0
 */

namespace FluxMedia\Core;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * Simple dependency injection container implementation.
 *
 * @since 1.0.0
 */
class Container implements ContainerInterface {

	/**
	 * Services registry.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $services = [];

	/**
	 * Set a service in the container.
	 *
	 * @since 1.0.0
	 * @param string $id The service identifier.
	 * @param mixed  $value The service instance.
	 */
	public function set( $id, $value ) {
		$this->services[ $id ] = $value;
	}

	/**
	 * Get a service from the container.
	 *
	 * @since 1.0.0
	 * @param string $id The service identifier.
	 * @return mixed The service instance.
	 * @throws NotFoundExceptionInterface If the service is not found.
	 * @throws ContainerExceptionInterface If there's an error retrieving the service.
	 */
	public function get( string $id ) {
		if ( ! $this->has( $id ) ) {
			throw new class( "Service '{$id}' not found" ) extends \Exception implements NotFoundExceptionInterface {};
		}

		return $this->services[ $id ];
	}

	/**
	 * Check if a service exists in the container.
	 *
	 * @since 1.0.0
	 * @param string $id The service identifier.
	 * @return bool True if the service exists, false otherwise.
	 */
	public function has( string $id ): bool {
		return isset( $this->services[ $id ] );
	}
}
