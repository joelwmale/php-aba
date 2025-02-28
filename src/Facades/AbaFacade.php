<?php

namespace Joelwmale\PhpAba\Facades;

use Illuminate\Support\Facades\Facade;

class AbaFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'aba';
    }
}
