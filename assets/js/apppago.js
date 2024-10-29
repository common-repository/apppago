class ErrorHandler {

    constructor()
    {
        this.wrapper = document.querySelector('.woocommerce-notices-wrapper');
        this.messagesList = document.querySelector('ul.woocommerce-error');
    }

    appendPreparedErrorMessageElement(errorMessageElement)
    {
        if(this.messagesList === null) {
            this.prepareMessagesList();
        }

        this.messagesList.replaceWith(errorMessageElement);
    }

    message(text, persist = false)
    {
        if(! typeof String || text.length === 0){
            throw new Error('A new message text must be a non-empty string.');
        }

        if(this.messagesList === null){
            this.prepareMessagesList();
        }

        if (persist) {
            this.wrapper.classList.add('ppcp-persist');
        } else {
            this.wrapper.classList.remove('ppcp-persist');
        }

        let messageNode = this.prepareMessagesListItem(text);
        this.messagesList.appendChild(messageNode);

        jQuery.scroll_to_notices(jQuery('.woocommerce-notices-wrapper'))
    }

    prepareMessagesList()
    {
        if(this.messagesList === null){
            this.messagesList = document.createElement('ul');
            this.messagesList.setAttribute('class', 'woocommerce-error');
            this.messagesList.setAttribute('role', 'alert');
            this.wrapper.appendChild(this.messagesList);
        }
    }

    prepareMessagesListItem(message)
    {
        const li = document.createElement('li');
        li.innerHTML = message;

        return li;
    }

    clear()
    {
        if (this.messagesList === null) {
            return;
        }

        this.messagesList.innerHTML = '';
    }
}


jQuery(document).ready(function($) {
    function paymentCompleted(response) {
        if (response['status'] !== 'IN ATTIVAZIONE') {
            document.getElementById("place_order").click();
        }
    }

    async function buttonClicked() {
        if (checkRequiredInput()) {
            throw new Error();
        }


        let possRates = $('#apppago_button_possiblerates').val().split(',').map(str => {
            return Number(str);
        });
        const domain = $('#apppago_button_domain').val();
        const paymentId = $('#apppago_button_paymentid').val();
        return {
            hashPass: $('#apppago_button_hashpass').val(),
            domain,
            paymentId,
            idMerchant: $('#apppago_button_merchantid').val(),
            firstName: $('#billing_first_name').val()?$('#billing_first_name').val() :'',
            lastName: $('#billing_last_name').val()?$('#billing_last_name').val() :'',
            phoneNumber: $('#billing_phone').val()?$('#billing_phone').val() :'',
            eMailAddress: $('#billing_email').val()?$('#billing_email').val() :'' ,
            fiscalCode: '',
            description: `#${paymentId} - ${domain}`,
            serviceSmallpay: $('#apppago_button_service').val(),
            totalAmount: $('#apppago_button_amount').val(),
            possibleRates: possRates,
            statusUpdateCallbackUrl: $('#apppago_button_status_update').val()
        }
    }
    APPpagoButtonConfiguration = {
        requestConfiguration: buttonClicked,
        onComplete: paymentCompleted
    }
});

function checkRequiredInput() {
    const errorHandler = new ErrorHandler();
    errorHandler.clear();

    const requiredFields = jQuery('form.woocommerce-checkout .validate-required:visible :input');
    requiredFields.each((i, input) => {
        jQuery(input).trigger('validate');
    });
    const invalidFields = Array.from(jQuery('form.woocommerce-checkout .validate-required.woocommerce-invalid:visible'));    
    if (invalidFields.length) {
        const messages = invalidFields.map(el => {
            const name = el.querySelector('[name]')?.getAttribute('name');
            
            let label = el.querySelector('label').textContent
                .replaceAll('*', '')
                .trim();
            return "%s è un campo richiesto."
                .replace('%s', `<strong>${label}</strong>`)
        }).filter(s => s.length > 2);

        if (messages.length) {1
            messages.forEach((s, i) => errorHandler.message(s));
        }

        return messages.length > 0;
    }
}

function checkPaymentMethod() {
    const setVisible = (selectorOrElement, show, important = false) => {
        const element = document.querySelector(selectorOrElement);
        if (!element) {
            return;
        }
        const currentValue = element.style.getPropertyValue('display');
        if (!show) {
            if (currentValue === 'none') {
                return;
            }

            console.log(element, "Display none");
            element.style.setProperty('display', 'none', important ? 'important' : '');
            
        } else {
            if (currentValue === 'none') {
                element.style.removeProperty('display');
            } // still not visible (if something else added display: none in CSS)
            const isVisible = !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
            if (!isVisible) {
                element.style.setProperty('display', 'block');
            }
            console.log("Remove Display none");
        }
    };

    jQuery(document).ready(function($) {
        if($('#payment_method_apppago').is(':checked')){
            setVisible('#place_order', false);
        }
        $('#payment').on('change', function (){
            setVisible('#place_order', !$('#payment_method_apppago').is(':checked'));
            clearInterval(intervalId);
        })
    });
}

var intervalId = window.setInterval(checkPaymentMethod, 1000);