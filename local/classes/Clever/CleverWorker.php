<?php

namespace Clever;

use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

/**
 * Класс для работы с heightblocks
 *
 */
class CleverWorker
{
    use DebugResult; // Отладка

    /** @var int hlBlockID ID нашего highloadblock */
    /** @var string tableName название нашего highloadbloc */
    /** @var object entityDataClass Сущность нашего highloadbloc*/
    /** @var array NAME_LIST таблица соответсий*/

    private $hlBlockID; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    private $tableName; // Указываем название нашего highloadblock блока к которому будет делать запросы.
    private $entityDataClass; // объект нашего highloadblock блока к которому будет делать запросы.

    const NAME_LIST = [
                'products' => 'Products',
                'barcode' => 'Barcodes'
            ];

    function __construct($tableName)
    {

        $this->tableName = $tableName;

        $name = self::NAME_LIST[$tableName];

        $hlblockAdd = HL\HighloadBlockTable::add([
            'NAME' => $name,
            'TABLE_NAME' => $tableName
        ]);
        if (!$hlblockAdd->isSuccess() || !$hlblockAdd) {
            $hlblockSearch = HL\HighloadBlockTable::getList(
                [
                    'filter' => [
                        '=NAME' => $name
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
     * Получение баркода по ID записи
     *
     * @param int
     *
     * @return int
     */


    public function getBarCode(int $hlBlockRecordID):int
    {
        $entityDataClass = $this->entityDataClass;
        $rsData = $entityDataClass::getList(
            array(
           'select' => array('*'),
           'order' => array('ID' => 'ASC'),
           'filter' => array('ID'=> $hlBlockRecordID)
            )
        );
        $result = $rsData->Fetch();
        return $result['UF_CODE'];
    }

    /**
     * Получение ID по баркоду записи
     *
     * @param string
     *
     * @return int
     */

    private function getByBarCode(string $barCode):int
    {
        $entityDataClass = $this->entityDataClass;
        $rsData = $entityDataClass::getList(
            array(
           'select' => array('*'),
           'order' => array('ID' => 'ASC'),
           'filter' => array('UF_CODE'=> $barCode)
            )
        );
        $result = $rsData->Fetch();
        return (int) $result['ID'];
    }

    /**
     * Обновление highloadblock Products
     *
     * @param int
     * @param float
     *
     * @return bool
     */

    public function updateProduts(int $hlBlockRecordID, float $price = 0):bool
    {
        $entityDataClass = $this->entityDataClass;

        if ($price) {
            $dataForUpdate = [
                'UF_PRICE' => $price
            ];

        }

        if ($entityDataClass::update($hlBlockRecordID, $dataForUpdate)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Обновление highloadblock описания
     *
     * @param string
     * @param string
     *
     * @return bool
     */

    public function updateDescription(string $barCode, string $text = ''):bool
    {
        $entityDataClass = $this->entityDataClass;

        $hlBlockRecordID = $this->getByBarCode($barCode);

        if ($text) {
            $dataForUpdate = [
                'UF_DESCRIPTION' => $text
            ];
        }
        if ($entityDataClass::update($hlBlockRecordID, $dataForUpdate)) {
            return true;
        } else {
            return false;
        }
    }

}
