<?php
/**
 * @name 收件地址智能解析
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs\SmartParsePro;
use \Exception,\Vipkwd\Utils\Dev,\Vipkwd\Utils\Libs\RandomName;

// http://wzhichao.gitee.io/smartparse/#/smartParse/import/es5
// http://wzhichao.gitee.io/smartparsepro/js/address_parse.js
// http://wzhichao.gitee.io/smartparsepro/js/zipCode.js
// http://wzhichao.gitee.io/smartparsepro/js/pcasCode.js
class Address {

    // 地址列表
    private static $addressList = [];
    // 邮编列表
    private static $zipCodeList = [];

    private $smartObj = [];

    private static $prefixSepter = "*%~.ca";

    public function __construct(){

        $this->bcityList = ["北京市","天津市","上海市","重庆市"];

        $this->childNodeName = [
            "zipCode" => 'child',
            "cityList"  => "children",
        ];
        $this->nodeKey = [
            "zipCode" => "zipcode",
            "cityTitle" => "name",
            "cityCode"  =>"code"
        ];
    }

    /**
     * 解析邮编
     * @param string $addrString 识别的地址
     * @returns <obj>
     */
    public function smart($addrString) {

        $this->loadData();

        $event = $addrString;
        $_phone = [];
        $obj = [];
        // $obj['_str'] = $addrString;
        //TODO 
        if(preg_match_all("/((\d{2,4}[-_－—])\d{3,8}([-_－—]?\d{3,8})?([-_－—]?\d{1,7})?)|(0?1[3-9]\d{9})/", $addrString, $match ,PREG_SET_ORDER)){
            if( strlen($match[0][0]) ){
                //$_phone = $match[0];
                //$addrString = str_replace($match[0][0], '', $addrString);
            }
        }
        //过滤特殊字符
        $addrString = preg_replace("/\ +/"," ", $this->stripscript($addrString));
        $copyAddress = explode(' ', $addrString);
        $familyNameList = RandomName::getFamilyNameList();

        $remarks = [];
        $names = [];
        $remarkIndex = 0;
        foreach($copyAddress as $res){
            $res = trim($res);
            if( $res != "" ){
                if(mb_strlen($res) == 1){
                    $res .= "XX";
                }
                $addressObj = $this->smartAddress($res);
                $obj = array_merge($obj, $addressObj);

                if(empty($addressObj)){
                    $likeName=  (
                        mb_strlen($res) >=2 && mb_strlen($res) <= 4
                    ) && (
                        /*单姓*/in_array(mb_substr($res,0,1), $familyNameList['sin']) ||
                        /*复姓*/in_array(mb_substr($res,0,2), $familyNameList['sur'])
                    );
                    if($likeName){
                        $names[$remarkIndex] = str_replace('XX','', mb_substr($res, 0));
                    }else{
                        $remarks[$remarkIndex] = str_replace('XX','', mb_substr($res, 0));
                    }
                    $remarkIndex++;
                }
            }
        }
        if(!empty($names)){
            //
            $obj['name'] = array_pop($names);
            if(!empty($names)){
                foreach( $names as $k => $v){
                    $remarks[$k] = $v;
                }
            }
        }
        if(!empty($remarks)){
            ksort($remarks);
            $obj['remark'] = implode(" ", $remarks);
        }

        // ksort($obj);
        // if (!isset($obj['phone']) && $_phone) {
        //     foreach($_phone as $phone){
        //         if(mb_strlen($phone) >=8 && !isset($obj['phone'])){
        //             $obj['phone'] = $phone;
        //         }else{
        //             $obj['address'] .= " ".$phone;
        //         }
        //     }
        // }
        $obj['__text'] = $event;
        return $obj;
    }

    /**
     * 验证身份证号
     *
     * @param string $idn
     * @return boolean
     */
    public function identityValidate(string $idn): bool{
        $pass = true;
        $city = [
            11 =>"北京",12 =>"天津",13 =>"河北",14 =>"山西",15 =>"内蒙古",21 =>"辽宁",22 =>"吉林",23 =>"黑龙江 ",31 =>"上海",32 =>"江苏",
            33 =>"浙江",34 =>"安徽",35 =>"福建",36 =>"江西",37 =>"山东",41 =>"河南",42 =>"湖北 ",43 =>"湖南",44 =>"广东",45 =>"广西",
            46 =>"海南",50 =>"重庆",51 =>"四川",52 =>"贵州",53 =>"云南",54 =>"西藏 ",61 =>"陕西",62 =>"甘肃",63 =>"青海",64 =>"宁夏",
            65 =>"新疆",71 =>"台湾",81 =>"香港",82 =>"澳门",91 =>"国外 ",
        ];
        $tip = "";
        if (!$idn || !preg_match("/^[1-9]\d{16}(\d|X)$/i",$idn) ) {
            $tip = "身份证号格式错误";
            $pass = false;
        } else if (!isset($city[ (substr($idn, 0, 2)) ]) ) {
            $tip = "地址编码错误";
            $pass = false;
        } else {
            //18位身份证需要验证最后一位校验位
            if (strlen($idn) == 18) {
                $idn = str_split(str_replace('x', 'X',$idn));
                //∑(ai×Wi)(mod 11)
                //加权因子
                $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                //校验位
                $parity = [1, 0, "X", 9, 8, 7, 6, 5, 4, 3, 2];
                $sum = $ai = $wi = 0;
                for ($i = 0; $i < 17; $i++) {
                    $ai = $idn[$i];
                    $wi = $factor[$i];
                    $sum += $ai * $wi;
                }
                $last = $parity[ $sum % 11];
                if ($parity[$sum % 11] != $idn[17]) {
                    $tip = "校验位错误";
                    $pass = false;
                }
            }
        }
        return $pass;
    }
    
    private function smartAddress($address) {
        $this->smartObj = [
            "province" => "",
            "provinceCode" => "",
            "city" => "",
            "cityCode" => "",
            "county" => "",
            "countyCode" => "",
            "street" => "",
            "streetCode" => "",
            "idn" => "",
            "phone" => "",
            "telephone" => "",
            "zipCode" => "",
        ];
        //身份证号匹配
        if ($this->identityValidate($address)) {
            $this->smartObj['idn'] = $address;
            $address = "";
            return $this->filter();
        }
        //电话匹配
        if (preg_match("/(\+?0?86\-1[3-9]\d{9})|(\+?0?861[3-9]\d{9})|(1[3-9]\d{9})/", $address, $match)) {
            $this->smartObj['phone'] = $match[0];
            $address = str_replace($match[0], "", $address);
        }
        //TODO 座机
        if (preg_match("/(\(0[1-9]\d{1,2}\)|(0[1-9]\d{1,2}))[\ \-]?\d{7,8}([\ \-]\d{1,6})?/", $address, $match)) {
            // 0915-2811369
            // 0915-28113690
            // 091528113690
            // 020-2811369
            // 020-28113690
            // (020)28113690
            // (020)28113690-1
            $this->smartObj['telphone'] = $match[0];
            $address = str_replace($match[0], "", $address);
        }
        unset($match);

        //邮编匹配
        foreach(self::$zipCodeList as $item){
            $pos = mb_stripos($address, $item);
            if( $pos !== false){
                $_address = mb_substr($address, 0, $pos);
                $this->smartObj['zipCode'] = mb_substr($address, $pos, $pos +6);
                $address = $_address . mb_substr($address, $pos+6 );
                unset($_address, $pos);
            }
        }
        // 省查找
        $this->findProvince($address);

        // 市查找
        $this->findCity($address);

        // 区县查找
        $this->findCounty($address);

        // 城镇/街道查找
        $this->findStreet($address);

        // 姓名查找
        if ( $this->smartObj['province']) {
            //TODO 
            $this->smartObj['address'] = $address;
        }
        return $this->filter();
    }

    private function findProvince(&$address){

        //省匹配 比如输入北京市朝阳区，会用北  北京  北京市 北京市朝 以此类推在addressList里的province中做匹配，会得到北京市 或 河北省 或 天津市等等；
        $match = [];
        $snippets = "";
        //粗略匹配上的省份
        for($endIndex = 0; $endIndex < mb_strlen($address); $endIndex++) {
                $snippets = mb_substr($address, 0, $endIndex + 2);
                foreach(self::$addressList as $res){
                    if(mb_stripos( $res["province"], $snippets) === 0){

                        empty($match) && $match = [
                            "province" => $res["province"],
                            "provinceCode" => $res[$this->nodeKey['cityCode']]
                        ];

                        $match["matchValue"] = $snippets;
                    }
                }
        }
        if(!empty($match)){
            $this->smartObj['province'] = $match['province'];
            $this->smartObj['provinceCode'] = $match['provinceCode'];
            $address = str_replace(self::$prefixSepter . $match['matchValue'], "", self::$prefixSepter . $address);
        }
        unset($match, $snippets, $endIndex);
    }

    private function findCity(&$address){
        //粗略匹配上的市
        $match = [];
        $snippets = "";
        for ($endIndex = 0; $endIndex < mb_strlen($address); $endIndex++) {
            $snippets = mb_substr($address, 0, $endIndex + 2);
            foreach(self::$addressList as $item){
                //  if ($item['name'] == $this->smartObj['province']) {
                        //前序没有匹配到省级 或 当前节点 == 前序已匹配省份
                        if ( $this->smartObj['provinceCode'] == "" || $item[$this->nodeKey['cityCode']] == $this->smartObj['provinceCode']) {
                            //前序匹配省级为直辖市
                            if ( $this->smartObj['province'] && in_array($this->smartObj['province'],$this->bcityList)) {
                                
                                foreach($item[$this->childNodeName['cityList']] as $itm){
                                    foreach($itm[$this->childNodeName['cityList']] as $res){
                                        $snippets = str_replace(self::$prefixSepter.$this->smartObj['province'], "", self::$prefixSepter.$snippets);
                                        $snippets = str_replace(self::$prefixSepter, "", $snippets);
                                        if($snippets && mb_stripos($res['county'], $snippets) === 0){
                                            // Dev::dump([$snippets, $res['county']]);

                                            empty($match) && $match = [
                                                "province"      => $item['province'],
                                                "provinceCode"  => $item[$this->nodeKey['cityCode']],
                                                "city"          => $itm['city'],
                                                "cityCode"      => $itm[$this->nodeKey['cityCode']],
                                                "county"        => $res['county'],
                                                "countyCode"    => $res[$this->nodeKey['cityCode']],
                                            ];

                                            $match['matchValue'] = $snippets;
                                        }
                                        unset($res);
                                    }
                                    unset($itm);
                                }  
                            }else{
                                //枚举城市
                                foreach($item[$this->childNodeName['cityList']] as $res){
                                    // unset($res['children']);
                                    $flag = false;
                                    if(isset($res['alias']) && !empty($res['alias'])){
                                        //节点已优化 城市名称
                                        if(in_array($snippets, $res['alias'])){
                                            $flag = true;
                                            $res['city'] = $snippets;
                                        }
                                    }else if( mb_stripos($res["city"], $snippets) === 0) {
                                        $flag = true;
                                    }
                                    if($flag){
                                        empty($match) && $match = [
                                            "province"      => $item["province"],
                                            "provinceCode"  => $item[$this->nodeKey['cityCode']],
                                            "city"          => $res["city"],
                                            "cityCode"      => $res[$this->nodeKey['cityCode']]
                                        ];
                                        $match['matchValue'] = $snippets;
                                    }
                                    unset($res);
                                }
                            }
                        }
                // }
                unset($item);
            }
        }
        if(!empty($match)){
            if ($this->smartObj['provinceCode'] == "") {
                $this->smartObj['province']     = $match['province'];
                $this->smartObj['provinceCode'] = $match['provinceCode'];
            }
            $this->smartObj['city']         = $match['city'];
            $this->smartObj['cityCode']     = $match['cityCode'];
            $this->smartObj['county']       = $match['county'] ?? "";
            $this->smartObj['countyCode']   = $match['countyCode'] ?? "";
            
            $address = str_replace(self::$prefixSepter . $this->smartObj['province'].$match['matchValue'],"",self::$prefixSepter."$address");
            $address = str_replace(self::$prefixSepter . $match['matchValue'],"",self::$prefixSepter.$address);
            $address = str_replace(self::$prefixSepter, "", $address);
        }
        unset($match, $snippets, $endIndex);
    }

    private function findCounty(&$address){
        //粗略匹配上的区县
        $match = [];
        $snippets = "";
        for ($endIndex = 0; $endIndex < mb_strlen($address); $endIndex++){
            $snippets = mb_substr($address, 0, $endIndex + 2);
            foreach(self::$addressList as $el){
                // 前序没有匹配到省级 或 当前节点 == 前序已匹配省份
                if ($el['province'] == $this->smartObj['province'] || $this->smartObj['province'] == "" ) {

                    //不是直辖市
                    if ( !in_array($this->smartObj['province'],$this->bcityList)) {

                        foreach($el[$this->childNodeName['cityList']] as $item){
                            // 定位城市
                            // 原理：前序已定位省份和城市 的前提下: 如果枚举城市(也包括别名)与前序城市不匹配,将直接忽略(foreach ··· continue)
                            if(
                                (( !isset($item['alias']) && $item['city'] != $this->smartObj['city']) || 
                                (isset($item['alias']) && !in_array($this->smartObj['city'], $item['alias']) )) &&
                                ($this->smartObj['province'] && $this->smartObj['city'])
                            ){
                                continue;
                            }
                            // 已定位到城市
                            foreach($item[$this->childNodeName['cityList']] as $res){
                                if(mb_stripos($res['county'], $snippets,) === 0){
                                    //含有 省
                                    if ($this->smartObj['province'] != "") {

                                        // 市级匹配或 没有市级
                                        if ( 
                                            ($this->smartObj['city'] && mb_substr($res['code'], 0, 4) == $this->smartObj['cityCode']) || 
                                            !$this->smartObj['city']
                                        ) {
                                            empty($match) && $match = [
                                                "province"      => $el['province'],
                                                "provinceCode"  => $el['code'],
                                                "city"          => $item['city'],
                                                "cityCode"      => $item['code'],
                                                "county"        => $res['county'],
                                                "countyCode"    => $res['code']
                                            ];

                                            $match['matchValue'] = $snippets;
                                        }
                                    }else if (!$this->smartObj['province'] && !$this->smartObj['city']) {
                                        empty($match) && $match = [
                                            "province"      => $el['province'],
                                            "provinceCode"  => $el['code'],
                                            "city"          => $item['city'],
                                            "cityCode"      => $item['code'],
                                            "county"        => $res['county'],
                                            "countyCode"    => $res['code']
                                        ];

                                        $match['matchValue'] = $snippets;
                                    }
                                }
                                unset($res);
                            }
                            unset($item);
                        }
                    }
                }
                unset($el);
            }
        }
        if(!empty($match)){
            if ($this->smartObj['province'] == "") {
                $this->smartObj['province']     = $match['province'];
                $this->smartObj['provinceCode'] = $match['provinceCode'];
            }
            if ($this->smartObj['city']== "") {
                $this->smartObj['city']         = $match['city'];
                $this->smartObj['cityCode']     = $match['cityCode'];
            }
            $this->smartObj['county']       = $match['county'];
            $this->smartObj['countyCode']   = $match['countyCode'];
            $address = str_replace(self::$prefixSepter . $match['matchValue'], "", self::$prefixSepter . $address);
        }
        unset($match, $snippets, $endIndex);
    }

    private function findStreet(&$address){

        $match = []; //粗略匹配上的街道查
        $snippets = "";
        for ($endIndex = 0; $endIndex < mb_strlen($address); $endIndex++) {
            $snippets = mb_substr($address, 0, $endIndex + 3);
            foreach(self::$addressList as $provinceEl){
                // 定位省份
                if ( $provinceEl['name'] == $this->smartObj['province']) {
                    //不是直辖市
                    if (!in_array($this->smartObj['province'],$this->bcityList)) {
                        foreach($provinceEl[$this->childNodeName['cityList']] as $cityEl){
                            // 定位城市
                            if($cityEl['name'] == $this->smartObj['city']){
                                foreach($cityEl[$this->childNodeName['cityList']] as $countyEl){
                                    // 定位区县
                                    if($countyEl['name'] == $this->smartObj['county']){
                                        // 遍历城镇/街道
                                        foreach($countyEl[$this->childNodeName['cityList']] as $res){

                                            if(mb_stripos($res['street'], $snippets) === 0){
                                                $match = [
                                                    "street"        => $res['street'],
                                                    "streetCode"    => $res['code'],
                                                    "matchValue"    => $snippets,
                                                ];
                                            }
                                            unset($res);
                                        }
                                    }
                                    unset($countyEl);
                                }
                            }
                            unset($cityEl);
                        }
                    }
                }
                unset($provinceEl);
            }
        }
        if(!empty($match)){
            $this->smartObj['street'] = $match['street'];
            $this->smartObj['streetCode'] = $match['streetCode'];
            $address = str_replace( self::$prefixSepter . $match['matchValue'], "", self::$prefixSepter . $address);

            if(mb_substr($this->smartObj['street'], -1) == "镇"){
                $this->smartObj['townstreet'] = "1";
            }
        }
        unset($match, $snippets, $endIndex);
    }

    private function loadData(){
        if(empty(self::$addressList)){
            $addressList = json_decode( file_get_contents(__DIR__.'/cityList.json'), true);
            foreach($addressList as $k => $item){
                $addressList[$k] = $this->formatAddresList($item, 1, []);
            }
            self::$addressList = $addressList;
            // Dev::dump($addressList,1);
            unset($addressList);
        }
        
        if(empty(self::$zipCodeList)){
            $zipCode = json_decode( file_get_contents(__DIR__.'/zipCode.json'), true);
            self::$zipCodeList = $this->zipCodeFormat($zipCode);
            // Dev::dump(self::$zipCodeList,1);
            unset($zipCode);
        }
    }

    /**
     * 地址数据处理
     * 
     * @param array $addressItem 各级数据对象
     * @param integer $idx 对应的省/市/县区/街道
     * @param array $province 只有直辖市会处理为  北京市北京市
     * 
     * @return array
     */
    private function formatAddresList(array $addressItem, int $idx, array $province = []):array{
        if ($idx === 1) {
            //省
            $addressItem['province'] = $addressItem['name'];
            $addressItem['type'] = "province";
        } elseif ($idx === 2) {
            //市
            if ($addressItem['name'] == "市辖区") {
                $addressItem['name'] = $province['name'];
            }
            $addressItem['city'] = $addressItem['name'];
            $addressItem['type'] = "city";
        } elseif ($idx === 3) {
            //区或者县
            $addressItem['county'] = $addressItem['name'];
            $addressItem['type'] = "county";
        } elseif ($idx === 4) {
            //街道
            $addressItem['street'] = $addressItem['name'];
            $addressItem['type'] = "street";
        }
        if (isset($addressItem[$this->childNodeName['cityList']])) {
            $idx++;
            foreach($addressItem[$this->childNodeName['cityList']] as $k => $item){
                $addressItem[$this->childNodeName['cityList']][$k] = $this->formatAddresList($item, $idx, $addressItem);
            }
        }
        return $addressItem;
    }

    /**
     * 解析邮编
     *
     * @param array $zipCode
     * @return array
     */
    private function zipCodeFormat(array $zipCode):array{
        $list = [];
        foreach($zipCode as $item){
            if(isset($item[$this->childNodeName['zipCode']]) && $item[$this->childNodeName['zipCode']]){
                foreach($item[$this->childNodeName['zipCode']] as $zipItem){
                    if(isset($zipItem[$this->childNodeName['zipCode']]) && $zipItem[$this->childNodeName['zipCode']]){
                        foreach($zipItem[$this->childNodeName['zipCode']] as $elem){
                            $list[] = $elem[$this->nodeKey['zipCode']];
                        }
                    }
                }
            }
        }
        return $list;
    }

    //过滤特殊字符
    private function stripscript(string $s):string{
        $s = preg_replace("/\s+/", ' ', $s);
        $s = str_replace(['（','）'], [' (',') '], $s);
        $s = str_replace(["，","。","、","；","‘","’","：","“","”"],' ', $s);
        $s = preg_replace("/(\d{3})([\-|\ ])(\d{4})\\2(\d{4})/", "$1$3$4", $s);
        $s = preg_replace("/[^A-Za-z0-9\-\x{4E00}-\x{9FFF}]\(\)/u", ' ', $s);
        $s = preg_replace("/\ +/", ' ', $s); //去除空格
        return preg_replace("/[\r\n]/", '', $s);
    }

    private function filter(){
        foreach($this->smartObj as $field => $v){
            if(trim($v) == "") unset($this->smartObj[$field]);
        }
        return $this->smartObj;
    }

}