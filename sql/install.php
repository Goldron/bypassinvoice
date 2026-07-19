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

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$idSoc = 1;

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bypassinvoice` (
    `id_bypassinvoice` int(11) NOT NULL AUTO_INCREMENT,
    `id_shop` int(11) NOT NULL DEFAULT \'1\',
	`id_customer` int(11) NOT NULL,
    `id_societe` int(11) NOT NULL DEFAULT ' . $idSoc . ',
    PRIMARY KEY  (`id_bypassinvoice`, `id_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
