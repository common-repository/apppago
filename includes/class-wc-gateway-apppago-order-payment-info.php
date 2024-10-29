<?php

class WC_Gateway_APPpago_Order_Payment_Info
{
    private $order_id;

    public function __construct($order_id)
    {
        $this->order_id = $order_id;
    }

    public function SetInfoXPay($request)
    {
        $aDataToSave = array(
            "importo",
            "data",
            "divisa",
            "esito"
        );

        $info = array();
        foreach ($request as $key => $value) {
            if (in_array($key, $aDataToSave)) {
                if ($key == "messaggio") {
                    $info[$key] = str_replace("\\'", "", $value);
                } else {
                    $info[$key] = $value;
                }
            }
        }

        $info['_data_pagamento'] = "";
        $info['_cliente'] = $info['nome'] . " " . $info['cognome'];
        $info['_importo'] = number_format(($info['importo'] / 100), 2, ",", ".");

        $date = DateTime::createFromFormat('YmdHis', $info['data'] . $info['orario']);
        if ($date == false) {
            $date = DateTime::createFromFormat('d/m/Y', $info['data']);
            if ($date) {
                $info['_data_pagamento'] = $date->format("d/m/Y");
            }
        } else {
            if ($date) {
                $info['_data_pagamento'] = $date->format("d/m/Y H:i:s");
            }
        }
        
        update_post_meta($this->order_id, 'xpay_details_order', json_encode(wc_clean($info)));
    }

    public function GetInfoXPay($detailField = null)
    {
        $order = wc_get_order($this->order_id);
        if (!$order) {
            return;
        }

        $jDetailsOrder = get_post_meta($this->order_id, 'xpay_details_order', true);
        $aDetailsOrder = json_decode($jDetailsOrder, true);

        if ($detailField) {
            return $aDetailsOrder[$detailField];
        } else {
            return $aDetailsOrder;
        }
    }
}
