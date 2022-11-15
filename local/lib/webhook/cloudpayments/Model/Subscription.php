<?php

namespace CloudPayments\Model;


class Subscription
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var integer
     */
    protected $currencyCode;

    /**
     * @var bool
     */
    protected $requireConfirmation;

    /**
     * @var string
     */
    protected $accountId;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $startDateIso;

    /**
     * @var \DateTime
     */
    protected $lastTransactionDateIso;

    /**
     * @var \DateTime
     */
    protected $nextTransactionDateIso;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var integer
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $interval;

    /**
     * @var integer
     */
    protected $intervalCode;

    /**
     * @var integer
     */
    protected $period;

    /**
     * @var integer
     */
    protected $maxPeriods;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $value
     * @return $this
     */
    public function setId($value)
    {
        $this->id = $value;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setAmount($value)
    {
        $this->amount = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setCurrency($value)
    {
        $this->currency = $value;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * @param integer $value
     * @return $this
     */
    public function setCurrencyCode($value)
    {
        $this->currencyCode = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getRequireConfirmation()
    {
        return $this->requireConfirmation;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setRequireConfirmation($value)
    {
        $this->requireConfirmation = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setAccountId($value)
    {
        $this->accountId = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setEmail($value)
    {
        $this->email = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setDescription($value)
    {
        $this->description = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setData($value)
    {
        $this->data = $value;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDateIso()
    {
        return $this->startDateIso;
    }

    /**
     * @param \DateTime $value
     * @return $this
     */
    public function setStartDateIso($value)
    {
        $this->startDateIso = $value;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastTransactionDateIso()
    {
        return $this->lastTransactionDateIso;
    }

    /**
     * @param \DateTime $value
     * @return $this
     */
    public function setLastTransactionDateIso($value)
    {
        $this->lastTransactionDateIso = $value;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNextTransactionDateIso()
    {
        return $this->nextTransactionDateIso;
    }

    /**
     * @param \DateTime $value
     * @return $this
     */
    public function setNextTransactionDateIso($value)
    {
        $this->nextTransactionDateIso = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;

        return $this;
    }

    /**
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param integer $value
     * @return $this
     */
    public function setStatusCode($value)
    {
        $this->statusCode = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInterval($value)
    {
        $this->interval = $value;

        return $this;
    }

    /**
     * @return integer
     */
    public function getIntervalCode()
    {
        return $this->intervalCode;
    }

    /**
     * @param integer $value
     * @return $this
     */
    public function setIntervalCode($value)
    {
        $this->intervalCode = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setPeriod($value)
    {
        $this->period = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxPeriods()
    {
        return $this->maxPeriods;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setMaxPeriods($value)
    {
        $this->maxPeriods = $value;

        return $this;
    }

    /**
     * @param $params
     * @return Subscription
     */
    public static function fromArray($params)
    {
        $subscription = new Subscription();

        $subscription->setId($params['Id']);
        $subscription->setAmount($params['Amount']);
        $subscription->setCurrency($params['Currency']);
        $subscription->setCurrencyCode($params['CurrencyCode']);

        if (isset($params['RequireConfirmation'])) {
            $subscription->setRequireConfirmation($params['RequireConfirmation']);
        }
        
        if (isset($params['AccountId'])) {
            $subscription->setAccountId($params['AccountId']);
        }

        if (isset($params['Email'])) {
            $subscription->setEmail($params['Email']);
        }

        if (isset($params['Description'])) {
            $subscription->setDescription($params['Description']);
        }

        if (isset($params['JsonData'])) {
            $subscription->setData((array)$params['JsonData']);
        }

        if (isset($params['StartDateIso'])) {
            $subscription->setStartDateIso(new \DateTime($params['StartDateIso']));
        }

        if (isset($params['LastTransactionDateIso'])) {
            $subscription->setLastTransactionDateIso(new \DateTime($params['LastTransactionDateIso']));
        }

        if (isset($params['NextTransactionDateIso'])) {
            $subscription->setNextTransactionDateIso(new \DateTime($params['NextTransactionDateIso']));
        }

        if (isset($params['Status'])) {
            $subscription->setStatus(strtolower($params['Status']));
        }

        if (isset($params['StatusCode'])) {
            $subscription->setStatusCode($params['StatusCode']);
        }

        if (isset($params['Interval'])) {
            $subscription->setInterval($params['Interval']);
        }

        if (isset($params['IntervalCode'])) {
            $subscription->setIntervalCode($params['IntervalCode']);
        }

        if (isset($params['Period'])) {
            $subscription->setPeriod($params['Period']);
        }

        if (isset($params['MaxPeriods'])) {
            $subscription->setMaxPeriods($params['MaxPeriods']);
        }

        return $subscription;
    }
}