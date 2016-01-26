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

namespace Rinvex\Widgets\Providers;

use Rinvex\Widgets\WidgetResolver;
use Illuminate\Support\ServiceProvider;

class WidgetServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->extend(function ($value) use ($blade) {
            $matcher = $blade->createMatcher('widget');

            return preg_replace($matcher, '<?php try { echo app(\'widgets\')->make$2; } catch (\Exception $e) { } ?>', $value);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->app['widgets'] = $this->app->share(function ($app) {
            return new WidgetResolver($app, $app['extensions']);
        });
    }
}
