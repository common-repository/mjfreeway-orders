<?php

class MJFreeway_ProductCategory{
    public $name;
    public $id;

    public function __construct($categoryName, $categoryId){
        $this->name = $categoryName;
        $this->id = $categoryId;
    }
}
