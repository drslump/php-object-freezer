<?php
class B
{
    public $a;

    public function __construct()
    {
        $this->a = new A(1, 2, 3);
    }

    public function getValuesOfA()
    {
        return $this->a->getValues();
    }
}
