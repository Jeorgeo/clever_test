<?php

namespace CGift;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader,
    Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

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

    private const HL_ID = 70; // ID highloadblock блока по операциям с сертификатами.

    /**
     * @var object // Cертификат
     */
    private $cGift;

    /**
     * @var int // Сумма оплаты сертификатом
     */
    private $cGiftSum;

    /**
     * @var int // ID заказа
     */
    private $orderID;

    /**
     * @var int // Номер сертификата
     */
    private $cGiftID;

    /**
     * @var int // Пин код сертификата
     */
    private $cGiftPin;

    /**
     * @var string // Номер текущей транзакции
     */
    private $transactionID;

    function __construct($cGiftID, $cGiftPin)
    {
        $this->cGift = new DiGiftApi($cGiftID, $cGiftPin);

        if (!$this->cGift)
        {
            throw new Exception\CGigtException('Ошибка инициализации сертификата');
        }
        else
        {
            $this->cGiftID = $cGiftID;
            $this->cGiftPin = $cGiftPin;
        }
    }

    /**
     * Закрепление сертификата за заказом (пока временное решение и нужно ли
     * оно?!)
     *
     * @param $orderID //
     */
    public function setOrderID($orderID)
    {
        if ($orderID)
        {
            $this->orderID = $orderID;
        }

    }

    /**
     * Получение информации по сертификату
     *
     * @return array // Вся инфа из метода getInfo
     */
    public function getCGiftInfo()
    {
        $cGiftInfo = $this->cGift->getInfo();

        if ( $cGiftInfo )
        {
            $status = 1;
            $message = 'Данные по карте';
            $info = $cGiftInfo;
        } else {
            $status = 0;
            $message = 'Карта не найдена либо не активна';
            $info = 0;
        }
        // to do Нужна дополнительная обработка

        $result['status'] = $status;
        $result['message'] = $message;
        $result['info'] = $info;

        return $result;
    }

    /**
     * Получение остатка по подарочному сертификату
     *
     * @return int // Сумма остатка
     */
    public function getCGiftBalance()
    {
        $cGiftBalance = $this->cGift->getBalance();

        if ( $cGiftBalance )
        {
            $status = 1;
            $message = 'Запрос баланса';
            $balance = $cGiftBalance;
        } else {
            $status = 0;
            $message = 'Карта не найдена либо не активна';
            $balance = 0;
        }
        // to do Нужна дополнительная обработка

        $result['status'] = $status;
        $result['message'] = $message;
        $result['balance'] = $balance;

        return $result;
    }

    /**
     * Инициализация сертификата (проверка статуса и баланса)
     *
     * Отдельная инициализации при использовании
     *
     * @return array //
     */
    public function initCGift()
    {
        $cGiftBalance = $this->cGift->getBalance();

        if ( $cGiftBalance >= 0 )
        {
            $status = 1;
            $message = 'Карта активна';
            $balance = $cGiftBalance;
        }
        elseif ( $this->cGift->initDiGift() )
        {
            $result = $this->cGift->getBalance();
            if ($result > 0)
            {
                $status = 1;
                $message = 'Карта активирована';
                $balance = $result;
            }
            else
            {
                $status = 1;
                $message = 'Карта не активна. Нет средств';
                $balance = 0;
            }
        }
        else
        {
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
     * @param int $cGiftSum // Сумма планируемеого списания по сертификату
     *
     * @return array
     */
    public function setCGiftSum($cGiftSum)
    {

        $cGiftBalance = $this->cGift->getBalance(); // актуальный баланс

        $result = [];

        if ( ($cGiftBalance - $cGiftSum) >= 0 )
        {
            $this->cGiftSum = $cGiftSum;
            $status = 1;
            $message = 'Баланс сертификата соответствует запрашиваемой сумме';
        } else {
            $this->cGiftSum = $cGiftBalance;
            $status = 0;
            $message = 'Баланс сертификата меньше запрашиваемой суммы';
        }

        $result['status'] = $status;
        $result['message'] = $message;
        $result['sum'] = $this->cGiftSum;

        return $result;
    }
     public function saveOnlyTransaction( $amount, $reason = 'pay' ): array
    {
        if($reason == 'pay'){$amount = -$amount;}
        return $this->cGift->addCGiftTransaction( $amount );
    }
    
    /**
     * Записываем операцию по транзакции
     *
     * @param int $amount // Сумма списания по сертификату
     * @param string $reason // Причина списания по сертификату
     *
     * @return array
     */
    public function saveTransaction( $amount, $reason = 'pay' ): array
    {
        // предусмотреть хранение транзакции в сессии + алерты

        $itemTransaction = $this->cGift->addCGiftTransaction( $amount );
        if ( $itemTransaction['status'])
        {
            // Массив полей для добавления
            $data = [
                'UF_ORDER_ID'        => $this->orderID, // ID заказа
                'UF_CGIFT_ID'        => $this->cGiftID, // Номер сертификата
                'UF_CGIFT_PIN'        => $this->cGiftPin, // пин-код сертификата
                'UF_TRANSACT_ID'     => $itemTransaction['transactionId'], // Номер транзакции в digift
                'UF_AMAUNT'          => $itemTransaction['closedAmount'], // Сумма операции
                'UF_DATA_ADD'        => date('d.m.Y H:i:s'),
                'UF_API_STATUS'      => $itemTransaction['state'],
                'UF_TRANSACT_STATUS' => 'pay', // Метка, если понадобиться в дальнейшем делать возврат
                'UF_1S_STATUS'       => 'N', // Пока не передаём в 1С
            ];

            $hlblock = HL\HighloadBlockTable::getById(self::HL_ID)->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityDataClass = $entity->getDataClass();

            $result['isSaveHL'] = 0;

            if( $resultHl = $entityDataClass::add($data) )
            {
                $result['isSaveHL'] = 1;
            }

            $result['status'] = 1;
            $result['message'] = 'Операция по сертификату завершена';
            $result['amount'] = $itemTransaction['closedAmount'];
            $result['transactionId'] = $itemTransaction['transactionId'];
//            $result['hl_item'] = (array) $resultHl;

        } else {
            $result['status'] = 0;
            $result['message'] = 'Ошибка транзакции';
            $result['error'] = $itemTransaction['error'];
        }

        return $result;
    }

    /**
     * Проверка последней операции по сертификату на стороне сайта
     *
     * Находим по номеру заказ
     *
     * @param int // Номер заказа
     *
     * @return array // Запись из highload блока
     */
    private function checkLastTransaction($orderID = 0)
    {
        // пока не делал возможно не надо
        return [ 'status' => 1 ];
    }

    /**
     * Списание средств по сертификату (возврат описать отдельно)
     *
     *
     * @param
     */
    public function payTransaction($cGiftSum )
    {

        if ($cGiftSum > 0)
        {
            $paySum = -1 * $cGiftSum;
        }
        else
        {
            $paySum = $cGiftSum;
        }

        return $this->saveTransaction( $paySum, 'pay' );

    }

    /**
     * Списание средств по сертификату (возврат описать отдельно)
     *
     *
     * @param
     */
    public function refaundTransaction($cGiftSum )
    {

        $paySum = abs($cGiftSum);

        return $this->saveTransaction( $paySum, 'refaund' );

    }

    /**
     * Отмена последней операции по сертификату
     *
     * @param
     */
    public function cancelTransaction()
    {
        // пока не делал, уточнение нужна ли данная операция

        return $this->cGift->cancelCGiftTransaction( $transactionID );

    }

    /**
     * Логирование
     *
     * @param
     */
    private function logOperations()
    {

    }


}
