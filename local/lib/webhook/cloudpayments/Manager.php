<?php

namespace CloudPayments;

class Manager
{
    /**
     * @var string
     */
    protected $url = 'https://api.cloudpayments.ru';

    /**
     * @var string
     */
    protected $locale = 'ru-RU';

    /**
     * @var string
     */
    protected $publicKey;

    /**
     * @var string
     */
    protected $privateKey;

    /**
     * @var bool
     */
    protected $enableSSL;

    /**
     * @param $publicKey
     * @param $privateKey
     * @param bool $enableSSL
     */
    public function __construct($publicKey, $privateKey, $enableSSL = true)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->enableSSL = $enableSSL ? 2 : 0;
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function sendRequest($endpoint, array $params = [])
    {
        $params['CultureName'] = $this->locale;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $this->publicKey, $this->privateKey));
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->enableSSL);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->enableSSL);

        $result = curl_exec($curl);

        curl_close($curl);

        return (array)json_decode($result, true);
    }

        /**
     * @param string $endpoint
     * @param $params
     * @return array
     */
    protected function sendJSONRequest($endpoint, $params)
    {
        $params['CultureName'] = $this->locale;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->url . $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $this->publicKey, $this->privateKey));
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->enableSSL);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->enableSSL);

        $result = curl_exec($curl);

        curl_close($curl);

        return (array)json_decode($result, true);
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @throws Exception\RequestException
     */
    public function test()
    {
        $response = $this->sendRequest('/test');
        if (!$response['Success']) {
            throw new Exception\RequestException($response);
        }

        return $response;
    }

    /**
     * @param $amount
     * @param $currency
     * @param $ipAddress
     * @param $cardHolderName
     * @param $cryptogram
     * @param array $params
     * @param bool $requireConfirmation
     * @return Model\Required3DS|Model\Transaction
     * @throws Exception\PaymentException
     * @throws Exception\RequestException
     */
    public function chargeCard($amount, $currency, $ipAddress, $cardHolderName, $cryptogram, $params = [], $requireConfirmation = false)
    {
        $endpoint = $requireConfirmation ? '/payments/cards/auth' : '/payments/cards/charge';
        $defaultParams = [
            'Amount' => $amount,
            'Currency' => $currency,
            'IpAddress' => $ipAddress,
            'Name' => $cardHolderName,
            'CardCryptogramPacket' => $cryptogram
        ];

        $response = $this->sendRequest($endpoint, array_merge($defaultParams, $params));

        if ($response['Success']) {
            return Model\Transaction::fromArray($response['Model']);
        }

        if ($response['Message']) {
            throw new Exception\RequestException($response);
        }

        if (isset($response['Model']['ReasonCode']) && $response['Model']['ReasonCode'] !== 0) {
            throw new Exception\PaymentException($response);
        }

        return Model\Required3DS::fromArray($response['Model']);
    }

    /**
     * @param $amount
     * @param $currency
     * @param $accountId
     * @param $token
     * @param array $params
     * @param bool $requireConfirmation
     * @return Model\Required3DS|Model\Transaction
     * @throws Exception\PaymentException
     * @throws Exception\RequestException
     */
    public function chargeToken($amount, $currency, $accountId, $token, $params = [], $requireConfirmation = false)
    {
        $endpoint = $requireConfirmation ? '/payments/tokens/auth' : '/payments/tokens/charge';
        $defaultParams = [
            'Amount' => $amount,
            'Currency' => $currency,
            'AccountId' => $accountId,
            'Token' => $token,
        ];

        $response = $this->sendRequest($endpoint, array_merge($defaultParams, $params));

        if ($response['Success']) {
            return Model\Transaction::fromArray($response['Model']);
        }

        if ($response['Message']) {
            throw new Exception\RequestException($response);
        }

        if (isset($response['Model']['ReasonCode']) && $response['Model']['ReasonCode'] !== 0) {
            throw new Exception\PaymentException($response);
        }

        return Model\Required3DS::fromArray($response['Model']);
    }

    /**
     * @param $transactionId
     * @param $token
     * @return Model\Transaction
     * @throws Exception\PaymentException
     * @throws Exception\RequestException
     */
    public function confirm3DS($transactionId, $token)
    {
        $response = $this->sendRequest('/payments/cards/post3ds', [
            'TransactionId' => $transactionId,
            'PaRes' => $token
        ]);

        if ($response['Message']) {
            throw new Exception\RequestException($response);
        }

        if (isset($response['Model']['ReasonCode']) && $response['Model']['ReasonCode'] !== 0) {
            throw new Exception\PaymentException($response);
        }

        return Model\Transaction::fromArray($response['Model']);
    }

    /**
     * @param $transactionId
     * @param $amount
     * @throws Exception\RequestException
     */
    public function confirmPayment($transactionId, $amount)
    {
        $response = $this->sendRequest('/payments/confirm', [
            'TransactionId' => $transactionId,
            'Amount' => $amount
        ]);

        if (!$response['Success']) {
            throw new Exception\RequestException($response);
        }
    }

	/**
	 * @param $transactionId
	 * @param $amount
	 * @throws Exception\RequestException
	 */
	public function confirmPaymentReceipt($transactionId, $amount, $params = [])
	{
		$endpoint = '/payments/confirm';
		$defaultParams = [
			'TransactionId' => $transactionId,
			'Amount' => $amount
		];

		$mergeParams = array_merge($defaultParams, $params);

		$response = $this->sendJSONRequest($endpoint, $mergeParams);

		if (!$response['Success']) {
			throw new Exception\RequestException($response);
		}

		return $response;
	}

    /**
     * @param $transactionId
     * @throws Exception\RequestException
     */
    public function voidPayment($transactionId)
    {
        $response = $this->sendRequest('/payments/void', [
            'TransactionId' => $transactionId
        ]);

        if (!$response['Success']) {
            throw new Exception\RequestException($response);
        }
    }

    /**
     * @param $transactionId
     * @param $amount
     * @throws Exception\RequestException
     */
	public function refundPayment($transactionId, $amount, $params = [])
	{
		$endpoint = '/payments/refund';
		$defaultParams = [
			'TransactionId' => $transactionId,
			'Amount' => $amount
		];

		$response = $this->sendJSONRequest($endpoint, array_merge($defaultParams, $params));

		if (!$response['Success']) {
			throw new Exception\RequestException($response);
		}
	}

    /**
     * @param $invoiceId
     * @return Model\Transaction
     * @throws Exception\RequestException
     */
    public function findPayment($invoiceId)
    {
        $response = $this->sendRequest('/payments/find', [
            'InvoiceId' => $invoiceId
        ]);

        if (!$response['Success']) {
            throw new Exception\RequestException($response);
        }

        return Model\Transaction::fromArray($response['Model']);
    }

    /**
     * @param $inn
     * @param $invoiceId
     * @param $accountId
     * @param array $items
     * @param $taxationSystem
     * @param $email
     * @param $phone
     * @param $income
     * @param array $params
     * @throws Exception\RequestException
     */
    public function sendReceipt($inn, $invoiceId, $accountId, array $items, $taxationSystem, $email, $phone, $income = true, $params = [])
    {
        $receiptArray = [
            'Items' => $items,
            'taxationSystem' => $taxationSystem,
            'email' => $email,
            'phone' => $phone,
            'amounts' => $params['amounts'],
        ];

        $defaultParams = [
            'Inn' => $inn,
            'InvoiceId' => $invoiceId,
            'AccountId' => $accountId,
            'Type' => $income ? 'Income' : 'IncomeReturn',
            'CustomerReceipt' => $receiptArray
        ];

        $response = $this->sendJSONRequest('/kkt/receipt', array_merge($defaultParams, $params));

        if (!$response['Success']) {
            throw new Exception\RequestException($response);
        }
        return $response;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPublicKey($value)
    {
        $this->publicKey = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPrivateKey($value)
    {
        $this->privateKey = $value;

        return $this;
    }


    public function orderCreate($amount, $currency, $description, $email, $params = [], $sendEmail = false, $requireConfirmation = false)
    {
        $endpoint = '/orders/create';
        $defaultParams = [
            'Amount' => $amount,
            'Currency' => $currency,
            'Description' => $description,
            'RequireConfirmation' => $requireConfirmation,
            'Email' => $email,
            'SendEmail' => $sendEmail
        ];

//		file_put_contents(
//			$_SERVER["DOCUMENT_ROOT"] . "/logs/webhook/session/" . date("Ymd") . "_data.txt",
//			date("Y.m.d H:i:s") . "\n" . get_class($this) . ":  " . $this->requestParams['installId'] .
//				json_encode(array_merge($defaultParams, $params),
//				1
//			) . "\n",
//			FILE_APPEND
//		);
        $response = $this->sendJSONRequest($endpoint, array_merge($defaultParams, $params));

        if ($response['Success']) {
            return $response['Model'];
        }

        return [];
    }

	public function ApplePayCreate($amount, $currency, $description, $ip, $crypto, $params = [])
	{
		$endpoint = '/payments/cards/charge';
		$defaultParams = [
			'Amount' => $amount,
			'Currency' => $currency,
			'Description' => $description,
			'IpAddress' => $ip,
			'CardCryptogramPacket' => $crypto,
		];


		$response = $this->sendJSONRequest($endpoint, array_merge($defaultParams, $params));

		return $response;

		if ($response['Success']) {
			return Model\Transaction::fromArray($response['Model']);
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		if (isset($response['Model']['ReasonCode']) && $response['Model']['ReasonCode'] !== 0) {
			throw new Exception\PaymentException($response);
		}

		return Model\Required3DS::fromArray($response['Model']);
	}

	public function SubscriptionsFind($accountId)
	{
		$endpoint = '/subscriptions/find';
		$response = $this->sendJSONRequest($endpoint, ['accountId' => $accountId]);

		if ($response['Success']) {
			$subscriptionList = [];
			foreach ($response['Model'] as $subscription) {
				$subscriptionList[] = Model\Subscription::fromArray($subscription);
			}
			return $subscriptionList;
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		return Model\Subscription::fromArray($response['Model']);;
	}

	public function SubscriptionsGet($id)
	{
		$endpoint = '/subscriptions/get';
		$response = $this->sendJSONRequest($endpoint, ['id' => $id]);

		if ($response['Success']) {
			return Model\Subscription::fromArray($response['Model']);
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		return Model\Subscription::fromArray($response['Model']);
	}

    public function TransactionGet($id)
	{
		$endpoint = '/payments/get';
		$response = $this->sendJSONRequest($endpoint, ['TransactionId' => $id]);

		return $response;
	}

	public function SubscriptionsUpdate($id, $params = [])
	{
		if (empty($params)) return [];

		$endpoint = '/subscriptions/update';
		$response = $this->sendJSONRequest($endpoint, array_merge(['id' => $id], $params));

		if ($response['Success']) {
			return Model\Subscription::fromArray($response['Model']);
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		return Model\Subscription::fromArray($response['Model']);
	}

	public function SubscriptionsCancel($id)
	{
		$endpoint = '/subscriptions/cancel';
		$response = $this->sendJSONRequest($endpoint, ['id' => $id]);

		if ($response['Success']) {
			return Model\Subscription::fromArray($response['Model']);
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		return Model\Subscription::fromArray($response['Model']);
	}

	public function SubscriptionsCreate($params)
	{
		$endpoint = '/subscriptions/create';
		if (empty($params['token'])) return 'token';
		if (empty($params['accountId'])) return 'accountId';
		if (empty($params['description'])) return 'description';
		if (empty($params['email'])) return 'email';
		if (empty($params['amount'])) return 'amount';
		if (empty($params['currency'])) return 'currency';
		if (empty($params['startDate'])) return 'startDate';
		if (empty($params['interval'])) return 'interval';
		if (empty($params['period'])) return 'period';

		$response = $this->sendJSONRequest($endpoint, $params);

		if ($response['Success']) {
			return Model\Subscription::fromArray($response['Model']);
		}

		if ($response['Message']) {
			throw new Exception\RequestException($response);
		}

		return Model\Subscription::fromArray($response['Model']);
	}

	// вывод списка токенов
	public function GetTokenList() {

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, "https://api.cloudpayments.ru/payments/tokens/list");
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $this->publicKey, $this->privateKey));
		curl_setopt($curl, CURLOPT_TIMEOUT, 20);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->enableSSL);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->enableSSL);

		$result = curl_exec($curl);

		curl_close($curl);

		return (array)json_decode($result, true);

	}
}
