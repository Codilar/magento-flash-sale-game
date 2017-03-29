<?php
class Codilar_Flashsale_IndexController extends Mage_Core_Controller_Front_Action{

    /*load Layout of the flashsale */
    public function IndexAction() {

        /*Check if flashsale is enabled or not*/
        $status  =  Mage::getStoreConfig('flashsale/general/status');
        if(!$status || !file_exists(Mage::getBaseDir().DS.'.flashsale'))
        {
            /*If flashsale not enabled, redirect to 404 pagenot found */
            $this->noRouteAction();
            return;
        }

        $this->loadLayout();
        $this->getLayout()->getBlock("head")->setTitle($this->__("Flash Sale"));
        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
        $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("flash sale", array(
                "label" => $this->__("Flash Sale"),
                "title" => $this->__("Flash Sale")
		   ));

      $this->renderLayout();
	  
    }

    /*Add Flash sale products to cart after sale is ended.*/
    public function EndsaleAction()
    {

        $status  =  Mage::getStoreConfig('flashsale/general/status')==1;
        $response =  array();
        $response['url'] = Mage::getUrl('checkout/cart');
        $response['status'] = '200';
        /*Check if flashsale is enabled */
        if($status && !file_exists(Mage::getBaseDir().'.flashsale'))
        {
            $cart = Mage::getSingleton('checkout/cart');
            $cart->truncate();
            $session = Mage::getSingleton('customer/session');
            $quote = Mage::getSingleton('checkout/session')->getQuote();
             $cart->init();
                
                $product_ids = $this->getRequest()->getParam('cart');

                /* Add Each Added FlashSale Products to cart */
                foreach(json_decode($product_ids,true) as $product_id)
                {

                    try{

                        $productInstance = Mage::getModel('catalog/product')->load($product_id);
                        $finalPrice = ($productInstance->getFlashsalePrice()? $productInstance->getFlashsalePrice():$productInstance->getFinalPrice() );
                        $item = $quote->addProduct($productInstance,1);
                        $item->setCustomPrice($finalPrice);
                        $item->setOriginalCustomPrice($finalPrice);
                        $item->getProduct()->setIsSuperMode(true);
                    }
                    catch(Exception $exception)
                    {
                        Mage::getSingleton('customer/session')->addError($exception->getMessage());
                        $response['status']= '500';
                    }
                }   
                $quote->collectTotals()->save();
                $cart->save();
            }
            else
            {
                $response['status'] = '503';
            }
        echo json_encode($response);
    }  


    
}