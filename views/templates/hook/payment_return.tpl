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
{if $group == 'failed'}
    <p class="alert alert-danger">
        {l s='Order payment canceled' mod='kevin'}
    </p>
{else}
    <p class="alert alert-warning">
        {l s='We will start executing the order only after receiving the payment' mod='kevin'}
    </p>
{/if}