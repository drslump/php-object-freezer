<?php
class F
{
    public $file;

    public function __construct()
    {
        $this->file = fopen(__FILE__, 'r');
    }
}
