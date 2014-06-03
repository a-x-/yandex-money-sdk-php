#PHP Yandex.Money API SDK

## Prerequisites

* PHP 5.3 or above
* curl, json & openssl extensions must be enabled
* composer for fetching dependencies (See http://getcomposer.org)

## Links

* Yandex.Money API page: [Ru](http://api.yandex.ru/money/), [En](http://api.yandex.com/money/)
* [sample app](https://github.com/yandex-money/yandex-money-sdk-php/tree/master/sample)
* [changelog](https://github.com/yandex-money/yandex-money-sdk-php/blob/master/CHANGELOG.md)

## Getting started

### Workflow

Your app asks user to give permissions to manage operations with user's Yandex.Money account. This process is known as
OAuth authorization. As a result of OAuth your app receives access token from Yandex.Money servers.

Then, with this token your app can make requests to our servers and perform operations with user's account,
 sometimes even without his actual activity.

### Code samples

#### OAuth and token receiving

First of all, we should get token. It's very simple. We take special uri which we'll sent user to ask permissions.

```php
$scope = "account-info operation-history operation-details "; // this is scope of permissions
$authUri = YandexMoney::authorizeUri(YOUR_APP_CLIENT_ID, YOUR_APP_REDIRECT_URI, $scope);
header('Location: ' . $authUri);
```

To get more information about permissions scope please visit Yandex.Money
 [API docs](http://api.yandex.com/money/doc/dg/concepts/protocol-rights.xml).

`YOUR_APP_CLIENT_ID` and `YOUR_APP_REDIRECT_URI` are parameters that you get when
 [register](https://sp-money.yandex.ru/myservices/new.xml) your app in Yandex.Money API.

Then Yandex.Money redirect user back to your app. At this redirect uri we should change temporary code parameter from
GET redirect request. This is even more simple.

```php
$ym = new YandexMoney(YOUR_APP_CLIENT_ID);
$receiveTokenResp = $ym->receiveOAuthToken($code, YOUR_APP_REDIRECT_URI, YOUR_APP_CLIENT_SECRET);

if ($receiveTokenResp->isSuccess()) {
    $token = $receiveTokenResp->getAccessToken();
    ... // Here you can store received token to your app's storage
} else {
    print "Error: " . $receiveTokenResp->getError();
    ...
}
```

#### Account info request

```php
$resp = $ym->accountInfo($token);
if ($resp->isSuccess()) {
    print $resp->getBalance();
    print $resp->getAccount();
    ...
} else {
    print "Error: " . $resp->getError();
    die();
}
```

Rest of requests you can use the same way. After this example we won't show you check on request success.

#### Operation history and details

```php
$resp = $ym->operationHistory($token, 0, 5);
// second param is first record record from set, third param is record count

$resp = $ym->operationDetail($token, $requestId);
// second param is requestId from payment method or one from operation hisory
```

#### p2p transfer

```php
$resp = $ym->requestPaymentP2P($token, "410011161616877", "0.02",
        "comment to sender", "message to recepient");

$requestId = $resp->getRequestId();
// payment by user's Yandex.Money wallet
$resp = $ym->processPaymentByWallet($token, $requestId);
```

#### Payment to shop by credit card

We'll show it on example of payment to Megafon.

```php
$params["pattern_id"] = "337"; // pattern_id - is id of shop in Yandex.Money.
$params["PROPERTY1"] = "921"; // preffix number
$params["PROPERTY2"] = "3020052"; // phone number
$params["sum"] = "2.00"; // amount
$resp = $ym->requestPaymentShop($token, $params);

$requestId = $resp->getRequestId();
$resp = $ym->processPaymentByCard($token, $requestId, "375"); // third param is cvc of user's credit card
```

#### Logs

We recommend you to log request to Yandex.Money system.
It's easy, you should only set log file name and path relatively to your current script.

Notice that if you want to log details of responses you should do it yourself.

```php
$ym = new YandexMoney(YOUR_APP_CLIENT_ID, './path/to/logfile/ym.log');
```
