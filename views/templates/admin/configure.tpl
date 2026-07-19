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


<div class="panel">
	<div class="row moduleconfig-header">
		<div style="margin-top: 16px" class="col-md-4">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.png" />
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/siladel.png" />
		</div>
		<div class="col-md-auto text-left">
			<h2>{l s='BypassInvoice' mod='bypassinvoice'} {$VERSION|escape:'html':'UTF-8'}</h2>
			<h4>{l s='Déléguer la gestion des factures sous Dolibarr.' mod='bypassinvoice'}</h4>
		</div>
	</div>
	<hr />
	<div class="moduleconfig-content">
		<div class="row">
			<div class="col-xs-12">
				<p class="text-center">
					<strong>
					</strong>
						<a href="https://addons.prestashop.com/contact-form.php?id_product=93635" target="_blank"
							title="Développeur Module Prestashop SILADEL">
							<i class="material-icons">mail</i>
							{l s='contact email' mod='bypassinvoice' }
						</a>
						
						| <a
							href="{$module_dir|escape:'html':'UTF-8'}doc/readme_{$LANG_ISO|escape:'html':'UTF-8'}.pdf">
							<i class="material-icons">book</i>
							{l s='Documentation' mod='bypassinvoice'}</a>
						{if !empty($LOGFILE)}
						| <a href="{$module_dir|escape:'html':'UTF-8'}log/{$LOGFILE|escape:'html':'UTF-8'}.dat">
						<i class="material-icons">grading</i>
						{l s='Log' mod='bypassinvoice'}</a>
						{/if}
					
				</p>

				<div class="alert alert-info" role="alert">
				<h4>{l s='Exclusive Mode: Management of invoices exclusively with Dolibarr.' mod='bypassinvoice'}</h4>
				<p><strong>{l s='Exclusive mode description.' mod='bypassinvoice'}</strong></p>
				<br>
				<ul class="list-styled">
				<li>{l s='Disable billing in Prestashop via the Invoices dropdown tab.' mod='bypassinvoice'}</li>
				<li>{l s='Return to the module settings to configure the (Billing Account Block) section.' mod='bypassinvoice'}</li>
				</ul>
				<br>
				<h4 >{l s='Duo Mode: Delegate invoice number management only.' mod='bypassinvoice'}</h4>
				<p><strong>{l s='Duo mode description.' mod='bypassinvoice'}</strong></p>
				<br>
				<ul class="list-styled">
				<li>{l s='Align Dolibarr and PrestaShop invoice numbers in the (Format Invoice Number) section.' mod='bypassinvoice'}</li>
				</ul>
				</div>
				
			</div>
		</div>
	</div>
</div>