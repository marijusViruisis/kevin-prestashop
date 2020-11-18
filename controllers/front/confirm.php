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

class KevinConfirmModuleFrontController extends ModuleFrontController
{
    /**
     * Process confirm request.
     */
    public function postProcess()
    {
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

                $kevinPayment = $this->module->getClient()->payment();
                $response = $kevinPayment->getPaymentStatus($row['payment_id'], array('PSU-IP-Address' => $row['ip_address']));

                $os_started = Configuration::get('KEVIN_ORDER_STATUS_STARTED');
                $os_pending = Configuration::get('KEVIN_ORDER_STATUS_PENDING');
                $os_completed = Configuration::get('KEVIN_ORDER_STATUS_COMPLETED');
                $os_failed = Configuration::get('KEVIN_ORDER_STATUS_FAILED');

                $old_os_id = $order->getCurrentOrderState()->id;
                $new_os_id = null;

                if (in_array($old_os_id, array($os_started))) {
                    if ($response['group'] === 'failed') {
                        $new_os_id = $os_failed;
                    } else {
                        $new_os_id = $os_pending;
                    }
                }

                if (!$new_os_id) {
                    Tools::redirect($this->context->link->getPageLink('order'));
                }

                if ($old_os_id != $new_os_id) {
                    $order->setCurrentState($new_os_id);
                }

                $params = array(
                    'id_order' => $order->id,
                    'id_module' => $this->module->id,
                    'id_cart' => $order->id_cart,
                    'key' => $customer->secure_key
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
    protected function displayError($message, $description = false)
    {
        $value = '<a href="' . $this->context->link->getPageLink('order') . '">' . $this->module->l('Payment') . '</a>';
        $value .= '<span class="navigation-pipe">&gt;</span>' . $this->module->l('Error');
        $this->context->smarty->assign('path', $value);

        array_push($this->errors, $this->module->l($message), $description);

        return $this->setTemplate('error.tpl');
    }
}
