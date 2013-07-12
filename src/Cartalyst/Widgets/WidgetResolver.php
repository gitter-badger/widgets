<?php namespace Cartalyst\Widgets;
/**
 * Part of the Platform application.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Platform
 * @version    2.0.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2013, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Cartalyst\Extensions\Extension;
use Cartalyst\Extensions\ExtensionBag;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Support\NamespacedItemResolver;

class WidgetResolver {

	/**
	 * The inversion of control container instance.
	 *
	 * @var Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * The Extension Bag used by the addon.
	 *
	 * @var Cartalyst\Extensions\ExtensionBag
	 */
	protected $extensionBag;

	/**
	 * Default namespace prefix for parsing
	 * keys
	 *
	 * @var string
	 */
	protected $namespacePrefix = 'Widgets';

	/**
	 * List of registered items.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Array of parsed keys.
	 *
	 * @var array
	 */
	protected $parsed = array();

	/**
	 * Create a new extension resolver.
	 *
	 * @param  Illuminate\Container\Container  $container
	 * @param  Cartalyst\Extensions\ExtensionBag  $extensionBag
	 * @return void
	 */
	public function __construct(Container $container, ExtensionBag $extensionBag)
	{
		$this->container    = $container;
		$this->extensionBag = $extensionBag;
	}

	/**
	 * Makes the resolvable instance for the given key.
	 *
	 * @param  string  $key
	 * @param  array   $parameters
	 */
	public function make($key, array $parameters = array())
	{
		// If we haven't actually got the item, we'll attempt
		// to auto-detect it based on our Extension Bag and
		// the key given.
		if ( ! isset($this->items[$key]))
		{
			$this->autoDetect($key);
		}

		$item = $this->items[$key];

		if ($item instanceof Closure)
		{
			return call_user_func_array($item, $parameters);
		}

		if (strpos($item, '@') === false)
		{
			throw new \InvalidArgumentException("No @ character was found to separate the class and method to be loaded in [$class].");
		}

		list($class, $method) = explode('@', $item);

		$instance = $this->container->make($class);
		return call_user_func_array(array($instance, $method), $parameters);
	}

	/**
	 * Manually maps an item into the registered array.
	 *
	 * @param  string  $key
	 * @param  mixed   $item
	 * @return void
	 */
	public function map($key, $item)
	{
		$this->items[$key] = $item;
	}

	/**
	 * If an item has not been registered with
	 * the resolver, we'll attempt to detect it
	 * based on the key given and register the
	 * item with this object.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function autoDetect($key)
	{
		list($class, $method) = $this->parseKey($key);
		$this->items[$key]    = "{$class}@{$method}";
	}

	/**
	 * Parses an extension resolvable key and
	 * returns the corresponding class and
	 * method in an array. A second parameter
	 * can be provided to denote a namespace prefix
	 * (within the extension's namespace) for the
	 * class name
	 *
	 * @param  string  $key
	 * @param  string  $namespacePrefix
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function parseKey($key, $namespacePrefix = null)
	{
		// Default the namespace prefix
		if ( ! isset($namespacePrefix))
		{
			$namespacePrefix = $this->namespacePrefix;
		}

		// Generate a cache key based on the key and prefix
		$cacheKey = $key;
		if (isset($namespacePrefix))
		{
			$cacheKey .= $namespacePrefix;
		}

		// If we have already parsed this key let's
		// Just return the class and save on the
		// overhead
		if (isset($this->parsed[$cacheKey]))
		{
			return $this->parsed[$cacheKey];
		}

		if ( ! str_contains($key, '::'))
		{
			throw new \InvalidArgumentException("An extension must be provided in the format vendor/extension::class.method for key [$key].");
		}

		list($extensionSlug, $classKey) = explode('::', $key);

		// We need at least a class and method,
		// so a minimum of 1 dot to separate the
		// class from method
		if (substr_count($classKey, '.') < 1)
		{
			throw new \InvalidArgumentException("At least two segments are required in [$classKey] for key [$key], one for class name and one for method name.");
		}

		if ( ! isset($this->extensionBag[$extensionSlug]))
		{
			throw new \InvalidArgumentException("Extension [$extensionSlug] is not existent on Extension Bag, cannot resolve.");
		}

		$extension = $this->extensionBag[$extensionSlug];

		if ( ! $extension->isEnabled())
		{
			throw new \RuntimeException("Extension [{$extension->getSlug()}] is not enabled therefore cannot resolve.");
		}

		// Calculate the class and method
		$lastDot = strrpos($classKey, '.');

		// The class is the namespace of the extension plus
		// the dot-notation converted to a namespace structure.
		// foo.bar.baz will become Foo\Bar\Baz
		$class = $extension->getNamespace().'\\';

		// Add in the namespace prefix if one is specified
		if (isset($namespacePrefix))
		{
			$class .= $namespacePrefix.'\\';
		}
		$class .= str_replace(' ', '\\', ucwords(str_replace('.', ' ', substr($classKey, 0, $lastDot))));

		// And the method is just everything after the dot
		$method = substr($classKey, $lastDot + 1);

		// Cache our parsed key and return it
		return $this->parsed[$cacheKey] = array($class, $method);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->make($key);
	}

}
