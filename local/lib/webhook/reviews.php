<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/local/lib/webhook/api.php");


class Reviews extends ApiAbstract
{
    public $apiName = 'reviews';

    private $bookId;

    protected function createAction()
    {
        $this->getJsonData();
        $reviews = $this->getReviews();

        if (!empty($reviews)) {
            $this->createLog('Reviews ответ: ', 'Есть список отзывов');
            //    $this->createLog('Reviews ответ: ', $reviews);
            return $this->response($reviews, 200);
        }

        $this->createLog('Reviews ответ: ', 'Нет отзывов!');
        return $this->response([]);
    }

    private function getJsonData()
    {
        $this->bookId = $this->requestParams['id'] ? (int)$this->requestParams['id'] : 0;
        $this->externalUserId = 0; //  пользователя нет

        return true;
    }

    private function getReviews() {
        $result = [];

        if ($this->bookId > 0) {
            $arSelect = Array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "PREVIEW_PICTURE", "DATE_CREATE", "DETAIL_TEXT", "PROPERTY_RATING");
            $arFilter = array("IBLOCK_ID" => 14, "SECTION_CODE" => $this->bookId ,'ACTIVE'=>'Y');
            $res = CIBlockElement::GetList(array("DATE_CREATE" => "DESC"), $arFilter, false, array(), $arSelect);
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();
                $result['reviews'][] = [
                    'author' => $arFields['NAME'],
                    'date' => gmdate("Y-m-d\TH:i", MakeTimeStamp($arFields['DATE_CREATE'])),
					'text' => html_entity_decode(strip_tags($arFields['DETAIL_TEXT'])),
                    'rating' => $arProps['RATING']['VALUE']
                ];
                $rewiewRating += $arProps['RATING']['VALUE'];
            }
            $rewiewAverage = count($result['reviews']) > 0 ? number_format($rewiewRating / count($result['reviews']), 1) : 0;
            if (!empty($result['reviews'])) {
                $result['rating'] = $rewiewAverage;
                ksort($result);
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
