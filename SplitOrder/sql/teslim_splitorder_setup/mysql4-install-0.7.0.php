<?php
/**
 * Created by PhpStorm.
 * User: Owner
 * Date: 9/27/2018
 * Time: 3:20 PM
 */



$installer = $this;
$installer->startSetup();

$installer->addAttribute("order", "split_order_parent_order_id", array("type"=>"varchar"));
$installer->addAttribute("quote", "split_order_parent_order_id", array("type"=>"varchar"));
$installer->endSetup();