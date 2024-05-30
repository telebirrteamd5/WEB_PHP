
<?php

require_once('vendor/autoload.php');
/**
 * @use phpseclib\Crypt\RSA version - 1.0
 */
use phpseclib\Crypt\RSA;

function sign($request)
{

    $exclude_fields = array("sign", "sign_type", "header", "refund_info", "openType", "raw_request");
    $data = $request;
    ksort($data);
    $stringApplet = '';
    foreach ($data as $key => $values) {

        if (in_array($key, $exclude_fields)) {
            continue;
        }

        if ($key == "biz_content") {
            foreach ($values as $value => $single_value) {
                if ($stringApplet == '') {
                    $stringApplet = $value . '=' . $single_value;
                } else {
                    $stringApplet = $stringApplet . '&' . $value . '=' . $single_value;
                }
            }
        } else {
            if ($stringApplet == '') {
                $stringApplet = $key . '=' . $values;
            } else {
                $stringApplet = $stringApplet . '&' . $key . '=' . $values;
            }
        }
    }

    $sortedString = sortedString($stringApplet);

    return SignWithRSA($sortedString);
}

/**
 * @Purpose: sorting string
 *
 * @Param: stringApplet|string
 * @Return: 
 */

function sortedString($stringApplet)
{
    $stringExplode = '';
    $sortedArray = explode("&", $stringApplet);
    sort($sortedArray);
    foreach ($sortedArray as $x => $x_value) {
        if ($stringExplode == '') {
            $stringExplode = $x_value;
        } else {
            $stringExplode = $stringExplode . '&' . $x_value;
        }
    }

    return $stringExplode;
}
/**
 * @Purpose: Generate RSA signature of data
 *
 * @Param: $data - the sign message in array format
 * @Return: base64 encoded sign signed with sha256
 */
function SignWithRSA($data)
{
    $rsa = new Crypt_RSA();

    $private_key_load = file_get_contents('./config/private_key.pem');

    $private_key = trimPrivateKey($private_key_load)[2];

    if ($rsa->loadKey($private_key) != TRUE) {
        echo "Error loading PrivateKey";
        return;
    };

    $rsa->setHash("sha256");

    $rsa->setMGFHash("sha256");

    // $rsa->signatureMode(Crypt_RSA::$signatureMode);
    $signtureByte = $rsa->sign($data);

    return base64_encode($signtureByte);
}
/**
 * @Purpose: To trim the private key 
 *
 * @Param: $stringData -> the private key to be trimmed
 * @Return: array of the return of explode function
 */
function trimPrivateKey($stringData)
{

    return explode("-----", (string)$stringData);
}
/**
 * @Purpose: Generate unique merchant order id
 *
 * @Param: no-Parameter is required.
 * @Return: String format of the time function.
 */
function createMerchantOrderId()
{
    return (string)time();
}

/**
 * @Purpose: Generate timestamp
 *
 * @Param: no-Parameter is required.
 * @Return: String format of the time function.
 */
function createTimeStamp()
{
    return (string)time();
}

/**
 * @Purpose: Generate a 32 length of random string
 *
 * @Param: no-Parameter is required.
 * @Return: A random string with length of 32..
 */
function createNonceStr()
{
    $chars = [
        "0",
        "1",
        "2",
        "3",
        "4",
        "5",
        "6",
        "7",
        "8",
        "9",
        "A",
        "B",
        "C",
        "D",
        "E",
        "F",
        "G",
        "H",
        "I",
        "J",
        "K",
        "L",
        "M",
        "N",
        "O",
        "P",
        "Q",
        "R",
        "S",
        "T",
        "U",
        "V",
        "W",
        "X",
        "Y",
        "Z",
    ];
    $str = "";
    for ($i = 0; $i < 32; $i++) {
        $index = intval(rand() * 35);
        $str .= $chars[$i];
    }
    // return uniqid();
    return "fcab0d2949e64a69a212aa83eab6ee1d";
}
