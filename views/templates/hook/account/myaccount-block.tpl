{**
 * Copyright since 2024 SILADEL.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to (Apache-2.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/Apache-2.0
 *
 * @author      David IGREJA. <david@siladel.fr> SILADEL
 * @copyright   Since 2024 SILADEL
 * @license     https://opensource.org/licenses/Apache-2.0 Apache License, Version 2.0
 *
 *}

 {if $customer.is_logged}
    <li>
      <a href="{$url|escape:'html':'UTF-8'}" title="{$bypassinvoicetilepage|escape:'html':'UTF-8'}" rel="nofollow">
        {$bypassinvoice|escape:'html':'UTF-8'}
      </a>
    </li>
  {/if}