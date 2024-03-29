<?php

/*
 * 2022 kevin.
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
 *  @author 2022 kevin. <help@kevin.eu>
 *  @copyright kevin.
 *  @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

use Kevin\KevinException;

class KevinRedirectModuleFrontController extends ModuleFrontController
{
    /**
     * Process redirect request.
     */
    public function postProcess()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        if (!$this->module->active) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        $cart = $this->context->cart;
        $currency = new Currency($cart->id_currency);

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $cart_id = (int) $cart->id;
        $payment_status = Configuration::get('KEVIN_ORDER_STATUS_STARTED');
        $message = null;
        $currency_id = $cart->id_currency;
        $secure_key = $customer->secure_key;
        $bank_id = Tools::getValue('id');
        $kevinAuth = $this->module->getClient()->auth();
        if ($bank_id == 'card') {
            $module_name = 'kevin (Credit/Debit card)';
        } else {
            $bank = $kevinAuth->getBank($bank_id);
            if (!empty($bank['officialName'])) {
                $module_name = 'kevin ('.$bank['officialName'].')';
            } else {
                $module_name = $this->module->displayName;
            }
        }

        $this->module->validateOrder($cart_id, $payment_status, $cart->getOrderTotal(), $module_name, $message, [], $currency_id, false, $secure_key);

        $order_id = Order::getOrderByCartId($cart_id);
        $order = new Order($order_id);

        $kevinPayment = $this->module->getClient()->payment();

        $creditor_name = $this->module->creditorName;
        $creditor_account = $this->module->creditorAccount;

        $redirect_preferred = Configuration::get('KEVIN_REDIRECT_PREFERRED');
        if ($redirect_preferred === false) {
            $redirect_preferred = 1;
        }
        $attr = [
            'amount' => number_format($cart->getOrderTotal(), 2, '.', ''),
            'currencyCode' => $currency->iso_code,
            'description' => sprintf($this->module->l('Order').' %s', $order->reference),
            'identifier' => ['email' => $customer->email],
            'redirectPreferred' => (bool) $redirect_preferred,
            'Redirect-URL' => $this->context->link->getModuleLink('kevin', 'confirm', [], true),
            'Webhook-URL' => $this->module->getContextUrl('kevin', 'webhook', [], true),
        ];

        $attr['bankPaymentMethod'] = [
            'creditorName' => $creditor_name,
            'endToEndId' => (string) $order_id,
            'creditorAccount' => [
                'iban' => $creditor_account,
            ],
        ];
        if ($bank_id !== null) {
            if ($bank_id == 'card') {
                $attr['cardPaymentMethod'] = [];
                $attr['paymentMethodPreferred'] = 'card';
            } else {
                $attr['bankId'] = $bank_id;
            }
        }
        try {
            $response = $kevinPayment->initPayment($attr);
        } catch (KevinException $e) {
            $this->context->smarty->assign([
                'errors' => [$e->getMessage()],
                'THEME_CSS_DIR' => _THEME_CSS_DIR_,
            ]);

            $this->errors[] = $e->getMessage();

            if (version_compare(_PS_VERSION_, '1.7.0', '<')) {
                return $this->setTemplate('error.tpl');
            } else {
                return $this->setTemplate('module:kevin/views/templates/front/error.tpl');
            }
        }

        Db::getInstance()->insert('kevin', [
            'id_order' => (int) $order_id,
            'payment_id' => pSQL($response['id']),
            'ip_address' => pSQL(Tools::getRemoteAddr()),
        ]);

        $lang = $this->context->language->iso_code;
        $query = parse_url($response['confirmLink'], \PHP_URL_QUERY);
        $response['confirmLink'] .= ($query) ? '&lang='.$lang : '?lang='.$lang;

        return Tools::redirect($response['confirmLink']);
    }

    /**
     * @param $message
     * @param bool $description
     *
     * @throws PrestaShopException
     */
    protected function displayError($message, $description = false)
    {
        $value = '<a href="'.$this->context->link->getPageLink('order', null, null, 'step=3').'">'.$this->module->l('Payment').'</a>';
        $value .= '<span class="navigation-pipe">&gt;</span>'.$this->module->l('Error');
        $this->context->smarty->assign('path', $value);

        array_push($this->errors, $this->module->l($message), $description);

        return $this->setTemplate('error.tpl');
    }
}
