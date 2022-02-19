<?php
/**
 * @name Xml操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);
namespace Vipkwd\Utils;

class Xml{

    /**
     * XML转数组
     * 
     * -e.g: phpunit("Xml::toArray", ["<vipkwd><a>110</a><b>120</b><c><d>true</d></c></vipkwd>"]);
     * 
     * @param string $xml xml
     *
     * @return array
     */
    static function toArray(string $xml): array{
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlString = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $result = json_decode(json_encode($xmlString), true);
        return $result;
    }
}