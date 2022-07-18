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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

/**
 * Class Kevin.
 */
class Kevin extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = [];
    protected $_warning;
    public $clientId;
    public $clientSecret;
    public $creditorName;
    public $creditorAccount;

    /**
     * Kevin constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'kevin';
        $this->tab = 'payments_gateways';
        $this->version = '1.10.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->author = 'kevin.';
        $this->controllers = ['redirect', 'confirm', 'webhook'];

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('kevin.');
        $this->description = $this->l('kevin. is a payment infrastructure company which offers payment initiation service in EU&EEA.');

        $this->confirmUninstall = $this->l('Are you sure you would like to uninstall?');

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(['KEVIN_CLIENT_ID', 'KEVIN_CLIENT_SECRET']);
        if (!empty($config['KEVIN_CLIENT_ID'])) {
            $this->clientId = $config['KEVIN_CLIENT_ID'];
        }
        if (!empty($config['KEVIN_CLIENT_SECRET'])) {
            $this->clientSecret = $config['KEVIN_CLIENT_SECRET'];
        }
        if (!$this->_warning && (!isset($this->clientId) || !isset($this->clientSecret))) {
            $this->_warning = $this->l('Client ID and Client Secret must be configured before using this module.');
        }

        $config = Configuration::getMultiple(['KEVIN_CREDITOR_NAME', 'KEVIN_CREDITOR_ACCOUNT']);
        if (!empty($config['KEVIN_CREDITOR_NAME'])) {
            $this->creditorName = $config['KEVIN_CREDITOR_NAME'];
        }
        if (!empty($config['KEVIN_CREDITOR_ACCOUNT'])) {
            $this->creditorAccount = $config['KEVIN_CREDITOR_ACCOUNT'];
        }
        if (!$this->_warning && (!isset($this->creditorName) || !isset($this->creditorAccount))) {
            $this->_warning = $this->l('Company Name and Company Bank Account must be configured before using this module.');
        }
    }

    /**
     * Process module installation.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        include __DIR__.'/sql/install.php';

        $order_statuses = $this->getDefaultOrderStatuses();
        foreach ($order_statuses as $status_key => $status_config) {
            $this->addOrderStatus($status_key, $status_config);
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayAdminOrderTabShip') &&
            $this->registerHook('displayAdminOrderTabLink') &&
            $this->registerHook('displayAdminOrderTabContent') &&
            $this->registerHook('hookActionOrderSlipAdd') &&
            $this->registerHook('displayOrderConfirmation');
    }

    /**
     * Process module uninstall.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $config_form_values = $this->getConfigFormValues();
        foreach (array_keys($config_form_values) as $config_key) {
            Configuration::deleteByName($config_key);
        }

        $order_statuses = $this->getDefaultOrderStatuses();
        foreach ($order_statuses as $status_key => $status_config) {
            $this->removeOrderStatus($status_key);
            Configuration::deleteByName($status_key);
        }

        include __DIR__.'/sql/uninstall.php';

        return parent::uninstall() &&
            $this->unregisterHook('header') &&
            $this->unregisterHook('backOfficeHeader') &&
            $this->unregisterHook('payment') &&
            $this->unregisterHook('orderConfirmation') &&
            $this->unregisterHook('paymentOptions') &&
            $this->unregisterHook('displayOrderConfirmation') &&
            $this->unregisterHook('hookActionOrderSlipAdd');
    }

    public function reset()
    {
        if (!$this->uninstall(false)) {
            return false;
        }
        if (!$this->install(false)) {
            return false;
        }

        return true;
    }

    /**
     * Load the configuration form.
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function getContent()
    {
        $is_submit = false;
        $buttons = ['submitKevinModule1', 'submitKevinModule2', 'submitKevinModule3'];
        foreach ($buttons as $button) {
            if (((bool) (Tools::isSubmit($button))) === true) {
                $is_submit = true;
                break;
            }
        }

        if ($is_submit === true) {
            $this->postValidation();
            if (!count($this->_postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br>';
        }

        if (isset($this->_warning) && !count($this->_postErrors) && $is_submit === false) {
            $this->_html .= $this->displayError($this->_warning);
        }

        $this->context->controller->addCSS($this->_path.'/views/css/back.css');

        $this->context->smarty->assign('module_dir', $this->_path);

        $this->_html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     */
    protected function renderForm()
    {
        $client_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Client Details'),
                    'icon' => 'icon-key',
                ],
                'input' => [
                    [
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Client ID'),
                        'name' => 'KEVIN_CLIENT_ID',
                        'required' => true,
                    ],
                    [
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Client Secret'),
                        'name' => 'KEVIN_CLIENT_SECRET',
                        'required' => true,
                    ],
                    [
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Endpoint Secret'),
                        'name' => 'KEVIN_ENDPOINT_SECRET',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitKevinModule1',
                ],
            ],
        ];

        $creditor_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Company Details'),
                    'icon' => 'icon-user',
                ],
                'input' => [
                    [
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Company Name'),
                        'name' => 'KEVIN_CREDITOR_NAME',
                        'required' => true,
                    ],
                    [
                        'col' => 6,
                        'type' => 'text',
                        'label' => $this->l('Company Bank Account'),
                        'name' => 'KEVIN_CREDITOR_ACCOUNT',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitKevinModule2',
                ],
            ],
        ];

        $settings_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Redirect Preferred'),
                        'name' => 'KEVIN_REDIRECT_PREFERRED',
                        'desc' => $this->l('Redirect user directly to bank.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Cart duplicate'),
                        'name' => 'KEVIN_CART_DUPLICATE',
                        'desc' => $this->l('Duplicates cart after failed payment.'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitKevinModule3',
                ],
            ],
        ];

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitKevinModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$client_form, $creditor_form, $settings_form]);
    }

    /**
     * Set values for the inputs.
     *
     * @return array
     */
    protected function getConfigFormValues()
    {
        $redirectPreferred = Tools::getValue('KEVIN_REDIRECT_PREFERRED', Configuration::get('KEVIN_REDIRECT_PREFERRED'));
        if ($redirectPreferred === false) {
            $redirectPreferred = 1;
        }

        $cartDuplicate = Tools::getValue('KEVIN_CART_DUPLICATE', Configuration::get('KEVIN_CART_DUPLICATE'));
        if ($cartDuplicate === false) {
            $cartDuplicate = 0;
        }

        return [
            'KEVIN_CLIENT_ID' => Tools::getValue('KEVIN_CLIENT_ID', Configuration::get('KEVIN_CLIENT_ID')),
            'KEVIN_CLIENT_SECRET' => Tools::getValue('KEVIN_CLIENT_SECRET', Configuration::get('KEVIN_CLIENT_SECRET')),
            'KEVIN_ENDPOINT_SECRET' => Tools::getValue('KEVIN_ENDPOINT_SECRET', Configuration::get('KEVIN_ENDPOINT_SECRET')),
            'KEVIN_CREDITOR_NAME' => Tools::getValue('KEVIN_CREDITOR_NAME', Configuration::get('KEVIN_CREDITOR_NAME')),
            'KEVIN_CREDITOR_ACCOUNT' => Tools::getValue('KEVIN_CREDITOR_ACCOUNT', Configuration::get('KEVIN_CREDITOR_ACCOUNT')),
            'KEVIN_REDIRECT_PREFERRED' => $redirectPreferred,
            'KEVIN_CART_DUPLICATE' => $cartDuplicate,
        ];
    }

    /**
     * Validate form data.
     */
    protected function postValidation()
    {
        if (!Tools::getValue('KEVIN_CLIENT_ID')) {
            $this->_postErrors[] = $this->l('Client ID is required.');
        }
        if (!Tools::getValue('KEVIN_CLIENT_SECRET')) {
            $this->_postErrors[] = $this->l('Client Secret is required.');
        }
        if (!Tools::getValue('KEVIN_CREDITOR_NAME')) {
            $this->_postErrors[] = $this->l('Company Name is required.');
        }
        if (!Tools::getValue('KEVIN_CREDITOR_ACCOUNT')) {
            $this->_postErrors[] = $this->l('Company Bank Account is required.');
        }

        $matches = [];
        preg_match('/[a-zA-Z0-9 ]*/', Tools::getValue('KEVIN_CREDITOR_NAME'), $matches);
        if (!in_array(Tools::getValue('KEVIN_CREDITOR_NAME'), $matches)) {
            $this->_postErrors[] = $this->l('Company Name: Please use only letters (a-z or A-Z), numbers (0-9) or spaces only in this field.');
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $config_form_values = $this->getConfigFormValues();
        foreach (array_keys($config_form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Hook files for frontend.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/front.css');
        $this->context->controller->addJs($this->_path."views/js/front.js?v={$this->version}");
    }

    public function hookActionOrderSlipAdd($params)
    {
        $this->hookBackOfficeHeader();
    }

    /**
     * Hook files for frontend.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
        if (Tools::getValue('id_order')) {
            $this->context->controller->addJquery();
            Media::addJsDefL('kevin_text', $this->l('Get your money back through the kevin system'));
            Media::addJsDefL('id_order', Tools::getValue('id_order'));
            $this->context->controller->addJs($this->_path."views/js/back.js?v={$this->version}");
        }
        $order = new Order(Tools::getValue('id_order'));
        if (Tools::isSubmit('partialRefund') && isset($order) && Tools::getValue('refundwithkevin')) {
            if (Tools::isSubmit('partialRefundProduct') && ($refunds = Tools::getValue('partialRefundProduct')) && is_array($refunds)) {
                $amount = 0;
                $order_detail_list = [];
                $full_quantity_list = [];
                foreach ($refunds as $id_order_detail => $amount_detail) {
                    $quantity = Tools::getValue('partialRefundProductQuantity');
                    if (!$quantity[$id_order_detail]) {
                        continue;
                    }

                    $full_quantity_list[$id_order_detail] = (int) $quantity[$id_order_detail];

                    $order_detail_list[$id_order_detail] = [
                        'quantity' => (int) $quantity[$id_order_detail],
                        'id_order_detail' => (int) $id_order_detail,
                    ];

                    $order_detail = new OrderDetail((int) $id_order_detail);
                    if (empty($amount_detail)) {
                        $order_detail_list[$id_order_detail]['unit_price'] = (!Tools::getValue('TaxMethod') ? $order_detail->unit_price_tax_excl : $order_detail->unit_price_tax_incl);
                        $order_detail_list[$id_order_detail]['amount'] = $order_detail->unit_price_tax_incl * $order_detail_list[$id_order_detail]['quantity'];
                    } else {
                        $order_detail_list[$id_order_detail]['amount'] = (float) str_replace(',', '.', $amount_detail);
                        $order_detail_list[$id_order_detail]['unit_price'] = $order_detail_list[$id_order_detail]['amount'] / $order_detail_list[$id_order_detail]['quantity'];
                    }
                    $amount += $order_detail_list[$id_order_detail]['amount'];
                }

                $shipping_cost_amount = (float) str_replace(',', '.', Tools::getValue('partialRefundShippingCost')) ?: false;

                if ($amount == 0 && $shipping_cost_amount == 0) {
                    if (!empty($refunds)) {
                        $this->errors[] = $this->l('Please enter a quantity to proceed with your refund.', [], 'Admin.Orderscustomers.Notification');
                    } else {
                        $this->errors[] = $this->l('Please enter an amount to proceed with your refund.', [], 'Admin.Orderscustomers.Notification');
                    }

                    return false;
                }

                $choosen = false;
                $voucher = 0;

                if ((int) Tools::getValue('refund_voucher_off') == 1) {
                    $amount -= $voucher = (float) Tools::getValue('order_discount_price');
                } elseif ((int) Tools::getValue('refund_voucher_off') == 2) {
                    $choosen = true;
                    $amount = $voucher = (float) Tools::getValue('refund_voucher_choose');
                }

                if ($shipping_cost_amount > 0) {
                    if (!Tools::getValue('TaxMethod')) {
                        $tax = new Tax();
                        $tax->rate = $order->carrier_tax_rate;
                        $tax_calculator = new TaxCalculator([$tax]);
                        $amount += $tax_calculator->addTaxes($shipping_cost_amount);
                    } else {
                        $amount += $shipping_cost_amount;
                    }
                }
            }

            if (Tools::isSubmit('cancel_product') && ($refunds = Tools::getValue('cancel_product')) && is_array($refunds)) {
                $amount = 0;
                foreach ($refunds as $key => $refund) {
                    if (strpos($key, 'amount') !== false) {
                        $amount += $refund;
                    }
                }
            }

            $id_order = Tools::getValue('id_order');
            $sql = 'SELECT * FROM '._DB_PREFIX_.'kevin WHERE id_order = \''.pSQL($id_order).'\'';
            $order = new Order($id_order);

            if ($row = Db::getInstance()->getRow($sql)) {
                try {
                    $paymentId = $row['payment_id'];
                    $amount = number_format($amount, 2);
                    if (!$paymentId) {
                        exit();
                    }
                    $kevinPayment = $this->getClient()->payment();
                    $webhook_url = $this->context->link->getModuleLink('kevin', 'webhook', [], true);
                    $attr = [
                        'amount' => $amount,
                        'Webhook-URL' => $webhook_url,
                    ];

                    try {
                        $response = $kevinPayment->initiatePaymentRefund($paymentId, $attr);
                    } catch (\Kevin\KevinException $e) {
                        $customer = new Customer($order->id_customer);
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int) $order->id_customer;
                        $customer_thread->id_shop = (int) $order->id_shop;
                        $customer_thread->id_order = (int) $order->id;
                        $customer_thread->id_lang = (int) $order->id_lang;
                        $customer_thread->email = $customer->email;
                        $customer_thread->status = 'closed';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();
                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $e->getMessage();
                        $customer_message->private = 1;
                        $customer_message->add();

                        return true;
                    }

                    if ($response) {
                        $customer = new Customer($order->id_customer);
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = (int) $order->id_customer;
                        $customer_thread->id_shop = (int) $order->id_shop;
                        $customer_thread->id_order = (int) $order->id;
                        $customer_thread->id_lang = (int) $order->id_lang;
                        $customer_thread->email = $customer->email;
                        $customer_thread->status = 'closed';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();
                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 0;
                        $customer_message->message = $this->l('Refund process started. Amount ').Tools::displayPrice($response['amount']);
                        $customer_message->private = 1;
                        $customer_message->add();

                        return true;
                    } else {
                        return $this->displayError($this->l('Refund failed.'));
                    }
                } catch (\Kevin\KevinException $e) {
                    $customer = new Customer($order->id_customer);
                    $customer_thread = new CustomerThread();
                    $customer_thread->id_contact = 0;
                    $customer_thread->id_customer = (int) $order->id_customer;
                    $customer_thread->id_shop = (int) $order->id_shop;
                    $customer_thread->id_order = (int) $order->id;
                    $customer_thread->id_lang = (int) $order->id_lang;
                    $customer_thread->email = $customer->email;
                    $customer_thread->status = 'closed';
                    $customer_thread->token = Tools::passwdGen(12);
                    $customer_thread->add();
                    $customer_message = new CustomerMessage();
                    $customer_message->id_customer_thread = $customer_thread->id;
                    $customer_message->id_employee = 0;
                    $customer_message->message = $e->getMessage();
                    $customer_message->private = 1;
                    $customer_message->add();

                    return true;
                }
            }
        }
    }

    /**
     * Display payment buttons.
     *
     * @param $params
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookPayment($params)
    {
        if (!$this->validateClientCredentials()) {
            return false;
        }

        $banks = [];

        $bank_data = $this->getBanks();

        $kevinAuth = $this->getClient()->auth();
        $paymentMethods = $kevinAuth->getPaymentMethods();
        if (in_array('bank', $paymentMethods['data'])) {
            foreach ($bank_data as $bank_datum) {
                $banks[] = [
                    'id' => $bank_datum['id'],
                    'title' => $bank_datum['name'],
                    'logo' => $bank_datum['imageUri'],
                    'action' => $this->context->link->getModuleLink($this->name, 'redirect', ['id' => $bank_datum['id']], true),
                ];
            }
            if (in_array('card', $paymentMethods['data'])) {
                $banks[] = [
                    'id' => 'card',
                    'title' => $this->l('Credit/Debit card'),
                    'logo' => 'https://cdn.kevin.eu/banks/images/VISA_MC.png',
                    'action' => $this->context->link->getModuleLink($this->name, 'redirect', ['id' => 'card'], true),
                ];
            }
        }

        $this->smarty->assign([
            'banks' => $banks,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /**
     * Display confirmation page.
     *
     * @param $params
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookOrderConfirmation($params)
    {
        $order = $params['objOrder'];

        return $this->orderconfirm($order->id);
    }

    /**
     * Return payment options.
     *
     * @param array
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }

        if (!$this->validateClientCredentials()) {
            return [];
        }
        $options = [];
        $bank_data = $this->getBanks();

        $kevinAuth = $this->getClient()->auth();
        $paymentMethods = $kevinAuth->getPaymentMethods();

        if (in_array('bank', $paymentMethods['data'])) {
            foreach ($bank_data as $bank_datum) {
                $option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
                $option->setModuleName($this->name);
                $option->setCallToActionText($bank_datum['name']);
                $option->setLogo($bank_datum['imageUri']);
                $option->setAction($this->context->link->getModuleLink($this->name, 'redirect', ['id' => $bank_datum['id']], true));
                $options[] = $option;
            }
        }
        if (in_array('card', $paymentMethods['data'])) {
            $option = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $option->setModuleName($this->name);
            $option->setCallToActionText('Credit/Debit card');
            $option->setLogo('https://cdn.kevin.eu/banks/images/VISA_MC.png');
            $option->setAction($this->context->link->getModuleLink($this->name, 'redirect', ['id' => 'card'], true));
            $options[] = $option;
        }

        return $options;
    }

    /**
     * Return Kevin PHP Client instance.
     *
     * @return \Kevin\Client
     *
     * @throws \Kevin\KevinException
     */
    public function getClient()
    {
        $options = [
            'error' => 'exception',
            'version' => '0.3',
        ];
        $options = array_merge($options, $this->getSystemData());

        return new \Kevin\Client($this->clientId, $this->clientSecret, $options);
    }

    public function getSystemData()
    {
        return [
            'pluginVersion' => $this->version,
            'pluginPlatform' => 'PrestaShop',
            'pluginPlatformVersion' => _PS_VERSION_,
        ];
    }

    /**
     * Return list of supported banks.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getBanks()
    {
        try {
            $cart = $this->context->cart;
            $address = new Address($cart->id_address_invoice);
            $country = new Country($address->id_country);

            $kevinAuth = $this->getClient()->auth();
            $banks = $kevinAuth->getBanks(['countryCode' => $country->iso_code]);
        } catch (\Kevin\KevinException $exception) {
            return [];
        }

        return $banks['data'];
    }

    /**
     * Return list of default order statuses.
     *
     * @return array[]
     */
    protected function getDefaultOrderStatuses()
    {
        return [
            'KEVIN_ORDER_STATUS_STARTED' => [
                'send_email' => false,
                'name' => $this->l('Payment Started'),
                'color' => 'Lavender',
                'paid' => false,
            ],
            'KEVIN_ORDER_STATUS_PENDING' => [
                'name' => $this->l('Payment Pending'),
                'color' => 'Orchid',
                'paid' => false,
            ],
        ];
    }

    /**
     * Register module related order statuses.
     *
     * @param $statusKey
     * @param $statusConfig
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addOrderStatus($statusKey, $statusConfig)
    {
        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->color = $statusConfig['color'];
        $orderState->send_email = false;
        $orderState->paid = $statusConfig['paid'];
        $orderState->name = [];
        $orderState->delivery = false;
        $orderState->logable = false;
        $orderState->hidden = false;
        foreach (Language::getLanguages() as $language) {
            if ($statusKey == 'KEVIN_ORDER_STATUS_COMPLETED') {
                $orderState->template[$language['id_lang']] = 'payment';
            }
            $orderState->name[$language['id_lang']] = $statusConfig['name'];
        }
        $orderState->add();

        Configuration::updateValue($statusKey, $orderState->id);
    }

    /**
     * Unregister module related order statuses.
     *
     * @param $statusKey
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function removeOrderStatus($statusKey)
    {
        $order_state_id = Configuration::get($statusKey);
        if ($order_state_id) {
            $orderState = new OrderState($order_state_id);
            if (Validate::isLoadedObject($orderState)) {
                try {
                    $orderState->delete();
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * Validate client credentials.
     *
     * @return bool
     */
    public function validateClientCredentials()
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->creditorName) || empty($this->creditorAccount)) {
            return false;
        }

        return true;
    }

    public function hookdisplayOrderConfirmation($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }
        $orderid = $order->id;
        if (!$this->active) {
            return null;
        }

        return $this->orderconfirm($orderid);
    }

    public function orderconfirm($orderid)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'kevin WHERE id_order = \''.pSQL($orderid).'\'';
        if ($row = Db::getInstance()->getRow($sql)) {
            $order = new Order($row['id_order']);
            if (!Validate::isLoadedObject($order)) {
                Tools::redirect($this->context->link->getPageLink('order'));
            }

            $customer = new Customer($order->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                Tools::redirect($this->context->link->getPageLink('order'));
            }

            $statusGroup = Tools::getValue('statusGroup');
            if ($statusGroup != 'completed') {
                if ($statusGroup == 'failed' && Configuration::get('KEVIN_CART_DUPLICATE')) {
                    if (!Validate::isLoadedObject($order)) {
                        Tools::redirect($this->context->link->getPageLink('order'));
                    }
                    if ($order) {
                        $oldCart = new Cart($order->id_cart);
                        $duplication = $oldCart->duplicate();
                        if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
                            $this->errors[] = Tools::displayError($this->l(
                                'Sorry. We cannot renew your order.'
                            ));
                        } elseif (!$duplication['success']) {
                            $this->errors[] = Tools::displayError($this->l(
                                'Some items are no longer available, and we are unable to renew your order.'
                            ));
                        } else {
                            $this->context->cookie->id_cart = $duplication['cart']->id;
                            $context = $this->context;
                            $context->cart = $duplication['cart'];
                            CartRule::autoAddToCart($context);
                            $this->context->cookie->write();
                        }
                    }
                }

                $this->smarty->assign(
                    [
                        'group' => $statusGroup,
                    ]
                );
                if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
                    return $this->display(__FILE__, '/views/templates/hook/payment_return.tpl');
                } else {
                    return $this->fetch('module:kevin/views/templates/hook/payment_return.tpl');
                }
            } else {
                if (version_compare(_PS_VERSION_, '1.7.0') < 0) {
                    $customer = new Customer($order->id_customer);

                    $this->smarty->assign([
                        'id_order_formatted' => sprintf('#%06d', $order->id),
                        'email' => $customer->email,
                    ]);

                    return $this->display(__FILE__, '/views/templates/hook/order-confirmation.tpl');
                } else {
                }
            }
        }
    }

    public function hookDisplayAdminOrderTabShip()
    {
        return $this->displayadminordertab();
    }

    public function hookDisplayAdminOrderTabLink()
    {
        return $this->displayadminordertab();
    }

    public function hookDisplayAdminOrderContentShip()
    {
        return $this->displayadmintabcontent();
    }

    public function hookDisplayAdminOrderTabContent()
    {
        return $this->displayadmintabcontent();
    }

    public function displayadminordertab()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);

        if ($order->module == $this->name) {
            return $this->display(__FILE__, 'views/templates/hook/admin_order_tab_ship.tpl');
        }
    }

    public function displayadmintabcontent()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);
        if ($order->module == $this->name) {
            $sql = 'SELECT * FROM '._DB_PREFIX_.'kevin WHERE id_order = \''.pSQL($id_order).'\'';
            if ($row = Db::getInstance()->getRow($sql)) {
                $this->context->smarty->assign([
                    'payment_id' => $row['payment_id'],
                ]);

                return $this->display(__FILE__, 'views/templates/hook/admin_order_content_ship.tpl');
            }
        }
    }
}
