<?php
class B
{
    private $a;

    public function __construct()
    {
        $this->a = new A(1, 2, 3);
    }
}
