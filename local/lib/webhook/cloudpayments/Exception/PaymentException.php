<?php

namespace CloudPayments\Exception;

class PaymentException extends BaseException
{
    /**
     * @var string
     */
    protected $reason;

    /**
     * @var integer
     */
    protected $reasonCode;

    /**
     * @var string
     */
    protected $cardHolderMessage;

    /**
     * @var \CloudPayments\Model\Transaction
     */
    protected $transaction;

    public function __construct($response)
    {
        $this->reason = $response['Model']['Reason'];
        $this->reasonCode = $response['Model']['ReasonCode'];
        $this->cardHolderMessage = $response['Model']['CardHolderMessage'];
        $this->transaction = $response['Model'];

        parent::__construct($this->reason);
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return integer
     */
    public function getReasonCode()
    {
        return $this->reasonCode;
    }

    /**
     * @return string
     */
    public function getCardHolderMessage()
    {
        return $this->cardHolderMessage;
    }

    /**
     * @return \CloudPayments\Model\Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}