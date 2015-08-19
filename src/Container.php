<?php

namespace Frozzare\Tank;

use ArrayAccess;
use Closure;
use Exception;
use ReflectionFunction;
use InvalidArgumentException;

/**
 * Container class.
 *
 * @package Tank
 */
class Container implements ArrayAccess {

	/**
	 * The container instance if any.
	 *
	 * @var \Frozzare\Tank\Container
	 */
	protected static $instance;

	/**
	 * The classes holder.
	 *
	 * @var array
	 */

	protected $classes = [];

	/**
	 * The keys holder.
	 *
	 * @var array
	 */
	protected $keys = [];

	/**
	 * The values holder.
	 *
	 * @var array
	 */
	protected $values = [];

	/**
	 * Set a parameter or an object.
	 *
	 * @param string $id
	 * @param mixed $value
	 * @param bool $singleton
	 *
	 * @return mixed
	 */
	public function bind( $id, $value = null, $singleton = false ) {
		if ( is_string( $id ) && $this->is_singleton( $id ) ) {
			throw new Exception( sprintf( 'Identifier `%s` is a singleton and cannot be rebind', $id ) );
		}

		if ( is_object( $id ) && get_class( $id ) !== false ) {
			$value = $id;
			$id    = $this->get_class_prefix( get_class( $id ), false );
			$this->classes[$id] = true;
		}

		if ( $value instanceof Closure ) {
			$closure = $value;
		} else {
			$closure = $this->get_closure( $value, $singleton );
		}

		$this->values[$id] = compact( 'closure', 'singleton' );
		$this->keys[$id] = true;

		return $value;
	}

	/**
	 * Call closure.
	 *
	 * @param mixed $closure
	 *
	 * @return mixed
	 */
	protected function call_closure( $closure, array $parameters = [] ) {
		if ( $closure instanceof Closure ) {
			$rc      = new ReflectionFunction( $closure );
			$args    = $rc->getParameters();
			$params  = $parameters;
			$classes = [
				$this->get_class_prefix( get_class( $this ) ),
				get_class( $this ),
				get_parent_class( $this )
			];

			foreach ( $args as $index => $arg ) {
				if ( $arg->getClass() === null ) {
					continue;
				}

				if ( in_array( $arg->getClass()->name, $classes ) ) {
					$parameters[$index] = $this;
				} else if ( $this->exists( $arg->getClass()->name ) ) {
					$parameters[$index] = $this->make( $arg->getClass()->name );
				}
			}

			if ( ! empty( $args ) && empty( $parameters ) ) {
				$parameters[0] = $this;
			}

			if ( count( $args ) > count( $parameters ) ) {
				$parameters = array_merge( $parameters, $params );
			}

			return $this->call_closure( call_user_func_array( $closure, $parameters ), $parameters );
		}

		return $closure;
	}

	/**
	 * Check if identifier is set or not.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function exists( $id ) {
		return isset( $this->keys[$this->get_class_prefix( $id )] );
	}

	/**
	 * Get closure function.
	 *
	 * @param mixed $value
	 * @param bool $singleton
	 *
	 * @return mixed
	 */
	protected function get_closure( $value, $singleton = false ) {
		return function() use( $value, $singleton ) {
			return $value;
		};
	}

	/**
	 * Get class prefix.
	 *
	 * @param string $class
	 * @param bool $check
	 *
	 * @return string
	 */
	protected function get_class_prefix( $id, $check = true ) {
		if ( strpos( $id, '\\' ) !== false && $id[0] !== '\\' ) {
			$class = '\\' . $id;

			if ( $check ) {
				return isset( $this->classes[$class] ) ? $class : $id;
			}

			return $class;
		}

		return $id;
	}

	/**
	 * Get the container instance if any.
	 *
	 * @return \Frozzare\Tank\Container
	 */
	public static function get_instance() {
		return static::$instance;
	}

	/**
	 * Determine if a given type is a singleton or not.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function is_singleton( $id ) {
		if ( ! is_string( $id ) ) {
			throw new InvalidArgumentException( 'Invalid argument. Must be string.' );
		}

		if ( ! $this->exists( $id ) ) {
			return false;
		}

		$id = $this->get_class_prefix( $id );

		return $this->values[$id]['singleton'] === true;
	}

	/**
	 * Resolve the given type from the container.
	 *
	 * @param string $id
	 * @param array $parameters
	 *
	 * @return mixed
	 */
	public function make( $id, array $parameters = [] ) {
		if ( ! $this->exists( $id ) ) {
			throw new InvalidArgumentException( sprintf( 'Identifier `%s` is not defined', $id ) );
		}

		$id      = $this->get_class_prefix( $id );
		$value   = $this->values[$id];
		$closure = $value['closure'];

		return $this->call_closure( $closure, $parameters );
	}

	/**
	 * Unset value by identifier.
	 *
	 * @param string $id
	 */
	public function remove( $id ) {
		$id = $this->get_class_prefix( $id );
		unset( $this->keys[$id], $this->values[$id] );
	}

	/**
	 * Get the container instance if any.
	 *
	 * @return \Frozzare\Tank\Container
	 */
	public static function set_instance( Container $instance ) {
		static::$instance = $instance;
	}

	/**
	 * Set a parameter or an object.
	 *
	 * @param string $id
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function singleton( $id, $value = null ) {
		return $this->bind( $id, $value, true );
	}

	/**
	 * Check if identifier is set or not.
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart
	public function offsetExists( $id ) {
	// @codingStandardsIgnoreEnd
		return $this->exists( $id );
	}

	/**
	 * Get value by identifier.
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	// @codingStandardsIgnoreStart
	public function offsetGet( $id ) {
	// @codingStandardsIgnoreEnd
		return $this->make( $id );
	}

	/**
	 * Set a parameter or an object.
	 *
	 * @param string $id
	 * @param mixed $value
	 */
	// @codingStandardsIgnoreStart
	public function offsetSet( $id, $value ) {
	// @codingStandardsIgnoreEnd
		$this->bind( $id, $value );
	}

	/**
	 * Unset value by identifier.
	 *
	 * @param string $id
	 */
	// @codingStandardsIgnoreStart
	public function offsetUnset( $id ) {
	// @codingStandardsIgnoreEnd
		$this->remove( $id );
	}
}
