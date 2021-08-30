{*
* 2017 4webs
*
* DEVELOPED By 4webs Prestashop Platinum Partner
*
* @author    4webs
* @copyright 4webs 2017
* @version 4.0.11
* @category payment_gateways
* @license 4webs
*}
<div class="tab-pane active" id="kevin">
    <h4 class="visible-print">{l s='Kevin payment info' mod='kevin'}</h4>
    <br/>
    <p>{l s='Order status' mod='kevin'}: <strong>{$status}</strong></p>
    <p>{l s='Order status group' mod='kevin'}: <strong>{$group}</strong></p>
    <p>{l s='Order payment ID ' mod='kevin'}: <strong>{$payment_id}</strong></p>
</div>
<hr/>
<script type="text/javascript">
    $(document).ready(function () {
        $('#shipping').removeClass('active');
    });
</script>