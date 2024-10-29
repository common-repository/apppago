<?php
wp_enqueue_style('apppago_style', plugins_url('assets/css/apppago.css', plugin_dir_path(__FILE__)));
get_template_part(plugin_dir_path(__FILE__) . "includes/constant_apppago.php");
?>

<?php
if (isset($aOrderInstallments->installments) && count($aOrderInstallments->installments) > 1) {
    ?>
    <h3><?php echo esc_html(__('Installments Information', 'apppago')); ?></h3>
    <div class="woocommerce_subscriptions_related_orders">
        <table style="text-align: center; width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: center;"><?php echo esc_html(__('Nr.', 'apppago')) ?></th>
                    <th style="text-align: center;"><?php echo esc_html(__('Amount', 'apppago')) ?></th>
                    <th style="text-align: center;"><?php echo esc_html(__('Expected Date', 'apppago')) ?></th>
                    <th style="text-align: center;"><?php echo esc_html(__('Payment Date', 'apppago')) ?></th>
                    <th style="text-align: center;"><?php echo esc_html(__('Paid', 'apppago')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($aOrderInstallments->installments as $i => $set) {
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo esc_html($i == 0 ? __('First payment', 'apppago') : $i) ?></td>
                        <td style="text-align: center;"><?php echo esc_html('€ ' . number_format($set->amount, 2, ',', '')) ?></td>
                        <td style="text-align: center;">
                            <?php
                            $expDate = new DateTime($set->payableBy);
                            echo esc_html($expDate->format("d/m/Y"));
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <?php
                            if ($set->transactionDate != null) {
                                $actDate = new DateTime($set->transactionDate);
                                echo esc_html($actDate->format("d/m/Y"));
                            }
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <?php
                            if ($set->transactionStatus == __APPPAGO_TS_PAYED__) {
                                echo esc_html(__APPPAGO_ICON_OK__);
                            } else {
                                echo esc_html(__APPPAGO_ICON_KO__);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <a href="<?php echo esc_url($aOrderInstallments->urlContract) ?>" target="_blank"><?php echo esc_html(__('Download your contract', 'apppago')); ?></a>
        <?php echo esc_html(__('fore the installment plan by APPpago', 'apppago')); ?>
        <br><br>
    </div>
    <?php
}
?>
</div>