<?
// Добавление призов для рулетки

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $USER, $DB;

use Bitrix\Main\Page\Asset;

Asset::getInstance()->addCss("/manager/admin_scripts/style.css");

//Asset::getInstance()->addCss("/manager/admin_scripts/style.css");
Bitrix\Main\Loader::registerAutoLoadClasses(
    null,
    array(
        'FilesСlass' => '/local/lib/files.php',
        'HlClass' => '/local/lib/hl.php'
    )
);
########################################################################
# Разрешено админам, контент-менеджерам, группе с доступам к admin scripts и группе SEO
#

//$hlName = 'BrandReference'; // hl блок с призами
$hlName = 'PromoRoulette'; // hl блок с призами

$basePath = 'campaigns'; // базовый путь для хранения выгрузок

// для разработки заглушено
//if (!CSite::InGroup(array(1, 38, 41, 8))) {
if (0) {
    header("location: /");
} else {
//## загружаем файл##
    $result = FilesСlass::addFile($_REQUEST['upload_csv'], $basePath);
    $log = [];
    $resArray = [];
    $resLog = [];
    $i = 0;

    if ($result['status'] == 1 && $result['file']) {
        $ArrResFile = FilesСlass::readFile($result['file']);
        if ($ArrResFile['status'] == 1) {
            foreach ($ArrResFile['val'] as $v) {
                if (empty($v[0])) {
                    $logError[] = print_r($v, 1) . ' ошибка добавления';
                } else {
                    $resArray[] = [
                        'UF_PROMO_ID'       => $v[0],
                        'UF_PRIZE_ID'       => $v[1],
                        'UF_PRIZE_CHANCE'   => $v[2],
                        'UF_PRIZE_IMG'      => $v[3], // откуда выгружать картинки
                        'UF_PRIZE_TEXT'     => $v[4],
                        'UF_PRIZE_COLOR'    => $v[5],
                        'UF_PRIZE'          => $v[6],
                    ];
                }
            }
        }

        if (!empty($resArray)) {
            if (HlClass::clearTable( $hlName )) {
                //загружаем данные по рулетке
                foreach ($resArray as $item) {
                    echo '<pre>';
                    echo print_r($item);
                    echo '</pre>';
                    $add = HlClass::setOrderSource($hlName, $item);
                    if ($add->isSuccess()) {
                        $log[] = $item . ' добавлены в XML';
                    } else {
                        $logError[] = $item . ' ошибка добавления';
                    }
                }
            } else {
                $logError[] = 'Ошибка при очистке HL таблицы.';
            }
        } else {
            $logError[] = 'CSV файл не имеет нужных данных.';
        }
    }

    ?>

    <div class="box_res_update_price">

        <h6>Загрузить файл-шаблон для рулетки</h6>
        <div class="box_name_file">

            <form action="" method="post" enctype="multipart/form-data" class="form_upload_csv6">
                <input type="submit" name="upload_csv" value="Загрузить">
                <input type="file" name="file_csv" accept=".csv">
            </form>

        </div>

    </div>
    <? if(!empty($log)){?>
        <div class="box_res_update_price" style="width:800px;">
            <h4>Отчет</h4>
            <? foreach($log as $k=>$v){
                echo $k.' : '.$v .'<hr>';
            }?>
        </div>
    <?}?>
    <? if(!empty($logError)){?>
        <div class="box_res_update_price" style="width:800px;">
            <h4 style="color: red;">Ошибки</h4>
            <? foreach($logError as $k=>$v){
                echo $k.' : '.$v .'<hr>';
            }?>
        </div>
    <?}?>
<? } ?>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?><?php
