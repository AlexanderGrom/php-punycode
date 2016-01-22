<?php

require 'punycode.php';

class PunycodeTests extends PHPUnit_Framework_TestCase
{
    private $punycode = null;

    protected function setUp()
    {
        $this->punycode = new Punycode();
    }

    protected function tearDown()
    {
        $this->punycode = null;
    }

    /**
     * Список доменов взят из
     * https://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Test_TLDs
     */
    public function domainsDataProvider()
    {
        return array(
             ['مثال.إختبار', 'xn--mgbh0fb.xn--kgbechtv'],
             ['مثال.آزمایشی', 'xn--mgbh0fb.xn--hgbk6aj7f53bba'],
             ['例子.测试', 'xn--fsqu00a.xn--0zwm56d'],
             ['例子.測試', 'xn--fsqu00a.xn--g6w251d'],
             ['пример.испытание', 'xn--e1afmkfd.xn--80akhbyknj4f'],
             ['उदाहरण.परीक्षा', 'xn--p1b6ci4b4b3a.xn--11b5bs3a9aj6g'],
             ['παράδειγμα.δοκιμή', 'xn--hxajbheg2az3al.xn--jxalpdlp'],
             ['실례.테스트', 'xn--9n2bp8q.xn--9t4b11yi5a'],
             ['בײַשפּיל.טעסט', 'xn--fdbk5d8ap9b8a8d.xn--deba0ad'],
             ['例え.テスト', 'xn--r8jz45g.xn--zckzah'],
             ['உதாரணம்.பரிட்சை', 'xn--zkc6cc5bi7f6e.xn--hlcj6aya9esc7a'],
        );
    }

    /**
     * @dataProvider domainsDataProvider
     */
    public function testEncode($domain, $expected)
    {
        $result = $this->punycode->encode($domain);
        $this->assertEquals($result, $expected);
    }

    /**
     * @dataProvider domainsDataProvider
     */
    public function testDecode($expected, $domain)
    {
        $result = $this->punycode->decode($domain);
        $this->assertEquals($result, $expected);
    }
}
