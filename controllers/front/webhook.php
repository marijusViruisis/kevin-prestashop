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

        $request_body  = file_get_contents('php://input');
        $request_array = json_decode($request_body, true);

        if (is_array($request_array)) {
            $payment_id = empty($request_array['id']) ? null : $request_array['id'];
            $payment_status = empty($request_array['status']) ? null : $request_array['status'];
            $payment_status_group = empty($request_array['statusGroup']) ? null : $request_array['statusGroup'];
        } else {
            $payment_id = Tools::getValue('id');
            $payment_status = Tools::getValue('status');
            $payment_status_group = Tools::getValue('statusGroup');
        }

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

            $os_started = Configuration::get('KEVIN_ORDER_STATUS_STARTED');
            $os_pending = Configuration::get('KEVIN_ORDER_STATUS_PENDING');
            $os_completed = Configuration::get('KEVIN_ORDER_STATUS_COMPLETED');
            $os_failed = Configuration::get('KEVIN_ORDER_STATUS_FAILED');

            $old_os_id = $order->getCurrentOrderState()->id;
            $new_os_id = null;

            if (in_array($old_os_id, array($os_started, $os_pending))) {
                switch ($payment_status_group) {
                    case 'completed':
                        $new_os_id = $os_completed;
                        break;
                    case 'failed':
                        $new_os_id = $os_failed;
                        break;
                    default:
                        $new_os_id = null;
                }
            }

            if (!$new_os_id) {
                die();
            }

            if ($old_os_id != $new_os_id) {
                $order->setCurrentState($new_os_id);
            }

            exit();
        }

        die();
    }
}
