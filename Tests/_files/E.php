<?php
class E
{
    public $array = array('array' => array());

    public function __construct()
    {
        $this->array['array'][] = new A(1, 2, 3);
    }
}
