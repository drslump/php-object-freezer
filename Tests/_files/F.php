<?php
class F
{
    private $file;

    public function __construct()
    {
        $this->file = fopen(__FILE__, 'r');
    }
}
