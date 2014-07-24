<?php

namespace YandexMoney;

use YandexMoney\Exception as Exceptions;
use YandexMoney\Operation\OperationDetail;
use YandexMoney\Response as Responses;

/**
 *
 */
class Client
{
    /**
     *
     */
    const VERSION = '1.3.0';

    /**
     *
     */
    const URI_API = 'https://money.yandex.ru/api';

    /**
     *
     */
    const URI_AUTH = 'https://sp-money.yandex.ru/oauth/authorize';

    /**
     *
     */
    const URI_TOKEN = 'https://sp-money.yandex.ru/oauth/token';

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @param string $clientId
     * @param string $logFile
     */
    public function __construct($clientId, $logFile = null)
    {
        self::validateClientId($clientId);
        $this->clientId = $clientId;
        $this->logFile  = $logFile;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @param string $redirectUri
     * @param string $scope =
     *
     * @return string
     */
    public static function makeAuthorizeUri($clientId, $redirectUri, $scope = null)
    {
        self::validateClientId($clientId);

        if (!isset($scope) || $scope === '') {
            $scope = 'account-info operation-history';
        }
        $scope = trim(strtolower($scope));

        $res = self::URI_AUTH . "?client_id=$clientId&response_type=code&"
            . http_build_query(['scope' => $scope, 'redirect_uri' => $redirectUri]);
        return $res;
    }

    /**
     * @param string $code
     * @param string $redirectUri
     * @param string $clientSecret
     *
     * @return \YandexMoney\Response\ReceiveTokenResponse
     */
    public function receiveOAuthToken($code, $redirectUri, $clientSecret = null)
    {
        $paramArray['grant_type']   = 'authorization_code';
        $paramArray['client_id']    = $this->clientId;
        $paramArray['code']         = $code;
        $paramArray['redirect_uri'] = $redirectUri;
        if (isset($clientSecret)) {
            $paramArray['client_secret'] = $clientSecret;
        }
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor();
        $resp      = $requestor->request(self::URI_TOKEN, $params);

        $responceObjectHelper = new Responses\ReceiveTokenResponse($resp);
        if(!$responceObjectHelper->isSuccess()) throw new ExceptionExtended($responceObjectHelper->getError());
        return $responceObjectHelper;
    }

//    /**
//     * @param string $accessToken
//     *
//     * @return boolean
//     */
//    public function revokeOAuthToken($accessToken)
//    {
//        $requestor = new ApiRequestor($accessToken, $this->logFile);
//        $requestor->request(self::URI_API . '/revoke');
//        return true;
//    }

//    /**
//     * @param string $accessToken
//     *
//     * @return \YandexMoney\Response\AccountInfoResponse
//     */
//    public function accountInfo($accessToken)
//    {
//        $requestor = new ApiRequestor($accessToken, $this->logFile);
//        $resp      = $requestor->request(self::URI_API . '/account-info');
//        return new Responses\AccountInfoResponse($resp);
//    }

//    /**
//     * @param string $accessToken
//     * @param int    $startRecord
//     * @param int    $records
//     * @param string $type
//     * @param null   $from
//     * @param null   $till
//     * @param null   $label
//     * @param null   $details
//     *
//     * @return \YandexMoney\Response\OperationHistoryResponse
//     */
//    public function operationHistory($accessToken, $startRecord = null, $records = null, $type = null, $from = null,
//                                     $till = null, $label = null, $details = null)
//    {
//        $paramArray = array();
//        if (isset($type)) {
//            $paramArray['type'] = $type;
//        }
//        if (isset($startRecord)) {
//            $paramArray['start_record'] = $startRecord;
//        }
//        if (isset($records)) {
//            $paramArray['records'] = $records;
//        }
//        if (isset($label)) {
//            $paramArray['label'] = $label;
//        }
//        if (isset($from)) {
//            $paramArray['from'] = $from;
//        }
//        if (isset($till)) {
//            $paramArray['till'] = $till;
//        }
//        if (isset($details)) {
//            $paramArray['details'] = $details;
//        }
//        if (count($paramArray) > 0) {
//            $params = http_build_query($paramArray);
//        } else {
//            $params = '';
//        }
//        $requestor = new ApiRequestor($accessToken, $this->logFile);
//        $resp      = $requestor->request(self::URI_API . '/operation-history', $params);
//        return new Responses\OperationHistoryResponse($resp);
//    }

//    /**
//     * @param string $accessToken
//     * @param string $operationId
//     *
//     * @return \YandexMoney\Operation\OperationDetail
//     */
//    public function operationDetail($accessToken, $operationId)
//    {
//        $paramArray['operation_id'] = $operationId;
//        $params                     = http_build_query($paramArray);
//        $requestor = new ApiRequestor($accessToken, $this->logFile);
//        $resp      = $requestor->request(self::URI_API . '/operation-details', $params);
//        return new OperationDetail($resp);
//    }

    /**
     * @param string      $accessToken
     * @param string      $to
     * @param float       $amount
     * @param string      $comment      - Комментарий к переводу, отображается в истории отправителя.
     * @param string      $message      - Комментарий к переводу, отображается в истории получателя.
     * @param string|null $label
     * @param bool|array  $test_payment - false или например ['test_card'=>'available','test_result'=>'success']
     *                                  test_card может быте не указано
     *                                  test_result обзательно и может  также приниматть значение кода ошибки из таблицы
     *                                  http://api.yandex.ru/money/doc/dg/reference/request-payment.xml#errors_table
     *
     * @return \YandexMoney\Response\RequestPaymentResponse
     */
    public function requestPaymentP2P($accessToken, $to, $amount, $comment = null, $message = null, $label = null, $test_payment = false)
    {
        $params = [
            'pattern_id'   => 'p2p',
            'to'           => $to,
            'amount'       => $amount,
            'comment'      => $comment,
            'message'      => $message,
            'label'        => $label,
            'test_payment' => $test_payment
        ];
        $params = $this->addTestPaymentFlag($test_payment, $params);
        return $this->stagePayment($accessToken, $params, 'request');
    }

    /**
     * @param string     $accessToken
     * @param string     $requestId
     * @param bool|array $test_payment
     *
     * @return \YandexMoney\Response\ProcessPaymentResponse
     */
    public function processPaymentByWallet($accessToken, $requestId, $test_payment)
    {
        $params = ['request_id' => $requestId, 'money_source' => 'wallet'];
        $params = $this->addTestPaymentFlag($test_payment, $params);
        return $this->stagePayment($accessToken, $params, 'process');
    }

    /**
     * @param string $accessToken
     * @param string $shopParams
     *
     * @return \YandexMoney\Response\RequestPaymentResponse
     */
    public function requestPaymentShop($accessToken, $shopParams)
    {
        return $this->stagePayment($accessToken, [$shopParams], 'request');
    }


    /**
     * @param string     $accessToken
     * @param string     $requestId
     * @param string     $csc
     * @param bool|array $test_payment
     *
     * @return \YandexMoney\Response\ProcessPaymentResponse
     */
    public function processPaymentByCard($accessToken, $requestId, $csc, $test_payment)
    {
        $params = ['request_id' => $requestId, 'money_source' => 'card', 'csc' => $csc];
        $params = $this->addTestPaymentFlag($test_payment, $params);
        return $this->stagePayment($accessToken, $params, 'process');
    }

    /**
     * @param string $clientId
     *
     * @throws \YandexMoney\Exception\Exception
     */
    private static function validateClientId($clientId)
    {
        if (empty($clientId)) {
            throw new Exceptions\Exception('You must pass a valid application client_id');
        }
    }

    /**
     * @param $accessToken
     * @param $paramArray
     * @param $stageName
     *
     * @return Response\ProcessPaymentResponse|Responses\RequestPaymentResponse
     */
    private function stagePayment($accessToken, $paramArray, $stageName)
    {
        if(IS_DEBUG_ALX) \Invntrm\_d($paramArray);
        $params = http_build_query($paramArray);
        if(IS_DEBUG_ALX) \Invntrm\_d($params);
        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp      = $requestor->request(self::URI_API . "/$stageName-payment", $params);

        $responceObjectHelper =  ($stageName == 'request') ?
            new Responses\RequestPaymentResponse($resp) :
            new Responses\ProcessPaymentResponse($resp);
        if(!$responceObjectHelper->isSuccess())
            throw new ExceptionExtended(
                $responceObjectHelper->getError()
            ,
                  $responceObjectHelper->getStatus()
                . ' / '
                . $responceObjectHelper->getError()
                . "\n Response Object: "
                . \Invntrm\varDumpRet($responceObjectHelper)
                . "\n Input params: "
                . \Invntrm\varDumpRet($paramArray)
            );
        return $responceObjectHelper;
    }

    private function addTestPaymentFlag($test_payment, $params)
    {
        if (is_array($test_payment) || $test_payment === true) {
            $test_payment_params = is_array($test_payment) ? $test_payment : [];
            if ($test_payment === true) $test_payment_params['test_result'] = 'success';
            $test_payment_params['test_payment'] = 'true';
            $params                              = array_merge($params, $test_payment_params);
        }
        return $params;
    }
}


class ExceptionExtended extends \Exception {
    protected $codeExtended;

    /**
     * @return string
     */
    public function getCodeExtended()
    {
        return $this->codeExtended;
    }

    /**
     * @param string     $codeExtended
     * @param string     $description
     * @param \Exception $previous    [optional]
     * @param \Exception $numericCode [optional]
     */
    public function __construct($codeExtended, $description=null, $previous=null, $numericCode=null)
    {
        if(!$description) $description = 'нет описания';
        parent::__construct($description, $numericCode, $previous);
        $this->codeExtended = \Invntrm\makeErrorCode($codeExtended);
    }
}

/**
 * Class ExceptionUser User not correct action exception
 * @package YandexMoney
 */
class ExceptionUser extends ExceptionExtended {

}

/**
 * Class ExceptionYandexmoney Yandex Money specified exception
 * @package YandexMoney
 */
class ExceptionYandexmoney extends ExceptionExtended {

}

/**
 * Class ExceptionApp Application bug exception
 * @package YandexMoney
 */
class ExceptionApp extends ExceptionExtended {

}
