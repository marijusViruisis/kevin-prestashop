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
 *  @author 2020 kevin. <help@kevin.eu>
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
                $order = new Order($row['id_order']);
                if (!Validate::isLoadedObject($order)) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }
                $customer = new Customer($order->id_customer);

                if (!Validate::isLoadedObject($customer)) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }

                $params = array(
                    'id_cart' => $order->id_cart,
                    'id_module' => $this->module->id,
                    'id_order' => $order->id,
                    'key' => $customer->secure_key,
                    'statusGroup' => Tools::getValue('statusGroup')
                );

                Tools::redirect($this->context->link->getPageLink('order-confirmation', null, null, $params));
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
