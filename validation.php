<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @deprecated 1.5.0 This file is deprecated, use moduleFrontController instead
 */

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/../../init.php');

$context = Context::getContext();

if(!$_GET || !$_GET['id_cart'] || !$_GET['amount'] || (string)$_GET['status']=='failed'){
	Tools::redirect('index.php?controller=order&step=1');
}else{
	$id_cart = (int)$_GET['id_cart'];
	$cart = $context->cart;

	$total = $_GET['amount'];

	//Double checking the cart data with payment data
	if($cart->id != $id_cart || (float)$cart->getOrderTotal(true, Cart::BOTH) != $_GET['amount']){
		Tools::redirect('index.php?controller=order&step=1');
	}
}

$customer = new Customer((int)$context->customer->id);
$currency = $context->currency;

$payhalal = Module::getInstanceByName('PayHalal');


if (!$payhalal->active)
	Tools::redirect('index.php?controller=order&step=1');

// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
$authorized = false;
foreach (Module::getPaymentModules() as $module)
	if ($module['name'] == 'PayHalal')
	{
		$authorized = true;
		break;
	}
if (!$authorized)
	die($payhalal->getTranslator()->trans('This payment method is not available.', array(), 'Modules.Wirepayment.Shop'));



if (!Validate::isLoadedObject($customer))
	Tools::redirect('index.php?controller=order&step=1');

$mailVars = array(
    '{PAYHALAL_MERCHANT_PASSWORD}' => Configuration::get('PAYHALAL_MERCHANT_PASSWORD'),
    'amount' => $total,
);

//Validate cart
$payhalal->validateOrder($cart->id, 2, $total, $payhalal->displayName, NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);

//Redirect to success page
Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$payhalal->id.'&id_order='.$payhalal->currentOrder.'&key='.$customer->secure_key);
