<?php
/**
 * Copyright since 2024 SILADEL.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (GPL-3.0-or-later)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL
 * @copyright   Since 2024 SILADEL
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, Version 3
 *
 */

namespace Bypassinvoice;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Bypassinvoice\DoliLogger;

class DoliTools
{
    private static $logger;

    /**
     * @return object
     */
    public static function getLogger()
    {
        if (!self::$logger) {
            self::$logger = new DoliLogger(\Configuration::get('BYPASSINVOICE_LOGS'));

            $logs_dir = _PS_MODULE_DIR_ . '/bypassinvoice/log/';
            if (!file_exists($logs_dir)) {
                $logs_dir = _PS_MODULE_DIR_ . '/bypassinvoice';
            }

            self::$logger->setFilename($logs_dir . date('Ym') . '_bypassinvoice.dat');
        }

        return self::$logger;
    }

    /**
     * is log
     * @return bool
     */
    public static function isLogger() : bool
    {
        $logs_dir = _PS_MODULE_DIR_ . '/bypassinvoice/log/';
        $file = $logs_dir . date('Ym') . '_bypassinvoice.dat';

        if (file_exists($file)) {
            return true;
        }

        return false;
    }

    /**
     * print log.
     *
     * @param string $msg  message
     * @param string $type (info|warning|error|notice)
     */
    public static function printLog(string $msg, $type = 'info'): void
    {
        if (!empty(\Configuration::get('BYPASSINVOICE_LOGS'))) {
            switch ($type) {
                case 'warning':
                    self::getLogger()->logWarning($msg);
                    break;
                case 'error':
                    self::getLogger()->logError($msg);
                    break;
                case 'notice':
                    self::getLogger()->logNotice($msg);
                    break;
                default:
                    self::getLogger()->logInfo($msg);
            }
        }
    }

    /**
     * deserialize an array.
     *
     * @param string $name config value ex: Configuration::get('DOLISYNCHRO_CONF')
     *
     * @return array
     */
    public static function getArrayConfig($name): ?array
    {
        $value = @json_decode(\Configuration::get($name));

        if (!is_array($value)) {
            return null;
        }

        return (array) $value;
    }

    /**
     * Return the category number if it exists.
     *
     * @param int   $cible
     * @param array $tab
     *
     */
    public static function inArray(int $cible, array $tab): int
    {
        if (in_array($cible, (array) $tab)) {
            return $cible;
        }

        return 0;
    }

    /**
     * Format a character string.
     *
     * @param string $value string to format
     */
    public static function capwords(string $value): string
    {
        $str = \Tools::strtolower($value);

        return \Tools::ucfirst($str);
    }


    /**
     * convert all data from the input array to the specified JSON format dolibarr
     */
    public static function convertArrayToJSON($array)
    {
        // Initialize the array to hold the converted structure
        $formattedArray = [];

        // Iterate over the input array and convert each value into an array
        foreach ($array as $key => $value) {
            $formattedArray[$key] = [$value]; // Make each value an array
        }

        // Convert the associative array to JSON and return
        return json_encode($formattedArray);
    }
}
