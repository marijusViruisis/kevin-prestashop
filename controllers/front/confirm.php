<?php

/*
 * 2020 kevin.
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
 *  @author 2020 kevin. <info@getkevin.eu>
 *  @copyright kevin.
 *  @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class KevinConfirmModuleFrontController extends ModuleFrontController {

    /**
     * Process confirm request.
     */
    public function postProcess() {
        if (!$this->module->active) {
            Tools::redirect('index');
        }

        $payment_id = Tools::getValue('paymentId');

        if (!$payment_id) {

            return $this->displayError($this->module->l('An error occurred. Please contact the merchant for more information.'));
        }

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'kevin WHERE payment_id = \'' . pSQL($payment_id) . '\'';

        if ($row = Db::getInstance()->getRow($sql)) {

            try {
                $order = new Order($row['id_order']);
                if (!Validate::isLoadedObject($order)) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }
                $customer = new Customer($order->id_customer);

                if (!Validate::isLoadedObject($customer)) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }
                $attr = array(
                    'PSU-IP-Address' => $row['ip_address'],
                );
                $kevinPayment = $this->module->getClient()->payment();
                $response = $kevinPayment->getPaymentStatus($row['payment_id'], $attr);
                $paymentinfo = $kevinPayment->getPayment($row['payment_id'], array('PSU-IP-Address' => $row['ip_address']));
                $kevinAuth = $this->module->getClient()->auth();
                $response['payment_id'] = $row['payment_id'];
                if (isset($paymentinfo['cardStatus']) || isset($paymentinfo['cardStatus'])) {
                    $order->payment = 'Kevin (Credit/Debit card)';
//                    $order->update();
                } else {
                    if (isset($paymentinfo['bankPaymentMethod']['bankId'])) {
                        $bank = $kevinAuth->getBank($paymentinfo['bankPaymentMethod']['bankId']);
                        if (!empty($bank['officialName'])) {
                            $order->payment = 'Kevin (' . $bank['officialName'] . ')';
//                        $order->update();
                        }
                    } else {
                        $order->payment = 'Kevin';
                    }
                }
                $old_os_id = $order->getCurrentOrderState()->id;
                $new_os_id = null;
                switch ($response['group']) {
                    case 'started':
                        $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_STARTED');
                        break;
                    case 'pending':
                        $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_PENDING');
                        break;
                    case 'completed':
                        $new_os_id = Configuration::get('PS_OS_PAYMENT');
                        break;
                    case 'failed':
                        $new_os_id = Configuration::get('PS_OS_ERROR');
                        break;
                    default:
                        $new_os_id = null;
                }

                if (!$new_os_id) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }
                       $sql = "DELETE FROM `" . _DB_PREFIX_ . "order_payment` WHERE `order_reference` = '{$order->reference}'";
                    Db::getInstance()->execute($sql);
                if (($old_os_id == Configuration::get('KEVIN_ORDER_STATUS_STARTED') || $old_os_id == Configuration::get('KEVIN_ORDER_STATUS_PENDING')) && $old_os_id != Configuration::get('PS_OS_PAYMENT')
                ) {
                    $order->setCurrentState($new_os_id);
                }
                $params = array(
                    'id_cart' => $order->id_cart,
                    'id_module' => $this->module->id,
                    'id_order' => $order->id,
                    'key' => $customer->secure_key,
                );

                Tools::redirect($this->context->link->getPageLink('order-confirmation', null, null, $params));
            } catch (\Kevin\KevinException $e) {

                return $this->displayError($this->module->l('An error occurred. Please contact the merchant for more information.'));
            }
        }

        return $this->displayError($this->module->l('An error occurred. Please contact the merchant for more information.'));
    }

    /**
     * @param $message
     * @param bool $description
     * @throws PrestaShopException
     */
    protected function displayError($message, $description = false) {
        $value = '<a href="' . $this->context->link->getPageLink('order') . '">' . $this->module->l('Payment') . '</a>';
        $value .= '<span class="navigation-pipe">&gt;</span>' . $this->module->l('Error');
        $this->context->smarty->assign('path', $value);

        array_push($this->errors, $this->module->l($message), $description);

        return $this->setTemplate('error.tpl');
    }

}
