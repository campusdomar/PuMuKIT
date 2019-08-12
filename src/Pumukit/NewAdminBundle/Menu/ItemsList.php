<?php

namespace Pumukit\NewAdminBundle\Menu;

class ItemsList
{
    private $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function add(ItemInterface $item)
    {
        array_push($this->items, $item);
    }

    public function items()
    {
        return $this->items;
    }
}
