<?php

namespace JackDTaylor\Dpr;

class Measure {
    protected static $storage = [];
    protected static $locks = [];

    public static function from($key) {
        if(isset(static::$locks[$key])) {
            return;
        }

        static::$locks[$key] = microtime(true);
    }

    public static function to($key) {
        if(!isset(static::$locks[$key])) {
            return;
        }

        $time = (microtime(true) - static::$locks[$key]) * 1000;

        static::$storage[$key] = number_format($time, 2, '.', '');

        unset(static::$locks[$key]);
    }

    public static function dump(...$args) {
        $data = static::$storage;
        ksort($data);

        Dpr::getInstance()->dump(array_merge([ $data ], $args));
    }
}