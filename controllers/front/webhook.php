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

class KevinWebhookModuleFrontController extends ModuleFrontController
{
    /**
     * Process webhook request.
     */
    public function postProcess()
    {
        if (!$this->module->active) {
            die();
        }

        $payment_id = Tools::getValue('id');
        $payment_status = Tools::getValue('status');
        $payment_status_group = Tools::getValue('statusGroup');
        if (!$payment_id || !$payment_status || !$payment_status_group) {
            die();
        }

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'kevin WHERE payment_id = \'' . pSQL($payment_id) . '\'';
        if ($row = Db::getInstance()->getRow($sql)) {

            $order = new Order($row['id_order']);
            if (!Validate::isLoadedObject($order)) {
                die();
            }

            $customer = new Customer($order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                die();
            }

            switch ($payment_status_group) {
                case 'started':
                    $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_STARTED');
                    break;
                case 'pending':
                    $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_PENDING');
                    break;
                case 'completed':
                    $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_COMPLETED');
                    break;
                case 'failed':
                    $new_os_id = Configuration::get('KEVIN_ORDER_STATUS_FAILED');
                    break;
                default:
                    $new_os_id = null;
            }

            if (!$new_os_id) {
                die();
            }

            $old_os_id = $order->getCurrentOrderState()->id;
            if ($old_os_id != $new_os_id) {
                $order->setCurrentState($new_os_id);
            }

            exit();
        }

        die();
    }
}
