<?php   
class Codilar_Flashsale_Block_Index extends Mage_Core_Block_Template
{

    public function _prepareLayout()
    {
        $this->setTemplate('Flashsale/playground.phtml');
        parent::_prepareLayout();
    }

    /**
    * Return Json of flashsale products if exists else generate new Json.
    *
    * @return Json string
    */

    public function getProductJson()
    {
        return Mage::helper('flashsale')->getJson();
    }

    /**
    * Returns Url where the data is to be sent after End of sale
    * 
    * @return string
    */


    public function getSaleUrl()
    {
        return $this->getUrl('flashsale/index/endsale');
    }

    /**
    * Returns the client IP
    * 
    * @return string
    */

    public function getIP()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
    * Returns the URL where server.php resides
    * 
    * @return string
    */

    public function getSocketUrl()
    {
        return 'ws://'.$_SERVER['HTTP_HOST'].':9000'.DS.Mage::getStoreConfig('flashsale/general/socket_url');
    }

    /**
    * Returns the name of the player or customer 
    * 
    * @return string
    */

    public function getPlayerName(){
        return Mage::getSingleton('customer/session')->isLoggedIn()?Mage::getSingleton('customer/session')->getCustomer()->getName():null;
    }

    /**
    * Returns the base url 
    * 
    * @return string
    */

    public function getHomePage(){
        return Mage::getBaseUrl();
    }


}