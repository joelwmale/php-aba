<?php

namespace Joelwmale\PhpAba;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PhpAbaServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind('aba', function () {
            return new PhpAba;
        });
    }

    public function provides(): array
    {
        return ['aba'];
    }
}
