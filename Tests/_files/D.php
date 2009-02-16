<?php
class D
{
    public $array;

    public function __construct()
    {
        $this->array[] = new A(1, 2, 3);
    }
}
