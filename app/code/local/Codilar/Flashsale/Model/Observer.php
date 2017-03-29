<?php
class Codilar_Flashsale_Model_Observer
{
    /**
     * FlashSale Helper
     *
     * @var helper
     */
    private $_helper;

    /**
    * On any changes in flashsale section in admin pannel.
    * @param observer 
    * 
    */

    public function onStatusChange($observer)
    {
        /*check if .flashsale file exists in base directory */
        $status  = Mage::getStoreConfig('flashsale/general/status');
        if($status && !file_exists(Mage::getBaseDir().DS.'.flashsale'))
        {
            Mage::helper('flashsale')->generateJson();
            $timer = Mage::getStoreConfig('flashsale/general/duration');
            $script = "php -q ".Mage::getBaseDir().DS.Mage::getStoreConfig('flashsale/general/socket_url')." ".$timer;
            $command = 'bash -c "exec nohup setsid '.$script.' > /dev/null 2>&1 &"';
            shell_exec($command);
        }
    }


    /**
    * If the cart product updated is in flashsale, abort update cart.
    * @param observer
    * 
    */

    public function onAddToCart($observer){

        /*check if flashsale is enabled */
        $status  =  Mage::getStoreConfig('flashsale/general/status');
        if(!$status || !file_exists(Mage::getBaseDir().DS.'.flashsale'))
        {
            return;
        }
        $items = $observer->getEvent()->getCart()->getQuote()->getAllItems();
        $cartHelper = Mage::helper('checkout/cart');
        $this->initHelper();
        foreach ($items as $key => $item) {
            $product = $item->getProduct();

            /* Check if product is in sale */
            if($this->_helper->isProductInSale($product)){
                /* Abort add to cart */
                $cartHelper->getCart()->removeItem($item->getItemId());
                Mage::getSingleton('core/session')->addError("Product \"".$product->getName()."\" is in sale. Please go <a href='".Mage::getUrl('flashsale/index')."'>here</a> to buy it.");
             }
        }
    
    }


    /**
    * If the product added to cart is in flashsale, abort add to cart.
    * @param observer
    * 
    */

    public function onAddToCartComplete($observer){
        /*check if flashsale is enabled */
        $status  =  Mage::getStoreConfig('flashsale/general/status');
        if(!$status || !file_exists(Mage::getBaseDir().DS.'.flashsale'))
        {
            return;
        }
        $product = $observer->getEvent()->getProduct();
        $cartHelper = Mage::helper('checkout/cart');
        $this->initHelper();
        /* Check if product is in sale */
        if($this->_helper->isProductInSale($product)){

            $quote =  Mage::getSingleton('checkout/session')->getQuote();
            $item = $quote->getItemByProduct($product);
            /* Abort update cart */
            $quote->removeItem($item->getItemId())->save();
            Mage::getSingleton('core/session')->addError("Product \"".$product->getName()."\" is in sale. Please go <a href='".Mage::getUrl('flashsale/index')."'>here</a> to buy it.");
        }
    
    }

    /**
    * Initializing the Helper
    * 
    */

    private function initHelper(){
        $helper = $this->getHelper();
        $helper->getJson(true);
        $this->_helper = $helper;
    }

    /**
    * Get Helper
    * @return Flashsale helper
    */

    private function getHelper(){
        return Mage::helper('flashsale');
    }
}