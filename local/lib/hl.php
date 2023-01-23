<?

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\Page\Asset,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Catalog\ProductTable,
    Bitrix\Sale,
    Bitrix\Highloadblock\HighloadBlockTable,
    Bitrix\Main\Entity;

Loader::includeModule('sale');
Loader::includeModule("highloadblock");
CModule::IncludeModule("catalog");

class HlClass
{

    #
    # HL класс
    #
    const status = [];

    public static function getOrderSource($table = 0, $params = [])
    {

        if (empty($params) || empty($table)) return [];
        $hlblock = HighloadBlockTable::getList(['filter' => ['=NAME' => $table]])->fetch();
        $entity_data_class = HighloadBlockTable::compileEntity($hlblock)->getDataClass();
        $data = ['filter' => $params];
        $arResult = $entity_data_class::getList($data);
        $out = $arResult->fetch();

        return $out;
    }

    /**
     * Получение записей из hl блока по параметрам
     *
     * @param $table string
     * @param $params array
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */

    public static function getMultiSource(string $table = '', array $params = []): array
    {

        if (empty($params) || empty($table)) return [];
        $hlblock = HighloadBlockTable::getList(['filter' => ['=NAME' => $table]])->fetch();
        $entity_data_class = HighloadBlockTable::compileEntity($hlblock)->getDataClass();
        $data = ['filter' => $params];
        $arResult = $entity_data_class::getList($data);
        while($ob = $arResult->fetch()){
            $out[] = $ob;
        }

        return $out;
    }

    public static function setOrderSource($table = 0, $params = [], $type = 1)
    {
        $data = [];
        if (empty($params) || empty($table)) return [];
        $hlblock = HighloadBlockTable::getList(['filter' => ['=NAME' => $table]])->fetch();
        $entity_data_class = HighloadBlockTable::compileEntity($hlblock)->getDataClass();

        if ($type == 1) {
            $result = $entity_data_class::add($params);
        } elseif ($type == 2 && $params['ID']) {
            $result = $entity_data_class::update($params['ID'], $params);
        }
        return $result;
    }

    public static function clearTable($table = '')
    {
        if (empty($table)) return false;
        $hlblock = HighloadBlockTable::getList(['filter' => ['=NAME' => $table]])->fetch();
        if (!empty($hlblock['TABLE_NAME'])) {
            global $DB;
            $delSQL = "DELETE FROM " . $hlblock['TABLE_NAME'];
            $resDEL = $DB->Query($delSQL, true);
            return !($resDEL == false);
        }
    }
}


