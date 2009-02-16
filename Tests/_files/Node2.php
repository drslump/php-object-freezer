<?php
class Node2
{
    protected $parent;
    protected $children = array();

    public function __construct(Node2 $parent = NULL)
    {
        $this->parent = $parent;

        if ($this->parent !== NULL) {
            $this->parent->addChild($this);
        }
    }

    public function addChild(Node2 $child)
    {
        $this->children[] = $child;
    }
}
