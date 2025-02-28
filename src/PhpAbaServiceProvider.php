<?php

namespace Joelwmale\PhpAba;

use Illuminate\Support\ServiceProvider;

class PhpAbaServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('aba', function () {
            return new PhpAba;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['aba'];
    }
}
