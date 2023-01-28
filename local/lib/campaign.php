<?

use Bitrix\Sale;

class Campaign
{
    const CAMPAIN_PERIOD = 60 * 60 * 24 * 10; // Время действия кук для каманий 10 дней

    public static function getCampaigns($CampaignId = '')
    {
        global $DB;
        $date = date('Y-m-d H:i:s');
        $res = [];
        $strSql = "SELECT e.ID,e.ACTIVE, e.ACTIVE_FROM, e.ACTIVE_TO, e.PREVIEW_PICTURE, e.PREVIEW_TEXT, e.DETAIL_PICTURE, e.DETAIL_TEXT,  
        (SELECT xx.VALUE FROM b_iblock_element_property xx WHERE  xx.IBLOCK_ELEMENT_ID=e.ID AND xx.IBLOCK_PROPERTY_ID=434) AS 'VALUE',
        (SELECT xx.VALUE FROM b_iblock_element_property xx WHERE  xx.IBLOCK_ELEMENT_ID=e.ID AND xx.IBLOCK_PROPERTY_ID=588) AS 'ss'
        FROM b_iblock_element e
        WHERE e.IBLOCK_ID=26" .
            ($CampaignId == '' ? " AND (e.ACTIVE='Y' OR(e.ACTIVE_FROM>='" . $date . "' AND e.ACTIVE_TO<='" . $date . "'))" : " AND e.ID=" . $CampaignId);
        $result = $DB->Query($strSql, false);
        while ($row = $result->Fetch()) {
            $res[$row['VALUE']] = $row;
            $res[$row['VALUE']]['IMG_1'] = CFile::GetPath($row["PREVIEW_PICTURE"]);
            $res[$row['VALUE']]['IMG_2'] = CFile::GetPath($row["DETAIL_PICTURE"]);
        }

        return $res;
    }

    /**
     * @param array $v
     * @param bool $test
     * @return array|int
     */
    public static function Campaigns(array $v, bool $test = false)
    {
        global $DB;
        global $APPLICATION;
        global $USER;
        if (empty($v['ID'])) {
            return [];
        }
        $isShowCampaign = $_COOKIE['__CleverMailReceived'] ? 0 : 1;
        $url_campaigns = '/manager/campaigns/type_campaigns/';
        $template = SITE_TEMPLATE_ID ? SITE_TEMPLATE_ID : '2021';
        $userAuth = $USER->IsAuthorized();
        // Проверяем есть ли кука кампании
        if (($_COOKIE['__CleverPushCampaign' . $v['ID']] == 1 && $isShowCampaign) || $test ) {
            //типы кампаний
            switch ($v['VALUE']) {
                // Рулетка
                case "69765":
                    $pageForShow = 1; // На какой странице показываем первый раз
                    if ($userAuth) {
                        // Не нужно для авторизованных
                        return 0;
                    }
                    elseif ($_COOKIE['__CleverCampaignPageVizit'] == $pageForShow)
                        // Для показа на требуемой странице
                    {
                        $APPLICATION->IncludeFile(
                            SITE_DIR . $url_campaigns . $template . '/' . $v['VALUE'] . '.php',
                            array(
                                "idsub" => $v['ID'],
                            ),
                            array(
                                "MODE" => "html"
                            )
                        );
                    } elseif ($_COOKIE['__CleverCampaignPageVizit'] > ($pageForShow + 1))
                        // для закрытия от показа на последующих страницах
                    {
                        setcookie(
                            '__CleverPushCampaign' . $v['ID'],
                            2, // закрываем от показа
                            $_COOKIE['__CleverPushCampaignTime' . $v['ID']],
                            '/'
                        );
                    }
                    break;
                ##################КАМПАНИЯ 69752 - форма email 1 раз в 10 дней #####################
                case "69752":
                    ## -- без авторизации --
                    $pageForShow = 4; // на какой странице показываем
                    if ($test) {
                        $APPLICATION->IncludeFile(SITE_DIR . $url_campaigns . $template . '/' . $v['VALUE'] . '.php',
                            array("idsub" => $v['ID'], 'text' => $v['PREVIEW_TEXT'], 'Dtext' => $v['DETAIL_TEXT'], 'm_img' => $v['IMG_1'], 'd_img' => $v['IMG_2']), array("MODE" => "html"));
                    } elseif ($userAuth) {
                        return 0;
                    }
                    elseif ($_COOKIE['__CleverCampaignPageVizit'] == $pageForShow)
                        // Для показа на требуемой странице
                    {
//                        $isShowCampaign = 1;
                        $_SESSION['CleverPushCampaign'][$v['ID']] += 1;
                        $APPLICATION->IncludeFile(
                            SITE_DIR . $url_campaigns . $template . '/' . $v['VALUE'] . '.php',
                            array(
                                'idsub' => $v['ID'],
                                'text' => $v['PREVIEW_TEXT'],
                                'Dtext' => $v['DETAIL_TEXT'],
                                'm_img' => $v['IMG_1'],
                                'd_img' => $v['IMG_2'],
                            ),
                            array("MODE" => "html")
                        );
//
                    } elseif ($_COOKIE['__CleverCampaignPageVizit'] > ($pageForShow + 1))
                        // для закрытия от показа на последующих страницах
                    {
                        setcookie(
                            '__CleverPushCampaign' . $v['ID'],
                            2,
                            $_COOKIE['__CleverPushCampaignTime' . $v['ID']],
                            '/'
                        );
                    } else
                    {
//                        $_SESSION['CleverPushCampaign'][$v['ID']] += 1; /*echo 'количество '.$_SESSION['CleverPushCampaign'][$v['ID']];*/
                    }
                    break;
                ##################КАМПАНИЯ 69752 - форма email 1 раз в 10 дней #####################
                ##################КАМПАНИЯ 69753 - полоса вверху #####################
                case "69753":
                    $APPLICATION->IncludeFile(SITE_DIR . $url_campaigns . $template . '/' . $v['VALUE'] . '.php',
                        array("idsub" => $v['ID'], 'text' => $v['PREVIEW_TEXT'], 'Dtext' => $v['DETAIL_TEXT'], 'm_img' => $v['IMG_1'], 'd_img' => $v['IMG_2'], 'ank' => $v['ss']), array("MODE" => "html"));
                    $isShowCampaign = 1;
                    break;
                ##################КАМПАНИЯ 69753 - полоса вверху #####################
            }

            return $isShowCampaign;
        } //если кука сработала уже и форма выводилась(ну хз зачем)
        elseif ($_COOKIE['__CleverPushCampaign' . $v['ID']] == 2) {
        } //если куки нет, то просто ставим
        else {
            $time = time() + self::CAMPAIN_PERIOD;
            // Устанавливаем временную метку (пока не знаю зачем, но это здесь было)
            setcookie(
                '__CleverPushCampaignTime' . $v['ID'],
                $time,
                $time,
                '/'
            );
            // устанавливаем куку кампании на 10 дней(позже можно вынести в базу и настраивать) и начинаем отсчет
            setcookie(
                '__CleverPushCampaign' . $v['ID'],
                1,
                $time,
                '/'
            );
            $_SESSION['CleverPushCampaign'][$v['ID']] = 1; //начинаем осчет страниц

        }

        return $isShowCampaign;
    }

    public static function CampaignView()
    {
        $flag = [];
        $arrCampaigns = self::getCampaigns($_GET['Campaig']);
        if (!empty($_GET['Campaig'])) {
            $f = true;
        } else {
            $f = false;
        }

        foreach ($arrCampaigns as $k => $v) {

            if (empty($flag[$v['VALUE']])) {
                $flag[$v['VALUE']] = self::Campaigns($v, $f);
            }
        }
    }

}

