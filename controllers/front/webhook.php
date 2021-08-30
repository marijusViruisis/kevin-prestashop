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

class KevinWebhookModuleFrontController extends ModuleFrontController {

    /**
     * Process webhook request.
     */
    public function postProcess() {
        if (!$this->module->active) {
            exit;
        }
        $request_body = file_get_contents('php://input');
        $request_array = json_decode($request_body, true);
        if (is_array($request_array)) {
            $payment_id = empty($request_array['id']) ? null : $request_array['id'];
        } else {
            $payment_id = Tools::getValue('id');
        }
        if (!$payment_id) {
            exit;
        }
        if (isset($request_array['type']) && $request_array['type'] == 'PAYMENT_REFUND') {
            $payment_id = empty($request_array['paymentId']) ? null : $request_array['paymentId'];
        }
        $kevinPayment = $this->module->getClient()->payment();
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'kevin WHERE payment_id = \'' . pSQL($payment_id) . '\'';
        if ($row = Db::getInstance()->getRow($sql)) {
            try {
                $order = new Order($row['id_order']);
                if (isset($request_array['paymentId']) && $request_array['paymentId'] && isset($request_array['type']) && $request_array['type'] == 'PAYMENT_REFUND' && isset($request_array['statusGroup']) && $request_array['statusGroup']) {
                    $sql = 'SELECT id_order_state FROM ' . _DB_PREFIX_ . 'order_history WHERE id_order_state = ' . (int) Configuration::get('PS_OS_REFUND') . ' AND id_order = ' . (int) $order->id;
                    $isExistAccepted = Db::getInstance()->getValue($sql);
                    if (!$isExistAccepted) { //if already refunded
                        $new_history = new OrderHistory();
                        $new_history->id_order = (int) $order->id;
                        $new_history->changeIdOrderState((int) Configuration::get('PS_OS_REFUND'), $order, true);
                        $new_history->addWithemail();
                    }
                } else {
                    $response = $kevinPayment->getPaymentStatus($payment_id, array('PSU-IP-Address' => $row['ip_address']));
                    $response['payment_id'] = $row['payment_id'];
                    if (isset($response['group'])) {
                        $statusgroup = $response['group'];
                        $sql = "SELECT id_order FROM `" . _DB_PREFIX_ . "orders` WHERE `reference` LIKE '" . $row['id_order'] . "'";
                        $idOrder = Db::getInstance()->getValue($sql);
                        $paymentinfo = $kevinPayment->getPayment($payment_id, array('PSU-IP-Address' => $row['ip_address']));
                        $kevinAuth = $this->module->getClient()->auth();
                        if (isset($paymentinfo['cardStatus'])) {
                            $order->payment = 'Kevin (Credit/Debit card)';
                        } else {
                            $bank = $kevinAuth->getBank($paymentinfo['bankPaymentMethod']['bankId']);
                            if (!empty($bank['officialName'])) {
                                $order->payment = 'Kevin (' . $bank['officialName'] . ')';
                            }
                        }
                        if (!Validate::isLoadedObject($order)) {
                            exit;
                        }
                        $customer = new Customer($order->id_customer);
                        if (!Validate::isLoadedObject($customer)) {
                            exit;
                        }
                        $old_os_id = $order->getCurrentOrderState()->id;
                        $new_os_id = null;
                        $new_os_id = $this->isPaymentCompletedOrFailed($statusgroup);
                        if (!$new_os_id) {
                            exit;
                        } else {
                            if ($old_os_id != $new_os_id) {
                                $order->setCurrentState($new_os_id);
                            }
                        }
                    }
                    exit('OK');
                }
            } catch (Exception $e) {
                exit($e->getMessage());
            }
        }
    }

    protected function isPaymentCompletedOrFailed($payment_status_group = '') {
        switch ($payment_status_group) {
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
        return (int) $new_os_id;
    }

}
