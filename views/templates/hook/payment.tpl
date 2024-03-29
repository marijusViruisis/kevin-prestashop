{*
* 2022 kevin.
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*  @author 2022 kevin. <help@kevin.eu>
*  @copyright kevin.
*  @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

<div class="row">
	<div class="col-xs-12">
		{foreach $banks as $bank}
			<p class="payment_module kevin_payment_button">
				<a class="kevin_choice" data-url="{$bank['action']|escape:'htmlall':'UTF-8'}" onClick="kevinProceedToPaymentUrl(this)" style="background-image:url('{$bank['logo']|escape:'htmlall':'UTF-8'}')">
					{$bank['title']}{if $bank['id'] neq 'card'}<br><span>{l s='Redirect to bank login' mod='kevin'}</span>{/if}
				</a>
			</p>
		{/foreach}
	</div>
</div>
