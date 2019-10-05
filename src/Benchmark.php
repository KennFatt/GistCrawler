<?php

declare(strict_types=1);

class Benchmark {

    private static $data = NULL;

    public static function initialize(array $initData) : bool {
        if ((self::$data === NULL))
            return false;

        self::$data = $initData;
        return true;
    }

    public static function update(array $newData) : bool {
        if ((self::$data === NULL))
            return false;

        self::$data = array_merge(self::$data, $newData);
        return true;
    }

    public static function finalize() : array {
        if ((self::$data === NULL))
            return [];
        
        $retVal = self::$data;
        self::$data = NULL;

        return $retVal;
    }
}