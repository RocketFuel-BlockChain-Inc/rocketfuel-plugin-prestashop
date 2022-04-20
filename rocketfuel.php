<?php

/**
 * Rocketfuel Payment Gateway for Prestashop - A Sample Payment Module for PrestaShop 1.7
 *
 * 
 *
 * @author Udor Blessing
 * 
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Rocketfuel extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();

    public $address;
    /**
     * PrestaPay constructor.
     *
     * Set the information about this module
     */
    public function __construct()
    {
        $this->name                   = 'rocketfuel';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0';
        $this->author                 = 'Rocketfuel Team';
        $this->controllers            = array('payment', 'validation');
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';
        $this->bootstrap              = true;
        $this->displayName            = 'Rocketfuel';
        $this->description            = 'Sample Payment module developed for learning purposes.';
        $this->confirmUninstall       = 'Are you sure you want to uninstall this module?';
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

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
     * @return string
     */
    public function getContent()
    {
 

        if (Tools::isSubmit('submit' . $this->name)) {
            $merchantID = strval(Tools::getValue('ROCKETFUEL_MERCHANT_ID'));
            $rf_iframe = strval(Tools::getValue('ROCKETFUEL_IFRAME'));

            if ($this->validateForm($merchantID, $rf_iframe)) {
                Configuration::updateValue('ROCKETFUEL_MERCHANT_ID', $merchantID);
                Configuration::updateValue('ROCKETFUEL_IFRAME', $rf_iframe);

                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError($this->l('Invalid Configuration value'));
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
        // $orderID = $params['order']->id;
// $payload =             [
//                 'iframe_url' => Configuration::get('ROCKETFUEL_IFRAME') ?: '',
//                 'order_id' => $orderID,
//                 'payload_url' => '/modules/rocketfuel/order.php?order_id=' . $orderID,
//                 /**
//                  * for view payload in testing
//                  */
//                 'debug' => DEBUG,
//                 'payload' => json_encode($this->getPayload($orderID)),
//             ]
   

        return $this->fetch(
            'module:rocketfuel/views/templates/hook/payment_return.tpl'
        );
    }
}
