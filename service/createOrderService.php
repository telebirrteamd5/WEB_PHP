<?php
require_once('applyFabricTokenService.php');
require_once('./utils/tool.php');
require_once('./config/env.php');

class CreateOrderService
{
    public $req;
    public $BASE_URL;
    public $fabricAppId;
    public $appSecret;
    public $merchantAppId;
    public $merchantCode;
    public $notify_path;

    function __construct($baseUrl, $req, $fabricAppId, $appSecret, $merchantAppId, $merchantCode)
    {
        $this->BASE_URL = $baseUrl;
        $this->req = $req;
        $this->fabricAppId = $fabricAppId;
        $this->appSecret = $appSecret;
        $this->merchantAppId = $merchantAppId;
        $this->merchantCode = $merchantCode;
        $this->notify_path = "http://"  . $_SERVER['SERVER_NAME'];
    }
    /**
     * @Purpose: Creating Order
     *
     * @Param: no parameters it takes from the constructor
     * @Return: rawRequest|String
     */
    function createOrder()
    {
        $title = $this->req->title;
        $amount = $this->req->amount;

        $applyFabricTokenResult = new ApplyFabricToken(
            $this->BASE_URL,
            $this->fabricAppId,
            $this->appSecret,
            $this->merchantAppId
        );

        $result = json_decode($applyFabricTokenResult->applyFabricToken());

        $fabricToken = $result->token;

        $createOrderResult = $this->requestCreateOrder($fabricToken, $title, $amount);

        $prepayId = json_decode($createOrderResult)->biz_content->prepay_id;

        $rawRequest = $this->createRawRequest($prepayId);

        echo trim((string)$rawRequest);
    }

    /**
     * @Purpose: Requests CreateOrder
     *
     * @Param: fabricToken|String title|string amount|string
     * @Return: String | Boolean
     */

    function requestCreateOrder($fabricToken, $title, $amount)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->BASE_URL . '/payment/v1/merchant/preOrder');
        curl_setopt($ch, CURLOPT_POST, 1);

        // Header parameters
        $headers = array(
            "Content-Type: application/json",
            "X-APP-Key: " . $this->fabricAppId,
            "Authorization: " . $fabricToken
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Body parameters
        $payload = $this->createRequestObject($title, $amount);

        $data = $payload;

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // for dev environment only

        $server_output = curl_exec($ch);

        curl_close($ch);

        return $server_output;
    }
    /**
     * @Purpose: Creating a new merchantOrderId
     *
     * @Param: no parameters
     * @Return: returns a string format of time (UTC)
     */
    function createMerchantOrderId_()
    {
        return (string)time();
    }
    /**
     * @Purpose: Creating Request Object
     *
     * @Param: title|String and amount|String
     * @Return: Json encoded string
     */
    function createRequestObject($title, $amount)
    {
        $req = array(
            'nonce_str' => createNonceStr(),
            'method' => 'payment.preorder',
            'timestamp' => createTimeStamp(),
            'version' => '1.0',
            'biz_content' => [],
        );

        $biz = array(
            // 'notify_url' => 'https://www.google.com',
            'notify_url' => $this->notify_path . '/api/payment.php', // set your notify end point
            'business_type' => 'BuyGoods',
            'trade_type' => 'InApp',
            'appid' => $this->merchantAppId,
            'merch_code' => $this->merchantCode,
            'merch_order_id' => $this->createMerchantOrderId_(),
            'title' => $title,
            'total_amount' => $amount,
            'trans_currency' => 'ETB',
            'timeout_express' => '120m',
            'payee_identifier' => '220311',
            'payee_identifier_type' => '04',
            'payee_type' => '5000',
            // 'redirect_url' => $this->path . '/app/product_list.html'
        );

        $req['biz_content'] = $biz;
        $req['sign_type'] = 'SHA256WithRSA';

        $req['sign'] = sign($req);

        return json_encode($req);
    }
    /**
     * @Purpose: Create a rawRequest string for H5 page to start pay
     *
     * @Param: prepayId returned from the createRequestObject
     * @Return: rawRequest|string
     */
    function createRawRequest($prepayId)
    {
        $maps = array(
            "appid" => $this->merchantAppId,
            "merch_code" => $this->merchantCode,
            "nonce_str" => createNonceStr(),
            "prepay_id" => $prepayId,
            "timestamp" => createTimeStamp(),
            "sign_type" => "SHA256WithRSA"
        );
        
        foreach ($maps as $map => $m) {
                $rawRequest .= $map . '=' . $m."&";
        }
        $sign = sign($maps);
        // order by ascii in array
        $rawRequest = $rawRequest.'sign='. $sign;

        return $rawRequest;
    }
}
