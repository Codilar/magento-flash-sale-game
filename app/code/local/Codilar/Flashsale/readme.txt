FlashSale 


This Product Is Used To Create A Realtime Sale.



Install the module by copying all the contents of the module in your Magento root directory.



Product Attributes

path: Catalog=>Attributes=>Manage Attributes;

The module itself create two new product attributes named
1.in_flash_sale =>  Include In Flash Sale
2.flashsale_price => Price For Sale



flashsale_price = is a price to be shown during the sale of the product if its in sale. This is not a required attribute ,  If No price is available then product price will be taken from special price (if availabale)  or the price.

in_flash_sale =  is used to set the product as a protuct to be sold only through flashsale.


System Configuration
Dashboard=>Configuration=>System=>Codilar=>Flashsale=>General.

Status : Set-to Yes when you need to start the sale. After all products are  ready to publish.
Sale - Duration : Set the Sale Duration.(in seconds).
Socket Url : Set path of file "server.php" currently paced under your magento root directory.

Presmissions
Server.php. 
File Owner  = www.data/
File Permissions 777.


