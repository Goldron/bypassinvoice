<?php
/**
 * Copyright since 2024 SILADAL SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (Apache-2.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/Apache-2.0
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL SAS
 * @copyright   Since 2024 SILADEL SAS
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License, Version 2.0
 *
 */

namespace Bypassinvoice;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ClassBypassInvoice extends \ObjectModel
{
    public static $definition = array(
        'table' => 'bypassinvoice',
        'primary' => 'id_bypassinvoice',
        'multilang' => false,
        'fields' => array(
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'id_societe' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
        ),
    );

    /**
     * get list
     */
    public static function getBypassList()
    {
        $sql = 'SELECT *
                FROM ' . _DB_PREFIX_ . 'bypassinvoice
                AND `id_shop` = ' . (int) \Context::getContext()->shop->id . '
                ';

        if (!$result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql)) {
            return null;
        }

        return $result;
    }

    /**
     * get Id Dolibarr societe
     * @param int $id customer
     * @return int societe id
     */
    public static function getBypass($id) : ?int
    {
        $sql = 'SELECT `id_societe`
                FROM ' . _DB_PREFIX_ . 'bypassinvoice
                WHERE `id_customer` = \'' . (int) $id . '\'
                AND id_shop = ' . \Context::getContext()->shop->id;

        if (!$result = \Db::getInstance()->getRow($sql)) {
            return null;
        }

        return (int) $result['id_societe'];
    }

    /**
     * is id societe
     * @param int societe id
     * @return bool
     */
    protected static function isBypass($id) : bool
    {
        $sql = 'SELECT `id_societe`
                FROM ' . _DB_PREFIX_ . 'bypassinvoice
                WHERE `id_customer` = \'' . (int) $id . '\'
                AND id_shop = ' . \Context::getContext()->shop->id;

        if (\Db::getInstance()->getRow($sql)) {
            return true;
        }

        return false;
    }

    /**
     * create bypass
     */
    public static function createBypass($id_customer, $id_societe)
    {
        if (ClassBypassInvoice::isBypass($id_customer)) {
            return ClassBypassInvoice::updateBypass($id_customer, $id_societe);
        }

        //\Tools::dieObject(ClassBypassInvoice::isBypass($id_customer));

        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'bypassinvoice (id_shop, id_customer, id_societe)
                VALUES
                (' . \Context::getContext()->shop->id . ',
                \'' . (int) $id_customer . '\',
                \'' . (int) $id_societe . '\')';

        return \Db::getInstance()->execute($sql);
    }

    /**
     * update bypass
     */
    protected static function updateBypass($id_customer, $id_societe)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'bypassinvoice
                SET `id_societe` = \'' . (int) $id_societe . '\'
                WHERE `id_customer` = \'' . (int) $id_customer . '\'
                AND id_shop = ' . \Context::getContext()->shop->id;

        return \Db::getInstance()->execute($sql);
    }
}
