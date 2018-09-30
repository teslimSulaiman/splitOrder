<?php
/**
 * Created by PhpStorm.
 * User: Owner
 * Date: 9/30/2018
 * Time: 12:32 PM
 */

class Teslim_SplitOrder_Model_Split
{


    public function splitOrderByAttribute($order, $attribute)
    {

        $allItems = $order->getAllVisibleItems();
        $unSplittedProductArray = array();
        foreach ($allItems as $item) {
            $product_id = $item->product_id;
            $_product = Mage::getModel('catalog/product')->load($product_id);
            $attributeValue = $_product->getData($attribute);
            //$brand = 'nike';
            $qty = $item->getData('qty_ordered');
            if ($attributeValue != '') {
                $unSplittedProductArray[$product_id]["id"] = $product_id;
                $unSplittedProductArray[$product_id][$attribute] = $attributeValue;
                $unSplittedProductArray[$product_id]["qty"] = $qty;
            }

        }
        $resultArray = $this->splitProductArrayByBrand($unSplittedProductArray, $attribute);

        if (count($resultArray) > 1) {
            $this->placeOrder($order, $resultArray);
        }
    }

    private function setCustomerToQuote($order, $quote)
    {
        $customer = $order->getCustomer();
        $quote->assignCustomer($customer);
        return $quote;
    }

    private function setQuoteParameters($order)
    {

        $store = $order->getStore();

        // Start New  Order Quote
        $quote = Mage::getModel('sales/quote')->setStoreId($store->getId());

        // Set Sales Order Quote Currency
        $quote->setCurrency($order->AdjustmentAmount->currencyID);
        $quote = $this->setCustomerToQuote($order, $quote);
        return $quote;
    }

    private function placeOrder($order, $array)
    {

        foreach ($array as $productsPerBrand) {

            $quote = $this->setQuoteParameters($order);
            $quote = $this->addProductsToQuote($quote, $productsPerBrand);
            $quote = $this->setBillingAndShippingAddress($quote, $order);

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();
            $this->createOrderFromQuote($quote, $order);
        }

    }

    private function createOrderFromQuote($quote, $order)
    {
        try {
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $newOrder = $service->getOrder();
            $newOrder->setData('split_order_parent_order_id', $order->getIncrementId());
            $newOrder->save();
            $quote->setIsActive(0)->save();
            Mage::getSingleton('checkout/cart')->truncate()->save();

        } catch (Exception $ex) {
            Mage::log("$ex->getMessage()", null, 'Teslim_SplitOrder.log');
        }
    }

    private function setBillingAndShippingAddress($quote, $order)
    {
        $billingAddress = $order->getBillingAddress()->getData();
        $quote->getBillingAddress()->addData($billingAddress);
        $shippingAddress = $order->getShippingAddress()->getData();
        $shippingAddress = $quote->getShippingAddress()->addData($shippingAddress);
        $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod($paymentMethodCode);

        // Set Sales Order Payment
        $quote->getPayment()->importData(array('method' => $paymentMethodCode));
        return $quote;
    }

    private function addProductsToQuote($quote, $productArray)
    {
        foreach ($productArray as $productData) {
            $id = $productData['id'];
            $quantity = $productData['qty'];
            $product = Mage::getModel('catalog/product')->load($id);
            $quote->addProduct($product, new Varien_Object(array('qty' => $quantity)));
        }
        return $quote;
    }

    private function splitProductArrayByBrand($array, $attribute)
    {
        $splittedArray = array();
        foreach ($array as $key => $item) {
            $splittedArray[$item[$attribute]][$key] = $item;
        }
        return $splittedArray;
    }
}