<?php
/**
 * Created by PhpStorm.
 * User: Owner
 * Date: 9/28/2018
 * Time: 3:28 PM
 */

class Teslim_SplitOrder_Model_Observer
{

    public function SplitOrder(Varien_Event_Observer $observer)
    {

        if (!Mage::registry('prevent_observer')) {
            $splitOrder = Mage::getModel('splitorder/split');
            $order = $observer->getEvent()->getOrder();
            $splitOrder->splitOrderByAttribute($order, "brand");

            Mage::register('prevent_observer', true);
        }
    }
}