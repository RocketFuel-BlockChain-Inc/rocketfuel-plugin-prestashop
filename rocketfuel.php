<?php
/**
 * RocketFuel - A Payment Module for PrestaShop 1.7
 *
 * This file is the declaration of the module.
 *
 * v 0.1.1
 */

require_once(dirname(__FILE__) . '/classes/RocketfuelService.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

define('DEBUG', true);

class RocketFuel extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public $address;

    const IFRAMES = [
        'https://iframe-stage1.rocketdemo.net',
        'https://iframe.rocketdemo.net',
        'https://iframe-stage2.rocketdemo.net',
        'https://iframe-test.rocketdemo.net',
        'https://iframe.rocketfuelblockchain.com'
    ];

    /**
     * @var string
     */
    protected $merchant_id;

    /**
     * @var string
     */
    protected $rf_iframe;
    /**
     * @var string
     */
    protected $rf_signature;

    /**
     * RocketFuel constructor.
     *
     * Set the information about this module
     */
    public function __construct()
    {
        $this->name = 'rocketfuel';
        $this->tab = 'payments_gateways';
        $this->version = '0.1.2';
        $this->author = 'RocketFuel Inc.';
        $this->controllers = array('payment', 'validation');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->displayName = 'RocketFuel';
        $this->description = 'The RocketFuel blockchain based one-click “BUY-NOW” check-out solution is a game-changing technology that promotes remarkable high conversion efficiencies and further stimulates highly impulsive buying in e-commerce scenarios.';
        $this->confirmUninstall = 'Are you sure you want to uninstall this module?';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

        $this->merchant_id = Configuration::get('ROCKETFUEL_MERCHANT_ID');
        $this->rf_iframe = Configuration::get('ROCKETFUEL_IFRAME');
        $this->rf_signature = Configuration::get('ROCKETFUEL_MERCHANT_PUBLIC_KEY');

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
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn');
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
     * form {
     *  merchant_id
     *  address_for_rocketfuel_callback
     * }
     * @return string
     */
    public function getContent()
    {
        $output = null;

        if (Tools::isSubmit('submit' . $this->name)) {
            $merchantID = strval(Tools::getValue('ROCKETFUEL_MERCHANT_ID'));
            $rf_iframe = strval(Tools::getValue('ROCKETFUEL_IFRAME'));
            $rf_signature=strval(Tools::getValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY'));
            if ($this->validateForm($merchantID, $rf_iframe)) {
                Configuration::updateValue('ROCKETFUEL_MERCHANT_ID', $merchantID);
                Configuration::updateValue('ROCKETFUEL_IFRAME', $rf_iframe);
                Configuration::updateValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY', $rf_signature);

                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            }
        }

        return $output . $this->displayForm();
    }

    /**
     * validate input form values
     *
     * @param $merchantID
     * @param $rf_iframe
     * @return bool
     */
    protected function validateForm($merchantID, $rf_iframe)
    {
        return !(
            (!$merchantID || empty($merchantID) || !Validate::isGenericName($merchantID))
            ||
            (!$rf_iframe || empty($rf_iframe) || !Validate::isGenericName($rf_iframe))
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

        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'description' => 'Callback URL for RocketFuel is <b>' . $this->getCallbackUrl() . '</b>',
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
                    'label' => $this->l('RocketFuel iframe URL'),
                    'name' => 'ROCKETFUEL_IFRAME',
                    'options' => [
                        'query' => $this->getSelectIframeValues(),
                        'id'    => 'name',
                        'name'  => 'name'
                    ],
                    'required' => true
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('User signature'),
                    'name' => 'ROCKETFUEL_MERCHANT_PUBLIC_KEY',
                    'size' => 20,
                    'required' => true
                ]
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
        $helper->fields_value['ROCKETFUEL_IFRAME'] =
            Tools::getValue('ROCKETFUEL_IFRAME', Configuration::get('ROCKETFUEL_IFRAME'));
        $helper->fields_value['ROCKETFUEL_MERCHANT_PUBLIC_KEY'] =
            Tools::getValue('ROCKETFUEL_MERCHANT_PUBLIC_KEY', Configuration::get('ROCKETFUEL_MERCHANT_PUBLIC_KEY'));

        return $helper->generateForm($fieldsForm);
    }

    protected function getSelectIframeValues()
    {
        $out = [];
        foreach (self::IFRAMES as $key => $value){
            $out[] = ['id' => $key, 'name' => $value];
        }
        return $out;
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

        /**
         * Create a PaymentOption object containing the necessary data
         * to display this module in the checkout
         */
        $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;
        $newOption->setModuleName($this->displayName)
            ->setCallToActionText($this->displayName)
            ->setAction($formAction)
            ->setForm($paymentForm);

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

        return $this->fetch(
            'module:rocketfuel/views/templates/hook/payment_return.tpl',
            [
                'iframe_url' => Configuration::get('ROCKETFUEL_IFRAME') ?: '',
                'order_id' => $orderID,
                'payload_url' => '/modules/rocketfuel/order.php?order_id=' . $orderID,
                /**
                 * for view payload in testing
                 */
                'debug' => DEBUG,
                'payload' => json_encode($this->getPayload($orderID)),
            ]
        );
    }

    /**
     * Get url for rocketfuel callback
     *
     * @return string
     */
    protected function getCallbackUrl()
    {
        return 'https://' . Configuration::get('PS_SHOP_DOMAIN') . '/modules/rocketfuel/callback.php';
    }

    /**
     * Get template variables
     *
     * @return array
     */
    protected function getPayload($orderID)
    {
        $callback = new RocketfuelService();
        $payload = $callback->getOrderPayload(new Order($orderID));

        return $payload;
    }
}
