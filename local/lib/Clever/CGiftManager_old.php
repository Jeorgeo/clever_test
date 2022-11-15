<?php

namespace CGift;

use Bitrix\Main\Loader,
    Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity;

if (!Loader::includeModule('highloadblock')) {
    throw new CGigtException('модуль highload не подключен');
}

if (!Loader::includeModule('sale')) {
    throw new CGigtException('модуль sale не подключен');
}

/**
 * Основной обработчик-переключатель
 *
 * Класс для работы с подарочным сертификатом в заказе
 */
class CGiftManager
{

    private const PAYMENT_GIFT_ID = 12; // ID платежной системы оплаты сертификатом

    private const PAYMENT_CLOUD_ID = 10; // ID платёжной системы оплаты сервисом Cloud

    private const PAYMENT_COURIER_ID = 1; // ID платёжной системы оплаты курьру

    private const HL_ID = 1; // ID highloadblock блока по операциям с сертификатами.

    /**
     * @var object // Cертификат
     */
    private $cGift;

    /**
     * @var object // Объект highloadblock блока с операциями по сертификатам
     */
    private $entityDataClass;

    /**
     * @var int // Сумма оплаты сертификатом
     */
    private $cGiftSum;

    /**
     * @var int // ID highloadblock блока по операциям с сертификатами. (уточнить возможно не пригодится)
     */
    private $hlBlockID;

    function __construct($cGiftID, $cGiftPin)
    {
        $this->cGift = new DiGiftApi($cGiftID, $cGiftPin);

        $hlblockAdd = HL\HighloadBlockTable::add([
            'NAME' => self::HL_NAME,
            'TABLE_NAME' => self::TABLE_NAME
        ]);
        if (!$hlblockAdd->isSuccess() || !$hlblockAdd) {
            $hlblockSearch = HL\HighloadBlockTable::getList(
                [
                    'filter' => [
                        '=NAME' => self::HL_NAME
                    ]
                ])->fetch();
            $hlBlockID = $hlblockSearch['ID'];
        } else {
            $hlBlockID = $hlblockAdd->getId();
        }

        $hlblock = HL\HighloadBlockTable::getById($hlBlockID)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $this->entityDataClass = $entity->getDataClass();

        $this->hlBlockID = $hlBlockID;

    }

    /**
     * Инициализация сертификата (проверка статуса и баланса)
     *
     * @param
     */
    public function initCGift()
    {
        $result = [];

        $cGiftBalance = $this->cGift->getBalance();

        if ( $this->cGift->initDiGift() )
        {
            $status = 1;
            $message = 'Карта активирована';
            $balance = $cGiftBalance;
        } else {
            $status = 0;
            $message = 'Карта не найдена либо не активна';
            $balance = 0;
        }

        $result['status'] = $status;
        $result['message'] = $message;
        $result['balance'] = $balance;

        return $result;
    }

    /**
     * Установка суммы погашения сертификата
     *
     * @param
     */
    public function setCGiftSum($cGiftSum)
    {

        $cGiftBalance = $this->cGift->getBalance();

        $result = [];

        if ( ($cGiftBalance - $cGiftSum) >= 0 )
        {
            $this->cGiftSum = $cGiftSum;
            $result['status'] = 1;
            $result['message'] = 'Баланс сертификата соответствует запрашиваемой сумме';
        } else {
            $this->cGiftSum = $cGiftBalance;
            $result['status'] = 0;
            $result['message'] = 'Баланс сертификата меньше запрашиваемой суммы';
        }

        return $result;

    }

    /**
     * Пересчёт в корзине
     *
     * @param
     */
    private function recalculationCart()
    {

    }

    /**
     * Пересчёт в заказе
     *
     * @param
     */
    private function recalculationOrder($orderID, $cGiftSum)
    {
        // Загружаем заказ и получаем данные по оплате сертификатом

        $order = \Bitrix\Sale\Order::load($orderID);
        $paymentCollection = $order->getPaymentCollection();
        $orderPrice = $order->getPrice();
        $payment = $paymentCollection->getItemById(PAYMENT_CLOUD_ID);

        // Меняем сумму оплаты по текущей оплате

        $payment->setField('SUM', $orderPrice - $cGiftSum );

        // Добавляем сумму оплаты по сертификату

        $cGiftPayment = \Bitrix\Sale\PaySystem\Manager::getObjectById(PAYMENT_GIFT_ID );
        $newPayment = $paymentCollection->createItem($cGiftPayment);
        $newPayment->setField('SUM', $cGiftSum );

    }

    /**
     * Записываем операцию по транзакции
     *
     * @param
     */
    private function saveTransaction()
    {

    }

    /**
     * Проверка последней операции по сертификату
     *
     * @param
     */
    private function checkLastTransaction()
    {

    }

    /**
     * Списание средств по сертификату
     *
     * @param
     */
    public function addTransaction()
    {

    }

    /**
     * Возврат средств по сертификату
     *
     * @param
     */
    public function refaundTransaction()
    {

    }

    /**
     * Обработка исключений и логирование
     *
     * @param
     */
    private function logOperations()
    {

    }



}
