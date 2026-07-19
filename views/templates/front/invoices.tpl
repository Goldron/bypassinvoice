{**
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
 *}

{if !empty($invoices)}
  {assign var="list" value=$invoices}
{/if}

{extends file='customer/page.tpl'}

{block name='page_header_container'}
  <header class="page-header">
    <h1>{$bypassinvoicetitle|escape:'html':'UTF-8'}</h1>
  </header>
{/block}

{block name='page_content_container'}
  <div id="bypassinvoice">
  <section id="content" class="page-customer">
    <div class="container">
      {if $active}
        {if !empty($list)}
          <table class="table table-striped table-bordered table-labeled hidden-sm-down">
            <thead class="thead-default">
              <tr>
                <th>{l s='Invoice reference' mod='bypassinvoice'}</th>
                <th>{l s='Order reference' mod='bypassinvoice'}</th>
                <th>{l s='Date' mod='bypassinvoice'}</th>
                <th>{l s='Total with tax' mod='bypassinvoice'}</th>
                <th class="hidden-md-down">{l s='Status' mod='bypassinvoice'}</th>
                <th>{l s='Download' mod='bypassinvoice'}</th>
              </tr>
            </thead>
            <tbody>

              {foreach $list as $invoice}
                <tr>
                  <th scope="row">{$invoice.ref|escape:'html':'UTF-8'}</th>
                  <td class="text-xs-left">{$invoice.ref_ext|escape:'html':'UTF-8'}</td>
                  <td>{$invoice.date_creation|escape:'html':'UTF-8'}</td>
                  <td class="text-xs-left">{$invoice.total_ttc|escape:'html':'UTF-8'}</td>

                  <td>
                    {if (int) $invoice.paye == 1}
                      <span class="label label-pill dark" style="background-color:#3470d8">
                        {l s='closed paid' mod='bypassinvoice'}
                      </span>
                    {/if}
                    {if $invoice.status == 1}
                      {if (int) $invoice.remaintopay < (int)$invoice.total_ttc}
                        <span class="label label-pill dark" style="background-color:#e3820d">
                          {l s='payment partially' mod='bypassinvoice'}
                        </span>
                      {else}
                        <span class="label label-pill dark" style="background-color:#34d847">
                          {l s='Validated not paid' mod='bypassinvoice'}
                        </span>
                      {/if}

                    {/if}
                    {if $invoice.status == 0}
                      <span class="label label-pill dark" style="background-color:#818181">
                        {l s='Draft' mod='bypassinvoice'}
                      </span>
                    {/if}
                    {if $invoice.status == 3}
                      <span class="label label-pill dark" style="background-color:#d72222c9">
                        {l s='Canceled' mod='bypassinvoice'}
                      </span>
                    {/if}
                  </td>
                  <td class="text-sm-center hidden-md-down">
                    {assign var="myUrl" value=$link->getModuleLink('bypassinvoice', 'PdfDisplay', ['reference' => $invoice.pdf])}
                    <a href="{$myUrl|escape:'html':'UTF-8'}"><i class="material-icons"></i></a>
                  </td>
                </tr>
              {{/foreach}}
            </tbody>
          </table>

          {* mobile *}
          <div class="invoices hidden-md-up">
            {foreach $list as $invoice}
              <div class="invoice">
                <div class="row">
                  <div class="col-xs-10">
                    <h3>{$invoice.ref|escape:'html':'UTF-8'}</h3>
                    <div class="reforder">{$invoice.ref_ext|escape:'html':'UTF-8'}</div>
                    <div class="date"> {$invoice.date_creation|escape:'html':'UTF-8'}</div>
                    <div class="total">{$invoice.total_ttc|escape:'html':'UTF-8'}</div>

                    <div class="status">
                      {if (int) $invoice.paye == 1}
                        <span class="label label-pill dark" style="background-color:#3470d8">
                          {l s='closed paid' mod='bypassinvoice'}
                        </span>
                      {/if}
                      {if $invoice.status == 1}
                        {if (int) $invoice.remaintopay < (int)$invoice.total_ttc}
                          <span class="label label-pill dark" style="background-color:#e3820d">
                            {l s='payment partially' mod='bypassinvoice'}
                          </span>
                        {else}
                          <span class="label label-pill dark" style="background-color:#34d847">
                            {l s='Validated not paid' mod='bypassinvoice'}
                          </span>
                        {/if}

                      {/if}
                      {if $invoice.status == 0}
                        <span class="label label-pill dark" style="background-color:#818181">
                          {l s='Draft' mod='bypassinvoice'}
                        </span>
                      {/if}
                      {if $invoice.status == 3}
                        <span class="label label-pill dark" style="background-color:#d72222c9">
                          {l s='Canceled' mod='bypassinvoice'}
                        </span>
                      {/if}
                    </div>
                  </div>
                  <div class="col-xs-2 text-xs-right">
                    {assign var="myUrl" value=$link->getModuleLink('bypassinvoice', 'PdfDisplay', ['reference' => $invoice.pdf])}
                    <a href="{$myUrl|escape:'html':'UTF-8'}"><i class="material-icons"></i></a>
                  </div>
                </div>
              </div>
              <hr>
            {/foreach}

          </div>

        {else}
          <p>{l s='Invoice not found' mod='bypassinvoice'}</p>
        {/if}
      {else}
        <p>{l s='Please wait, this page is under maintenance...' mod='bypassinvoice'}</p>
      {/if}

    </div>
  </section>
  </div>

{/block}

{block name='page_footer_container'}
  <footer class="page-footer">
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}" class="text-primary"><i
        class="material-icons">chevron_left</i>{l s='Return to your account' mod='bypassinvoice'}</a>
    <a href="{$urls.base_url|escape:'html':'UTF-8'}" class="text-primary"><i
        class="material-icons">home</i>{l s='Home' mod='bypassinvoice'}</a>
  </footer>
{/block}