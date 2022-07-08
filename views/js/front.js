function kevinProceedToPaymentUrl(element) {
    const dataUrl = element.getAttribute('data-url');
    if (dataUrl) {
        const kevinChoices = jQuery('.payment_module a.kevin_choice');
        kevinChoices.removeAttr('data-url');
        kevinChoices.addClass('disabled');
        jQuery(element).addClass('loading');
        window.location.href = dataUrl;
    }
}