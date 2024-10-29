<?php

class WC_APPpago_Api
{

    public $response;
    private $url;
    private $uri;
    private $domain;
    private $urlBack;

    public function __construct($domain, $urlBack, $url = null)
    {
        if ($url == null) {
            $url = APPPAGO_URL;
        }
        $this->set_env($url);
        $this->set_domain($domain);
        $this->set_uri();
        $this->urlBack = $urlBack;
    }

    /**
     * Set API URL
     *
     * @param string $url - API url
     *
     */
    public function set_env($url)
    {
        $this->url = $url;
    }

    /**
     * Set domain
     *
     * @param string $domain - domain of shop
     *
     */
    public function set_domain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Set API URI
     *
     * @param string $domain - domain of shop
     *
     */
    public function set_uri()
    {
        $this->uri = $this->url . APPPAGO_URI . $this->domain;
    }

    /**
     * Set order referance
     * 
     * @param string $orderReference
     */
    public function set_orderReference($orderReference)
    {
        $this->orderReference = $orderReference;
    }

    /**
     * sets config setting
     * 
     * @param type $oConfig
     */
    public function set_settings($idMerchant, $service, $secret)
    {
        $this->idMerchant = (int) $idMerchant;
        $this->service = $service;
        $this->secret = $secret;
    }

    /**
     * check apppago credentials
     * 
     */
    public function checkConfigs()
    {
        $pay_load = array(
            'merchantInfo' => array(
                'idMerchant' => $this->idMerchant,
                'hashPass' => sha1('paymentId=' . 'domain=' . $this->domain . 'serviceSmallpay=' . $this->service . 'uniqueId=' . $this->secret),
            ),
            'serviceSmallpay' => $this->service,
        );

        $this->uri .= '/checkSellConfigs';

        try {
            $this->exec_curl($this->uri, $pay_load, true);
        } catch (\Exception $exc) {
            $error = __('Please verify APPpago credentials', 'apppago');

            \WC_APPpago_Logger::LogExceptionError(new\Exception($error));

            throw new\Exception($error);
        }
    }

    public function retrieve_recurrences()
    {
        $pay_load = array(
            'idMerchant' => $this->idMerchant,
            'hashPass' => sha1('paymentId=' . $this->orderReference . 'domain=' . $this->domain . 'serviceSmallpay=' . 'uniqueId=' . $this->secret)
        );

        $this->uri .= '/retrieveRecurrences/' . $this->orderReference;

        try {
            WC_APPpago_Logger::Log('Retrieve recurrences request - ' . json_encode($pay_load));

            $res = $this->exec_curl($this->uri, $pay_load, true);

            WC_APPpago_Logger::Log('Retrieve recurrences response - ' . json_encode($this->response));

            return $res;
        } catch (\Exception $exc) {
            WC_APPpago_Logger::LogExceptionError($exc);

            throw new Exception(__('Error while retrieving installments info from APPpago', 'apppago'), 0, $exc);
        }
    }

    private function exec_curl($request_uri, $pay_load, $url_complete = false)
    {
        if ($url_complete) {
            $url = $request_uri;
        } else {
            $url = $this->url . $request_uri;
        }

        $args = array(
            'body' => json_encode($pay_load),
            'timeout' => '30',
            'headers' => array('Content-Type' => 'application/json', 'accept' => 'application/json'),
        );

        $response = wp_remote_post($url, $args);

        if (is_array($response) && json_decode($response['response']['code'], true) == '204') {
            return true;
        }

        if (is_array($response) && json_decode($response['response']['code'], true) != '200') {
            \WC_APPpago_Logger::Log(json_encode(array('url' => $url, 'pay_load' => $pay_load, 'response' => $response['response'])), 'error');
            throw new \Exception(json_encode($response['response']['code'], true) . ' - ' . json_decode($response['response']['message']));
        }

        if (is_array($response)) {
            $this->response = json_decode($response['body'], true);

            return true;
        } else {
            \WC_APPpago_Logger::Log(json_encode(array('url' => $url, 'pay_load' => $pay_load, 'response' => $response['response'])), 'error');

            return false;
        }
    }

}
