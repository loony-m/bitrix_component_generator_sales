# Генератор случайных купонов

## Описание
Компонент для 1C-Bitrix. Позволяет сгенерировать случайный купон на скидку. Время жизни купона 3 часа

## Для запуска

1. Выполнить миграцию, с помощью модуля https://marketplace.1c-bitrix.ru/solutions/sprint.migration/
2. Разместить компонент на странице
```
$APPLICATION->IncludeComponent("test:sale.generator", "", []);
```
