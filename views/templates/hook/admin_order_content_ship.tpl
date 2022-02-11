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

<div class="tab-pane active" id="kevin">
    <h4 class="visible-print">{l s='kevin. payment info' mod='kevin'}</h4>
    <br/>
    <p>{l s='Order payment ID ' mod='kevin'}: <strong>{$payment_id}</strong></p>
</div>
<hr/>
<script type="text/javascript">
    $(document).ready(function () {
        $('#shipping').removeClass('active');
    });
</script>