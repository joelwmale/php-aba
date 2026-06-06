<?php

namespace Joelwmale\PhpAba\Validators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

abstract class Validator
{
    protected static ?Factory $factory = null;

    public static function instance(): Factory
    {
        if (! static::$factory) {
            $loader = new FileLoader(new Filesystem, '/Translations');
            $translator = new Translator($loader, 'en');
            static::$factory = new Factory($translator);
        }

        return static::$factory;
    }

    public static function make(array $data, array $rules, array $messages = [], array $customAttributes = []): \Illuminate\Validation\Validator
    {
        return static::instance()->make($data, $rules, $messages, $customAttributes);
    }
}
