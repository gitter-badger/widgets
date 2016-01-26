<?php

/**
 * Part of the Rinvex Widgets Package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under The MIT License (MIT).
 *
 * This source file is subject to The MIT License (MIT) that is
 * bundled with this package in the LICENSE file.
 *
 * @package        Rinvex Widgets Package
 * @license        The MIT License (MIT)
 * @link           http://rinvex.com
 */

namespace Rinvex\Widgets\Facades;

use Illuminate\Support\Facades\Facade;

class Widget extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'widgets'; }

}
