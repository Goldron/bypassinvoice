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
