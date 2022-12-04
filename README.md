## компонент для генерации купонов на скидку

- Для установки:
1.  выполнить миграцию , с помощью модуля https://marketplace.1c-bitrix.ru/solutions/sprint.migration/
2. разместить компонент на странице

$APPLICATION->IncludeComponent("test:sale.generator", "", []);

- Время жизни купона 3 часа
- Демо:
http://test1.958855-cf95282.tmweb.ru/test/
логин - test
пароль - 12345678
