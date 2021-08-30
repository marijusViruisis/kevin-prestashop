{*
* 2020 kevin.
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
*  @author 2020 kevin. <info@getkevin.eu>
*  @copyright kevin.
*  @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

<div class="row">
	<div class="col-xs-12">
		{foreach $banks as $bank}
			<p class="payment_module kevin_payment_button">
				<a class="kevin_choice" href="{$bank['action']|escape:'htmlall':'UTF-8'}" style="background-image:url('{$bank['logo']|escape:'htmlall':'UTF-8'}')">
					{$bank['title']}<br><span>{l s='Redirect to bank login' mod='kevin'}</span>
				</a>
			</p>
		{/foreach}
	</div>
</div>
