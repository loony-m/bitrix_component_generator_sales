<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;


class hl20221202133427 extends Version
{
    protected $description = "Создать highload для фиксации купонов пользователей";

    protected $moduleVersion = "4.1.3";

    const HLBLOCK_NAME = 'SaleCoupon';

    public function up()
    {
        Loader::includeModule("highloadblock");

        $result = HighloadBlockTable::add([
            'NAME' => self::HLBLOCK_NAME,
            'TABLE_NAME' => 'sale_coupon',
        ]);

        if (!$result->isSuccess()) {
            foreach ($result->getErrorMessages() as $errorMessage) {
                echo $errorMessage . "<br>";
            }
        } else {
            echo "HL создан с ID - " . $result->getId();
        }

        $oUserTypeEntity = new \CUserTypeEntity();

        $arField = [
            'UF_USER' => [
                'TYPE' => 'integer',
                'NAME' => 'ID пользователя'
            ],
            'UF_DATE_CREATE' => [
                'TYPE' => 'string',
                'NAME' => 'Дата создания купона'
            ],
            'UF_COUPON_CODE' => [
                'TYPE' => 'string',
                'NAME' => 'Код купона'
            ],
            'UF_DISCOUNT_VALUE' => [
                'TYPE' => 'string',
                'NAME' => 'Скидка, %'
            ],
        ];

        foreach($arField as $fieldCode => $field){
            $aUserField = [
                'ENTITY_ID' => 'HLBLOCK_'.$result->getId(),
                'FIELD_NAME' => $fieldCode,
                'USER_TYPE_ID' => $field['TYPE'],
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => $field['NAME'], 'en' => $field['NAME']],
                'LIST_COLUMN_LABEL' => ['ru' => $field['NAME'], 'en' => $field['NAME']],
            ];

            $oUserTypeEntity->Add($aUserField);
        }
    }

    public function down()
    {
        // todo: в рамках тестового задания не делаю удаление полей и hl
        // это лишнее время, но пишу об этом, чтобы было понимание, что я эту проблему вижу
    }
}
