<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/Helper/elementpropertytable.php");

class Balance extends ApiAbstract
{
    public $apiName = 'balance';

    private $elementIds = [];

    protected function createAction()
    {
        $this->getJsonData();
        $ids = $this->getBalance();

        if (!empty($ids)) {
            return $this->response(
                array_merge(
                        ["warehouses" => [[
                            "warehouseId" => "1",
                            "name" => "Интернет магазин",
                            "address" => "3-й Монетчиковский пер., д.16, стр.1",
                            "city" => "Москва",
                            "lat" => 55.731059,
                            "lon" => 37.629268,
                            "online" => true,
                            "public" => true,
                            "subway" => 'Павелецкая',
                            "mall" => ' '
                        ]],
                        "availability" => [
                            ["id" => "12345", "warehouseId" => "1", "quantity" => 10]
                        ]

                    ],
                    ['availability' => $ids]
                ),
                200
            );
        }

        return '';
    }

    private function getJsonData()
    {
        $this->elementIds = $this->requestParams['configurationIds'] ? $this->requestParams['configurationIds']  : '';

        return true;
    }

    private function getBalance() {
        $result = [];
        if (!empty($this->elementIds)) {
            $propIds = [
                477 => 'AMOUNT5',
            ];
            $tmp = ElementPropertyTable::getList(
                [
                    'select' => [
                        'IB_PROP_ID' => 'IBLOCK_PROPERTY_ID',
                        'IB_ID' => 'IBLOCK_ELEMENT_ID',
                        'VALUE',
                    ],
                    'filter' => ['=IBLOCK_ELEMENT_ID' => $this->elementIds, '=IBLOCK_PROPERTY_ID' => array_keys($propIds)],
                ]
            );

            while ($prop = $tmp->fetch()) {
                $result[] = ["id" => $prop['IB_ID'], "warehouseId" => "1", "quantity" => (int) $prop['VALUE'], 'price' => 0];
            }
        }

        return $result;
    }

    protected function indexAction()
    {
        // TODO: Implement indexAction() method.
    }

    protected function viewAction()
    {
        // TODO: Implement viewAction() method.
    }

    protected function updateAction()
    {
        // TODO: Implement updateAction() method.
    }

    protected function deleteAction()
    {
        // TODO: Implement deleteAction() method.
    }

}
