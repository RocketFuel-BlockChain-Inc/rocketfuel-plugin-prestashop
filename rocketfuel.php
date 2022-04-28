<?php

/**
 * Rocketfuel Payment Gateway for Prestashop - A Simple Rocketfuel Payment Module for PrestaShop
 *
 *
 *
 * @author Udor Blessing
 *
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
require_once(dirname(__FILE__) . '/classes/Callback.php');

class Rocketfuel extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();

    public $address;

    const ENVIRONMENT = [
        'prod' => 'Production',
        'dev' => 'Development',
        'stage2' => 'QA',
        'preprod' => 'Pre-Production',

    ];
    /**
     * PrestaPay constructor.
     *
     * Set the information about this module
     */
    public function __construct()
    {
        $this->name                   = 'rocketfuel';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0.0';
        $this->author                 = 'Rocketfuel Team';
        $this->controllers            = array('payment', 'validation');
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';
        $this->bootstrap              = true;
        $this->displayName            = 'Rocketfuel';
        $this->description            = 'A Simple Payment module for Prestashop.';
        $this->confirmUninstall       = 'Are you sure you want to uninstall this module?';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
        $this->module_key = 'cd2ac6c3b2a488dfed10c5aca3092cec';
        parent::__construct();
    }
    /**
     * Install this module and register the following Hooks:
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions');
        //&& $this->registerHook('paymentReturn');
    }
    /**
     * Uninstall this module and remove it from all hooks
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }
    /**
     * Returns a string containing the HTML necessary to
     * generate a configuration screen on the admin
     *
     * @return string
     */
    public function getContent()
    {


        if (Tools::isSubmit('submit' . $this->name)) {
            $merchantID = strval(Tools::getValue('ROCKETFUEL_MERCHANT_ID'));

            $rfEnv = strval(Tools::getValue('ROCKETFUEL_ENVIRONMENT'));

            $merchantEmail = strval(Tools::getValue('ROCKETFUEL_MERCHANT_EMAIL'));

            $merchantPassword = strval(Tools::getValue('ROCKETFUEL_MERCHANT_PASSWORD'));

            $merchantPublicKey = strval(Tools::getValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY'));

            if ($this->validateForm($merchantID, $rfEnv)) { // Update teh form values

                Configuration::updateValue('ROCKETFUEL_MERCHANT_ID', $merchantID);

                Configuration::updateValue('ROCKETFUEL_ENVIRONMENT', $rfEnv);

                Configuration::updateValue('ROCKETFUEL_MERCHANT_EMAIL', $merchantEmail);

                if ($merchantPassword != '') {
                    Configuration::updateValue('ROCKETFUEL_MERCHANT_PASSWORD', $merchantPassword);
                }

                Configuration::updateValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY', $merchantPublicKey);

                $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $this->_html .= $this->displayError($this->l('Invalid Configuration value'));
            }
        }

        return $this->_html . $this->displayForm();
    }

    /**
     * Display this module as a payment option during the checkout
     *
     * @param array $params
     * @return array|void
     */
    public function hookPaymentOptions($params)
    {
        /*
         * Verify if this module is active
         */
        if (!$this->active) {
            return;
        }

        /**
         * Form action URL. The form data will be sent to the
         * validation controller when the user finishes
         * the order process.
         */
        $formAction = $this->context->link->getModuleLink($this->name, 'validation', array(), true);

        /**
         * Assign the url form action to the template var $action
         */
        $this->smarty->assign(['action' => $formAction]);

        /**
         *  Load form template to be displayed in the checkout step
         */
        $paymentForm = $this->fetch('module:rocketfuel/views/templates/hook/payment_options.tpl');
        $orderID = $params['cart']->id;
        //var_dump($params['objOrder']);

        /**
         * Load in the iframe to be displayed on click of the order button
         */
        $paymentIFrame = $this->fetch(
            'module:rocketfuel/views/templates/hook/payment_return.tpl',
            [
                'iframe_url' => Configuration::get('ROCKETFUEL_IFRAME') ?: '',
                'order_id' => $orderID,
                'payload_url' => Context::getContext()->shop->getBaseURL(true).'modules/rocketfuel/order.php',
                /**
                 * for view payload in testing
                 */
                'debug' => true,
                'cart' => json_encode($params['cart']),
                'customer' => json_encode($this->getPayload($params['cart']->id_customer)),
            ]
        );

        /**
         * Create a PaymentOption object containing the necessary data
         * to display this module in the checkout
         */
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->displayName)
            ->setCallToActionText($this->displayName)
            ->setAction($formAction)
            ->setForm($paymentForm)
            ->setAdditionalInformation($paymentIFrame);

        $payment_options = array(
            $newOption
        );

        return $payment_options;
    }
    /**
     * Display a message in the paymentReturn hook
     *
     * @param array $params
     * @return string
     */
    public function hookPaymentReturn($params)
    {
        /**
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }
        $orderID = $params['order']->id;

        $payload =  array(
            'env' => Configuration::get('ROCKETFUEL_ENVIRONMENT') ?: '',
            'order_id' => $orderID,
            'payload_url' => '/modules/rocketfuel/order.php?order_id=' . $orderID,
            /**
             * for view payload in testing
             */
            'debug' => true,
            'payload' => json_encode($this->getPayload($orderID)),
        );


        return $this->fetch(
            'module:rocketfuel/views/templates/hook/payment_return.tpl',
            $payload
        );
    }
    /**
     * Display form for configure on the admin
     *
     * @return mixed
     */
    public function displayForm()
    {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');


        // 'description' => 'Callback URL for RocketFuel is <b>' . $this->getCallbackUrl() . '</b>',


        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'description' => 'Pay with Rocketfuel',
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('RocketFuel Merchant ID'),
                    'name' => 'ROCKETFUEL_MERCHANT_ID',
                    'size' => 20,
                    'required' => true
                ],
                [
                    'type'    => 'select',
                    'label' => $this->l('RocketFuel Environment'),
                    'name' => 'ROCKETFUEL_ENVIRONMENT',
                    'options' => [
                        'query' => $this->getSelectEnvirontmentValues(),
                        'id'    => 'id',
                        'name'  => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type'    => 'text',
                    'label' => $this->l('RocketFuel Email'),
                    'name' => 'ROCKETFUEL_MERCHANT_EMAIL',
                    'required' => true
                ],
                [
                    'type'    => 'password',
                    'label' => $this->l('RocketFuel Password'),
                    'name' => 'ROCKETFUEL_MERCHANT_PASSWORD',
                    'required' => true
                ],
                [
                    'type'    => 'textarea',
                    'label' => $this->l('RocketFuel Merchant Public Key'),
                    'name' => 'ROCKETFUEL_MERCHANT_PUBLIC_KEY',
                    'size' => 40,

                    'required' => true
                ],
                [
                    'type'    => 'textarea',
                    'label' => $this->l('RocketFuel Callback URL'),
                    'name' => 'ROCKETFUEL_CALLBACK_URL',
                    'size' => 40,
                    'readonly' => true
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Load current value
        $helper->fields_value['ROCKETFUEL_MERCHANT_ID'] =
            Tools::getValue('ROCKETFUEL_MERCHANT_ID', Configuration::get('ROCKETFUEL_MERCHANT_ID'));

        $helper->fields_value['ROCKETFUEL_ENVIRONMENT'] =
            Tools::getValue('ROCKETFUEL_ENVIRONMENT', Configuration::get('ROCKETFUEL_ENVIRONMENT'));

        $helper->fields_value['ROCKETFUEL_MERCHANT_EMAIL'] =
            Tools::getValue('ROCKETFUEL_MERCHANT_EMAIL', Configuration::get('ROCKETFUEL_MERCHANT_EMAIL'));

        $helper->fields_value['ROCKETFUEL_MERCHANT_PUBLIC_KEY'] =
            Tools::getValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY', Configuration::get('ROCKETFUEL_MERCHANT_PUBLIC_KEY'));

        $helper->fields_value['ROCKETFUEL_MERCHANT_PASSWORD'] =
            Tools::getValue('ROCKETFUEL_MERCHANT_PASSWORD', Configuration::get('ROCKETFUEL_MERCHANT_PASSWORD'));


        $helper->fields_value['ROCKETFUEL_CALLBACK_URL'] =
            $this->getCallbackUrl();

        return $helper->generateForm($fieldsForm);
    }
    protected function getSelectEnvirontmentValues()
    {
        $out = [];
        foreach (self::ENVIRONMENT as $key => $value) {
            $out[] = ['id' => $key, 'name' => $value];
        }
        return $out;
    }
    /**
     * validate input form values
     *
     * @param $merchantID
     * @param $rfEnv
     * @return bool
     */
    protected function validateForm($merchantID, $rfEnv)
    {
        return !(
            (!$merchantID || empty($merchantID) || !Validate::isGenericName($merchantID))
            ||
            (!$rfEnv || empty($rfEnv) || !Validate::isGenericName($rfEnv))
        );
    }

    /**
     * Get url for rocketfuel callback
     *
     * @return string
     */
    protected function getCallbackUrl()
    {
        return 'https://' . Configuration::get('PS_SHOP_DOMAIN') . '/modules/rocketfuel/api/callback.php';
    }

    /**
     * Get payload
     * @param int $orderID
     *
     * @return Customer
     */
    protected function getPayload($orderID)
    {
        return new Customer($orderID);
    }
}
