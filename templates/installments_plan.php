<?php
if ($installments > 1) {
    $date = new DateTime(date("Y-m-d"));
    ?>

    <p class="apppago-p-size apppago-p-confirm"><?php echo esc_html(__('your installment plan will be:', 'apppago')) ?></p>
    <table class="shop_table woocommerce-checkout-review-order-table apppago-table" >
        <tr class="cart_item">
            <th class="product-name apppago-installment" ><?php echo esc_html(__('Installment n°', 'apppago')) ?></th>
            <th class="product-total apppago-amount" ><?php echo esc_html(__('Installment price', 'apppago')) ?></th>
            <th class="product-name apppago-date" ><?php echo esc_html(__('Installment of', 'apppago')) ?></th>
        </tr>
        <tr class="cart_item">
            <td class="product-name apppago-installment" ><?php echo esc_html(__('First payment', 'apppago')) ?></td>
            <td class="product-total apppago-amount" >€ <?php echo esc_html($first_installment_amount) ?></td>
            <td class="product-name apppago-date" ><?php echo esc_html($date->format('m/Y')) ?></td>
        </tr>

        <?php
        for ($inst = 1; $inst < $installments; $inst++) {
            $date->modify('+ 1  month');
            ?>
            <tr class="cart_item">
                <td class="product-name apppago-installment" ><?php echo esc_html($inst) ?></td>
                <td class="product-total apppago-amount" >€ <?php echo esc_html($installment_amount) ?></td>
                <td class="product-name apppago-date" ><?php echo esc_html($date->format('m/Y')) ?></td>
            </tr>
            <?php
        }
        ?>
        <tr class="cart_item">
            <td class="product-name apppago-total"><?php echo esc_html(__('Total', 'apppago')) ?></td>
            <td class="product-total apppago-amount">€ <?php echo esc_html($totalFormatted) ?></td>
        </tr>
    </table>
    <?php
}
?>