<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$this->addExternalCss($this->__component->__path."/assets/jquery-ui.css");
$this->addExternalJS($this->__component->__path."/assets/jquery-ui.min.js");

if(!empty($arResult['FATAL_ERROR'])){
    foreach ($arResult['FATAL_ERROR'] as $error) {
        ShowMessage($error);
    }
}else{
    if(!empty($arResult['ERROR'])){
        foreach ($arResult['ERROR'] as $error) {
            ShowMessage($error);
        }
    }

    if(!empty($arResult['COUPON']['DISCOUNT_VALUE'])){
        echo '<p>Ваша текущая скидка - ' . $arResult['COUPON']['DISCOUNT_VALUE'] . '%</p>';
        echo '<p>Код купона - ' . $arResult['COUPON']['COUPON_CODE'] . '</p>';
    }
?>
    <form action="" class="mb-3" method="post">
        <input type="hidden" name="generate_code" value="Y">
        <button type="submit" class="btn btn-primary">Получить скидку</button>
    </form>

    <hr>

    <form action="" method="post">
        <div class="form-group mb-3">
            <label for="exampleInput">Код скидки</label>
            <input name="sale_code" class="form-control" id="exampleInput" placeholder="Код скидки">
        </div>
        <button type="submit" class="btn btn-primary">Проверить скидку</button>
    </form>

<? } ?>