<?php
class Codilar_Flashsale_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * JSON of flashsale products 
	 *
     * @var string
     */
	private $_json;


	/**
     * Get name of the Json file
     *
     * @return filename from base directory
     */
	private function getJsonFile(){
		$filename = Mage::getBaseDir('var').DS.'flashproducts.json';
		return $filename;
	}

	/**
    * Build a json and write it into file.
    * 
    * @return Json string
    */

	public function generateJson(){
		$productids = array();
		$json = array();

		/* Get Flashsale Products Collection */
		$flashsaleProductCollection = $this->getFlashsaleProductCollection();

		foreach( $flashsaleProductCollection as $single_product )
        {
        	$productid = $single_product->getId();
			$product = $this->loadProduct($productid);
			$price = $this->getPrice($product);
			
			/*Get product image path*/
			try{
				  $imgSrc = Mage::helper('catalog/image')->init($product, 'image')->__toString();
				}
				catch(Exception $e) {
				  $imgSrc = Mage::getDesign()->getSkinUrl('images/catalog/product/placeholder/image.jpg',array('_area'=>'frontend'));  
			}
			$info = array(
    					"id" 		=> $product->getId(),
    					"title"     => $product->getName()."(Price :  Rs. ".$price." )",
    					"left" 		=> rand(400,800)."px",
    					"top"		=> rand(200,400)."px",
						"image_url" => $imgSrc,
						"width"		=>	rand(80,90)."px",
						"height"	=>	rand(80,90)."px",			
					);
			array_push($json, $info);			
		}
		
		$json=json_encode($json);
		$json = addslashes($json);
		
		file_put_contents($this->getJsonFile(), $json);
		chmod($this->getJsonFile(), "0700");
		
		return $json;
	}
	
	/**
    * Return Json of flashsale products if exists else generate new Json.
    * @param returnArray
    * @return Json string
    */

	public function getJson($returnArray = false){
		if(Mage::getStoreConfig('flashsale/general/status')!=1 && !file_exists(Mage::getBaseDir().'.flashsale'))
        {
        	return "{}";
        }
		$jsonfile = file_get_contents($this->getJsonFile());
		$response = null;
		if(strlen($jsonfile)>1){
			$response = $jsonfile;	
		}
		else{
			$response = $this->generateJson();

		}
		if($returnArray){
			$response = json_decode(stripcslashes($response),true);
		}
		$this->_json = $response;
		return $response;
	}

	/**
    * Get Flashsale Product Collection
    * 
    * @return Flashsale Product Collection
    */

	public function getFlashsaleProductCollection(){

		/*Filter product collection with in_flash_sale Attribute.*/
        $products_collection=Mage::getModel('catalog/product')->getCollection()
          ->addAttributeToFilter('in_flash_sale',true);

         
        return $products_collection;
    }

	/**
    * Load Product with product id
    * @param Product Id 
    * @return Product 
    */

	public function loadProduct($productid){
		return Mage::getModel('catalog/product')->load($productid);
	}

	/**
    * Get Flash Sale Price
    * @param Product
    * @return flashsale price of the product
    */

	public function getPrice($product){
		if($product->getFlashsalePrice())
			$price = $product->getFlashsalePrice();
		elseif($product->getSpecialPrice())
			$price = $product->getSpecialPrice();
		else
			$price = $product->getPrice();
		return $price;
	}

	/**
    * Check If Product Is in Sale
    * @param products from cart 
    * @return boolean
    */

	public function isProductInSale($product = null){
		$id = $product->getId();
		$sale = $this->_json;
		foreach ($sale as $item) {
			if($item['id']==$id){
				return true;
			}
		}
		return false;
	}
}
	 