
## PHP-Pynycode - Преобразует имена доменов в формат Punycode и обратно. [![Build Status](https://travis-ci.org/AlexanderGrom/php-punycode.svg?branch=master)](https://travis-ci.org/AlexanderGrom/php-punycode)

Основывается на спецификации [RFC 3492](http://tools.ietf.org/html/rfc3492)  (Punycode: A Bootstring encoding of Unicode for Internationalized Domain Names in Applications (IDNA)).

Данная библиотека используется мной на сайте [DigHub.ru](http://dighub.ru) для преобразования интернациональных доменных имен в формат Punycode и обратно.

### Возможности

* Кодирует имена доменов в формат Punycode;
* Декодирует имена доменов в формате Pynucode.

### Требования

* UTF-8

### Пример

```php
require('punycode.php');

$punycode = new Punycode();

$domainEncode = $punycode->encode("مثال.إختبار"); // xn--mgbh0fb.xn--kgbechtv
$domainDecode = $punycode->decode("xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g"); // उदाहरण.परीक्षा
```

### Тест

Вы можете запустить тест представленный в файле test.php. При успешном выполение на экране браузера должно появиться "Ok".

## -~- THE END -~-
