$(document).ready(function () {
    var html = '<p class="checkbox">';
    html += '<label for="refundwithkevin">';
    html += '<input type="checkbox" id="refundwithkevin" name="refundwithkevin">';
    html += '<strong>'+kevin_text+'</strong>';
    html += '</label>';
    html += '</p>';

    $('button[name=partialRefund]').closest('.partial_refund_fields').prepend(html);
});