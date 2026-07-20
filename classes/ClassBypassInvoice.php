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

class ClassBypassInvoice extends \ObjectModel
{
    /**
     * id_shop is NOT listed here on purpose: it collides with the id_shop
     * property already declared protected on the core ObjectModel class.
     * EntityMapper (PrestaShop core) hydrates every field listed here via
     * $entity->{$key} = $value from outside the class hierarchy, which
     * fatals on a protected property. This class never uses ObjectModel's
     * own save()/add() (everything goes through raw SQL below), so leaving
     * id_shop out of the definition has no effect besides avoiding the crash.
     */
    public static $definition = array(
        'table' => 'bypassinvoice',
        'primary' => 'id_bypassinvoice',
        'multilang' => false,
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
            'id_societe' => array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt'),
        ),
    );

    /**
     * id_shop of a relation, fetched directly (see note above on why it
     * can't be read from a hydrated instance's property).
     *
     * @param int $id_bypassinvoice
     * @return int
     */
    public static function getShopOf(int $id_bypassinvoice): int
    {
        $sql = 'SELECT `id_shop`
                FROM ' . _DB_PREFIX_ . 'bypassinvoice
                WHERE `id_bypassinvoice` = ' . (int) $id_bypassinvoice;

        return (int) \Db::getInstance()->getValue($sql);
    }

    /**
     * get list
     */
    public static function getBypassList()
    {
        $sql = 'SELECT *
                FROM ' . _DB_PREFIX_ . 'bypassinvoice
                WHERE `id_shop` = ' . (int) \Context::getContext()->shop->id . '
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

    /**
     * update the Dolibarr societe id for a given relation (admin edit action)
     *
     * @param int $id_bypassinvoice
     * @param int $id_societe
     * @return bool
     */
    public static function updateSocieteById(int $id_bypassinvoice, int $id_societe): bool
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'bypassinvoice
                SET `id_societe` = ' . (int) $id_societe . '
                WHERE `id_bypassinvoice` = ' . (int) $id_bypassinvoice . '
                AND `id_shop` = ' . (int) \Context::getContext()->shop->id;

        return (bool) \Db::getInstance()->execute($sql);
    }

    /**
     * delete a relation (admin action)
     *
     * @param int $id_bypassinvoice
     * @return bool
     */
    public static function deleteById(int $id_bypassinvoice): bool
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'bypassinvoice
                WHERE `id_bypassinvoice` = ' . (int) $id_bypassinvoice . '
                AND `id_shop` = ' . (int) \Context::getContext()->shop->id;

        return (bool) \Db::getInstance()->execute($sql);
    }
}
