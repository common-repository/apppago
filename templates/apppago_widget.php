<div class="apppago-widget">
    <div style="border-bottom: 1px solid #eee;margin: 0 -12px;padding: 8px 12px 4px;">
        <img src="<?php echo esc_url($logo) ?>" style="margin-left: auto;margin-right: auto;display: block;padding-bottom: 10px;">
    </div>
    <ul class="wc_status_list" style="overflow: hidden">
        <li style="margin: 0 -12px;padding: 8px 12px 4px;width:50%;display: block;float:left;text-align:center;">
            <h1><strong><span style="color:green;font-size:50px;"><?php echo esc_html($ok) ?></span></strong></h1>
            <?php echo esc_html($okText) ?>
        </li>
        <li style="margin: 0 -12px;padding: 8px 12px 4px;width:50%;display: block;float:left;text-align:center;">
            <h1><strong><span style="color:red;font-size:50px;"><?php echo esc_html($ko) ?></span></strong></h1>
            <?php echo esc_html($koText) ?>
        </li>
        <li style="margin: 0 -12px;padding: 8px 12px 4px;width:100%;display: block;float:left;text-align:center;margin-top: 20px;">
            <h1><strong><span style="color:black;font-size:50px;"><?php echo esc_html($completed) ?></span></strong></h1>
                    <?php echo esc_html($completedText) ?>
        </li>
    </ul>
</div>
