<?php
Namespace RocketFuel\Classes;
/**
 * Plugin class
 *
 * @category  Rocketfuel
 * @package   Rocketfuel
 * @author    Rocketfuel 
 */

class Plugin
{
    private static $name = 'rocketfuel';
    private static $version= '2.1.1';
    private static $module_key = 'cd2ac6c3b2a488dfed10c5aca3092cec';
    private static $tab = 'payments_gateways';
    private static $currencies_mode = 'checkbox';
    private static $currencies = true;
    private static $author = 'Rocketfuel Team';
    private static $controllers = array('payment', 'validation');
    private static $bootstrap = true;
    private static $displayName = 'Rocketfuel';
    private static $description = 'Rocketfuel Payment Gateway for PrestaShop';
    private static $confirmUninstall = 'Are you sure you want to uninstall this module?';
    private static $temp_order_delimiter = '__rkfl_temp_order__';
    private static $ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);

    public static function getPluginInfo()
    {
        return [
            'name' => self::$name,
            'version' => self::$version,
            'description' => self::$description,
            'author' => self::$author,
            'module_key' => self::$module_key,
            'tab' => self::$tab,
            'currencies_mode' => self::$currencies_mode,
            'currencies' => self::$currencies,
            'controllers' => self::$controllers,
            'bootstrap' => self::$bootstrap,
            'displayName' => self::$displayName,
            'confirmUninstall' => self::$confirmUninstall,
            'ps_versions_compliancy' => self::$ps_versions_compliancy,
            'temp_order_delimiter' => self::$temp_order_delimiter,
        ];
    }
}
