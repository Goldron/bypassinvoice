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

class DoliLogger extends \FileLogger
{
    protected $logs_enabled = false;

    public function __construct($debug, $level = self::INFO)
    {
        $this->logs_enabled = $debug;

        parent::__construct($level);
    }

    /**
     * log message only if logs are enabled
     * Generation du fichier log.
     *
     * @param string $event Message d\'Evenement
     */
    public function log($event, $level = self::DEBUG): void
    {
        if (!$this->logs_enabled) {
            return;
        }

        parent::log($event, $level);
    }
}
