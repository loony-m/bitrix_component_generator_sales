<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Context;
use Bitrix\Highloadblock as HL;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Sale\Internals\DiscountGroupTable;
use Bitrix\Sale\Internals\DiscountCouponTable;

class TestSaleGenerator extends CBitrixComponent {

    const HLBLOCK_NAME = 'SaleCoupon';
    const MAX_HOURS_LIFE_COUPON = 3;

    private function _checkLogin(): void {
        global $USER;
        if (!$USER->IsAuthorized()) {
            $this->arResult['FATAL_ERROR'][] = 'Форма для получения скидки, доступна авторизованному пользователю';
        }
    }

    private function _checkHl(): void
    {
        $hlID = self::_getIdHl();

        if(empty($hlID)){
            $this->arResult['FATAL_ERROR'][] = 'Отсутствует highloadblock с именем '.self::HLBLOCK_NAME;
        }
    }

    private function _getIdHl()
    {
        Loader::includeModule("highloadblock");

        $hlblockImportTable = HighloadBlockTable::getList([
            'select' => ['ID'],
            'filter' => ['=NAME' => self::HLBLOCK_NAME],
        ])->fetch();

        return $hlblockImportTable['ID'];
    }

    private function _getDateNow()
    {
        return date('d.m.Y H:i');
    }

    public function createCoupon()
    {
        global $USER, $APPLICATION;
        Loader::includeModule('sale');
        Loader::includeModule('catalog');

        $arResult = [];
        $discount = rand(1, 50);
        $arGroup = [1, 6];

        $arDiscountFields = [
            'LID' => SITE_ID,
            'SITE_ID' => SITE_ID,
            'NAME'=> '[user_'.$USER->GetID().'] Генератор скидок, '.$discount.'%',
            'DISCOUNT_VALUE' => $discount,
            'DISCOUNT_TYPE' => 'P',
            'LAST_LEVEL_DISCOUNT' => 'Y',
            'LAST_DISCOUNT' => 'Y',
            'ACTIVE' => 'Y',
            'CURRENCY' => 'RUB',
            'USER_GROUPS' => $arGroup,
        ];

        // todo: в рамках тестового задания не делаю проверку на существование скидки и просто добавления купона
        // это лишнее время, но пишу об этом, чтобы было понимание, что я эту проблему вижу
        $discountID = \CSaleDiscount::Add($arDiscountFields);

         if(!empty($discountID)) {
             DiscountGroupTable::updateByDiscount($discountID, $arGroup, 'Y',true);

             $couponCode = CatalogGenerateCoupon();

             $arCouponFields = [
                 'DISCOUNT_ID' => $discountID,
                 'ACTIVE' => 'Y',
                 'TYPE' => DiscountCouponTable::TYPE_ONE_ORDER,
                 'COUPON' => $couponCode,
                 'USER_ID' => $USER->GetID(),
                 'MAX_USE' => 1
             ];

             $coupon = DiscountCouponTable::add($arCouponFields);

             if ($coupon->isSuccess()) {
                 $arResult['DISCOUNT_VALUE'] = $discount;
                 $arResult['COUPON_CODE'] = $couponCode;
                 $arResult['COUPON_CREATE_DATE'] = self::_getDateNow();
                 $arResult['USER_ID'] = $USER->GetID();
             }else{
                 $exception = $APPLICATION->GetException();
                 $this->arResult['ERROR'][] = $exception->GetString();
             }
         }else{
             $this->arResult['ERROR'][] = 'Не удалось создать скидку, приносим Вам свои извенения. Попробуйте позже';
         }

         return $arResult;
    }

    public function saveCoupon($arCoupon): void
    {
        Loader::includeModule("highloadblock");

        if(!empty($arCoupon)){
            $hlID = self::_getIdHl();
            $hlblock = HL\HighloadBlockTable::getById($hlID)->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $dataClass = $entity->getDataClass();

            $arField = [
                'UF_USER' => $arCoupon['USER_ID'],
                'UF_DATE_CREATE' => $arCoupon['COUPON_CREATE_DATE'],
                'UF_COUPON_CODE' => $arCoupon['COUPON_CODE'],
                'UF_DISCOUNT_VALUE' => $arCoupon['DISCOUNT_VALUE'],
            ];

            $arResult = $dataClass::add($arField);

            if(!$arResult->isSuccess()){
                $this->arResult['ERROR'][] = implode('<br>', $arResult->getErrorMessages());
            }
        }
    }

    public function checkUserCoupon($couponCode = null)
    {
        Loader::includeModule("highloadblock");

        global $USER;
        $arResult = [];

        $hlID = self::_getIdHl();
        $hlblock = HL\HighloadBlockTable::getById($hlID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $dataClass = $entity->getDataClass();

        if($couponCode !== null){
            $filter = ['UF_USER' => $USER->GetID(), 'UF_COUPON_CODE' => $couponCode];
        }else{
            $filter = ['UF_USER' => $USER->GetID()];
        }
        $dbCoupon = $dataClass::getList([
            'select' => ['DISCOUNT_VALUE' => 'UF_DISCOUNT_VALUE', 'COUPON_CODE' => 'UF_COUPON_CODE', 'UF_DATE_CREATE'],
            'filter' => $filter
        ]);

        if ($arCoupon = $dbCoupon->Fetch()) {
            $dateCoupon = $arCoupon['UF_DATE_CREATE'];
            $dateNow = self::_getDateNow();

            $dateCouponTs = strtotime($dateCoupon);
            $dateNowTs = strtotime($dateNow);

            $seconds = abs($dateCouponTs - $dateNowTs);
            $hours = floor($seconds / 3600);

            $arResult = $arCoupon;

            if ($hours <= self::MAX_HOURS_LIFE_COUPON) {
                $arResult['EXPIRED'] = 'N';
            } else {
                $arResult['EXPIRED'] = 'Y';

                // todo: в рамках тестового задания не делаю очистку истёкших кодов и скидок
                // это лишнее время, но пишу об этом, чтобы было понимание, что я эту проблему вижу
            }
        }

        return $arResult;
    }

    public function executeComponent() {
        $this->_checkLogin();
        $this->_checkHl();

        $request = Context::getCurrent()->getRequest();


        if($request['generate_code']){
            $arResultCheck = self::checkUserCoupon();

            if($arResultCheck['EXPIRED'] == 'N'){
                $arCoupon = $arResultCheck;
            }else{
                $arCoupon = self::createCoupon();
                self::saveCoupon($arCoupon);
            }

            $this->arResult['COUPON'] = $arCoupon;

        }elseif($request['sale_code']){
            $arResultCheck = self::checkUserCoupon($request['sale_code']);

            if($arResultCheck['EXPIRED'] == 'N') {
                $arCoupon = $arResultCheck;
                $this->arResult['COUPON'] = $arCoupon;
            }else{
                $this->arResult['ERROR'][] = 'Скидка недоступна';
            }
        }

        $this->includeComponentTemplate();
    }
}