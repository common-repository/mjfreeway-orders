<?php 
class MJFreeway_Product{
    public $description;
    public $pricing;
    public $name;
    public $imageUrl;
    public $id;
    public $category;
    
    public function __construct($id, $description, $pricing, $name, $imageUrl, MJFreeway_ProductCategory $category){
        $this->description = $description;
        $this->pricing = $pricing;
        $this->name = $name;
        $this->imageUrl = $imageUrl;
        $this->id = $id;
        $this->category = $category;
    }
    
}
