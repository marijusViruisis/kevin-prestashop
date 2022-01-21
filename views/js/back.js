$(document).ready(function () {
    var html = '<p class="checkbox">';
    html += '<label for="refundwithkevin">';
    html += '<input type="checkbox" id="refundwithkevin" name="refundwithkevin">';
    html += '<strong>' + kevin_text + '</strong>';
    html += '</label>';
    html += '</p>';

    $('button[name=partialRefund]').closest('.partial_refund_fields').prepend(html);
	if(_PS_VERSION_ > "1.7.6"){
		$(document).ready(function () {
			var html = `<div class="cancel-product-element form-group restock-products" style="display: block;">
				<div class="checkbox">
					<div class="md-checkbox md-checkbox-inline">
						<label for="refundwithkevin">
						<input type="checkbox" id="refundwithkevin" name="refundwithkevin" class="cancel_product_restock" material_design="material_design" value="1">
							<i class="md-checkbox-control"></i>${kevin_text}</label>
					</div>
				</div>
			</div>`;
		

			$('.refund-checkboxes-container').prepend(html);
		});

		$(document).ready(function () {
				var html = `<div className="cancel-product-element form-group restock-products" style="display: none;">
					<div className="checkbox">
						<div className="md-checkbox md-checkbox-inline">
							<label for="refundwithkevin">
							<input type="hidden" id="partialRefund" name="partialRefund" class="cancel_product_restock" material_design="material_design" value="1">
								<i className="md-checkbox-control"></i>hidden_input</label>
						</div>
					</div>
				</div>`;
		
				html += `<div className="cancel-product-element form-group restock-products" style="display: none;">
				<div className="checkbox">
					<div className="md-checkbox md-checkbox-inline">
						<label for="refundwithkevin">
						<input type="hidden" id="id_order" name="id_order" class="cancel_product_restock" material_design="material_design" value="${id_order}">
							<i className="md-checkbox-control"></i>hidden_input</label>
					</div>
				</div>
			</div>`;
			$('.refund-checkboxes-container').prepend(html);
		});
	}
});
