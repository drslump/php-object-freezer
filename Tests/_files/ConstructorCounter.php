<?php
class ConstructorCounter
{
    public static $numTimesConstructorCalled = 0;

    public function __construct()
    {
        self::$numTimesConstructorCalled++;
    }
}
