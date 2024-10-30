<?php

class MJFreeway_CartItem{
    public $name;
    public $quantity;
    public $price;
 

    public function __construct($productName, $quantity, $price){
        $this->name = $productName;
        $this->quantity = $quantity;
        $this->price = $price;
    }

    public function get_subtotal() { 
        return $this->quantity * $this->price;
    }

}    