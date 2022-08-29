<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Request;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Internals\UserBudgetPool;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CgiftHandler
    extends PaySystem\ServiceHandler
    // implements PaySystem\IRefund
{
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$result = new PaySystem\ServiceResult();

		/** @var \Bitrix\Sale\PaymentCollection $paymentCollection */
		$paymentCollection = $payment->getCollection();

		if ($paymentCollection)
		{
			/** @var \Bitrix\Sale\Order $order */
			$order = $paymentCollection->getOrder();
			if ($order)
			{
				$res = $payment->setPaid('Y');
				if ($res->isSuccess())
				{
					$res = $order->save();
					if (!$res->isSuccess())
					{
						$result->addErrors($res->getErrors());
					}
				}
				else
				{
					$result->addErrors($res->getErrors());
				}
			}
		}

		return $result;
	}

    /**
    * @param Request $request
    * @return mixed
    */

    public function getPaymentIdFromRequest(Request $request)
    {
        $paymentId = $request->get('ORDER');
        $paymentId = preg_replace("/^[0]+/","",$paymentId);

        return intval($paymentId);
    }

    /**
    * @return array
    */
	public function getCurrencyList()
	{
		return array();
	}

    /**
     * @param Payment $payment
     * @param Request $request
     * @return PaySystem\ServiceResult
     */

    public function processRequest(Payment $payment, Request $request)
    {

        $result = new PaySystem\ServiceResult();
        // $action = $request->get('ACTION');
        $action = 0;
        $data = $this->extractDataFromRequest($request);

        $data['CODE'] = $action;

        if($action === 1)
        {
            $result->addError(new Error("Ошибка платежа"));
        } elseif($action === 0) {
            $fields = array(
                "PS_STATUS_CODE" => $action,
                "PS_STATUS_MESSAGE" => '',
                "PS_SUM" => $request->get('AMOUNT'),
                "PS_CURRENCY" => $payment->getField('CURRENCY'),
                "PS_RESPONSE_DATE" => new DateTime(),
                "PS_INVOICE_ID" => '',
            );

            $paymentSum = PriceMaths::roundPrecision($payment->getSum());

            UserBudgetPool::addPoolItem($order, ( $paymentSum * -1 ), UserBudgetPool::BUDGET_TYPE_ORDER_PAY, $payment);

            if ($this->isCorrectSum($payment, $request))
            {
                $data['CODE'] = 0;
                $fields["PS_STATUS"] = "Y";
                $fields['PS_STATUS_DESCRIPTION'] = "Оплата произведена успешно";
                $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            } else {
                $data['CODE'] = 200;
                $fields["PS_STATUS"] = "N";
                $message = "Неверная сумма платежа";
                $fields['PS_STATUS_DESCRIPTION'] = $message;
                $result->addError(new Error($message));
            }

            $result->setPsData($fields);

        } else {
            $result->addError(new Error("Неверный статус платежной системы при возврате информации о платеже"));
        }

        $result->setData($data);

        if (!$result->isSuccess())
        {
            PaySystem\ErrorLog::add(array(
                'ACTION' => "processRequest",
                'MESSAGE' => join('\n', $result->getErrorMessages())
            ));
        }

        return $result;
    }
}
