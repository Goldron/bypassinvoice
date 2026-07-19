{**
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
 *}

<div class="panel">
	<div class="panel-heading">
		<i class="icon-link"></i> {l s='Modifier la relation' mod='bypassinvoice'}
	</div>

	<form id="bypassinvoice_relation_form" action="{$save_url|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
		<input type="hidden" name="id_bypassinvoice" value="{$id_bypassinvoice|intval}">
		<input type="hidden" name="submitBypassinvoiceRelation" value="1">

		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Client PrestaShop' mod='bypassinvoice'}</label>
			<div class="col-lg-9">
				<p class="form-control-static">
					{$customer_fullname|escape:'html':'UTF-8'} &lt;{$customer_email|escape:'html':'UTF-8'}&gt;
				</p>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Société Dolibarr actuelle' mod='bypassinvoice'}</label>
			<div class="col-lg-9">
				<p class="form-control-static">{$current_societe_label|escape:'html':'UTF-8'}</p>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3" for="bypassinvoice_societe_search">{l s='Nouvelle société Dolibarr' mod='bypassinvoice'}</label>
			<div class="col-lg-9" style="position:relative;">
				<input type="hidden" id="bypassinvoice_id_societe" name="id_societe" value="{$id_societe|intval}">
				<input type="text" id="bypassinvoice_societe_search" class="form-control" autocomplete="off"
					placeholder="{l s='Rechercher une société (2 caractères min.)' mod='bypassinvoice'}">
				<ul id="bypassinvoice_societe_results" class="list-group"
					style="position:absolute;left:0;right:0;z-index:1000;max-height:220px;overflow-y:auto;display:none;margin-top:2px;box-shadow:0 2px 6px rgba(0,0,0,.15);"></ul>
			</div>
		</div>

		<div class="panel-footer">
			<a href="{$back_url|escape:'html':'UTF-8'}" class="btn btn-default pull-left">
				<i class="process-icon-cancel"></i> {l s='Annuler' mod='bypassinvoice'}
			</a>
			<button type="submit" class="btn btn-primary pull-right">
				<i class="process-icon-save"></i> {l s='Enregistrer' mod='bypassinvoice'}
			</button>
		</div>
	</form>
</div>

<script type="text/javascript">
(function () {
	'use strict';

	var input = document.getElementById('bypassinvoice_societe_search');
	var hidden = document.getElementById('bypassinvoice_id_societe');
	var results = document.getElementById('bypassinvoice_societe_results');
	var searchUrl = {$ajax_search_url|json_encode nofilter};
	var timer = null;
	var currentRequest = null;

	function clearResults() {
		results.innerHTML = '';
		results.style.display = 'none';
	}

	input.addEventListener('input', function () {
		var term = input.value;
		hidden.value = '';
		clearTimeout(timer);

		if (term.length < 2) {
			clearResults();
			return;
		}

		timer = setTimeout(function () {
			if (currentRequest) {
				currentRequest.abort();
			}
			currentRequest = new XMLHttpRequest();
			currentRequest.open('GET', searchUrl + '&q=' + encodeURIComponent(term), true);
			currentRequest.onload = function () {
				if (currentRequest.status !== 200) {
					return;
				}
				var data;
				try {
					data = JSON.parse(currentRequest.responseText);
				} catch (e) {
					return;
				}

				clearResults();

				if (!data.results || !data.results.length) {
					return;
				}

				data.results.forEach(function (item) {
					var li = document.createElement('li');
					li.className = 'list-group-item';
					li.style.cursor = 'pointer';
					li.style.background = '#fff';
					li.textContent = item.text;
					li.addEventListener('click', function () {
						hidden.value = item.id;
						input.value = item.text;
						clearResults();
					});
					results.appendChild(li);
				});

				results.style.display = 'block';
			};
			currentRequest.send();
		}, 300);
	});

	document.addEventListener('click', function (e) {
		if (e.target !== input) {
			clearResults();
		}
	});

	document.getElementById('bypassinvoice_relation_form').addEventListener('submit', function (e) {
		if (!hidden.value) {
			e.preventDefault();
			alert({l s='Veuillez sélectionner une société dans la liste.' mod='bypassinvoice' js=1});
		}
	});
})();
</script>
