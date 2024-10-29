<input type="hidden" name="apppago_button_amount" id="apppago_button_amount" value="<?php echo esc_attr($amount) ?>">
<input type="hidden" name="apppago_button_service" id="apppago_button_service" value="<?php echo esc_attr($serviceID) ?>">
<input type="hidden" name="apppago_button_merchantid" id="apppago_button_merchantid" value="<?php echo esc_attr($merchantID) ?>">
<input type="hidden" name="apppago_button_possiblerates" id="apppago_button_possiblerates" value="<?php echo esc_attr($possibleRates) ?>">
<input type="hidden" name="apppago_button_paymentid" id="apppago_button_paymentid" value="<?php echo esc_attr($paymentId) ?>">
<input type="hidden" name="apppago_button_hashpass" id="apppago_button_hashpass" value="<?php echo esc_attr($hashPass) ?>">
<input type="hidden" name="apppago_button_domain" id="apppago_button_domain" value="<?php echo esc_attr($domain) ?>">
<input type="hidden" name="apppago_button_status_update" id="apppago_button_status_update" value="<?php echo esc_url($statusUpdateCallbackUrl) ?>">
<?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
<div>
    <apppago-button label="Dilaziona" buttonType="button" buttonColor="primary" corners="round" id="apppago-button"></apppago-button>
</div>
<?php do_action('woocommerce_credit_card_form_end', $this->id); ?>