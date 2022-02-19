# vipkwd-phputils
A PHP common toolkit.

## 功能
- 常用PHP工具函数库

## 介绍
历经无数个项目沉淀的工具函数，有兴趣的可以一起来维护， 邮箱：service#vipkwd.com

## 环境
- PHP 7.0+
- composer

## 安装使用
```shell
composer require vipkwd/utils
```

## Artisan Command
```shell
yipeng@mbp vipkwd-utils % ./artisan

Vipkwd/utils 1.1.0

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display help for the given command. When no command is given display help for the list command
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi|--no-ansi  Force (or disable --no-ansi) ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  completion  Dump the shell completion script
  dump        Show the class list of Vipkwd/utils package
  help        Display help for a command
  list        List commands
```

### [dump] 查看工具类列表
```shell
yipeng@mbp vipkwd-utils % ./artisan dump
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | -----
1  | Vipkwd\Utils | Algorithm | Et: 10 | #    | #         | #  | 经典排序/查找算法
2  | Vipkwd\Utils | Arr       | Et: 7  | #    | #         | #  | 数组操作
3  | Vipkwd\Utils | Async     | Et: 4  | #    | #         | #  | PHP异步回调
4  | Vipkwd\Utils | Bit       | Et: 8  | #    | #         | #  | 位操作运算
5  | Vipkwd\Utils | Calendar  | Et: 9  | #    | #         | #  | 阴、阳历法
6  | Vipkwd\Utils | Captcha   | Et: 1  | #    | #         | #  | 生成验证码
7  | Vipkwd\Utils | Crypt     | Et: 15 | #    | #         | #  | 加解密组件
8  | Vipkwd\Utils | Date      | Et: 5  | #    | #         | #  | 日期操作
9  | Vipkwd\Utils | Db        | Et: 36 | #    | #         | #  | 数据库驱动
10  | Vipkwd\Utils | Dev       | Et: 7  | #    | #         | #  | 开发调试函数
11  | Vipkwd\Utils | Excel     | Et: 5  | #    | #         | #  | Excel表格工具
12  | Vipkwd\Utils | FFmpeg    | Et: 16 | #    | #         | #  | 媒体处理  
13  | Vipkwd\Utils | Fenci     | Et: 6  | #    | #         | #  | 中文分词组件
14  | Vipkwd\Utils | File      | Et: 19 | #    | #         | #  | 文件操作函数
15  | Vipkwd\Utils | Http      | Et: 2  | #    | #         | #  | http请求  
16  | Vipkwd\Utils | Idcard    | Et: 15 | #    | #         | #  | 证件号码(大陆/港/澳/台) 
17  | Vipkwd\Utils | Image     | Et: 14 | #    | #         | #  | Thinkphp图像处理类（水印,剪裁 ...)
18  | Vipkwd\Utils | Ip        | Et: 8  | #    | #         | #  | 数组操作  
19  | Vipkwd\Utils | Math      | Et: 9  | #    | #         | #  | 数学函数
20  | Vipkwd\Utils | Obj       | Et: 1  | #    | #         | #  | 对象操作
21  | Vipkwd\Utils | Page      | Et: 4  | #    | #         | #  | 通用分页类
22  | Vipkwd\Utils | Position  | Et: 12 | #    | #         | #  | 经纬度操作类
23  | Vipkwd\Utils | RabbitMQ  | Et: 8  | #    | #         | #  | RabbitMQ
24  | Vipkwd\Utils | Random    | Et: 16 | #    | #         | #  | 构建各类有意义的随机数
25  | Vipkwd\Utils | Redis     | Et: 85 | #    | #         | #  | Redis驱动
26  | Vipkwd\Utils | Store     | Et: 2  | #    | #         | #  | 常用工具集合
27  | Vipkwd\Utils | Str       | Et: 24 | #    | #         | #  | 字符串处理函数包
28  | Vipkwd\Utils | Thumb     | Et: 52 | #    | #         | #  | 图像处理类  
29  | Vipkwd\Utils | Tools     | Et: 21 | #    | #         | #  | 常用工具集合
30  | Vipkwd\Utils | Tree      | Et: 5  | #    | #         | #  | 数据树      
31  | Vipkwd\Utils | Validate  | Et: 26 | #    | #         | #  | (regexp)验证类
32  | Vipkwd\Utils | Xml       | Et: 1  | #    | #         | #  | Xml操作     
33  | Vipkwd\Utils | Zip       | Et: 3  | #    | #         | #  | PHP ZipArchive工具包


### [Class] 查看类方法列表
```shell
yipeng@mbp vipkwd-utils % ./artisan dump Arr   
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  1  | Vipkwd\Utils | Arr   | arrayArrRange | static | $input                           | [x] | 排列组合（适用多规格SKU生成）
|  2  | Vipkwd\Utils | Arr   | deepSort      | static | $array,$orderKey,$orderBy="desc" | [√] | 二维数组排序
|  3  | Vipkwd\Utils | Arr   | deepUnique    | static | $arr,$filterKey="id",$cover=true | [√] | 二维数组去重
|  4  | Vipkwd\Utils | Arr   | in            | static | $value,$array                    | [√] | 不区分大小写的in_array实现 
|  5  | Vipkwd\Utils | Arr   | isArray       | static | $arr,$field                      | [√] | 判断数组中指定键是否为数组 
|  6  | Vipkwd\Utils | Arr   | isAssoc       | static | $arr                             | [√] | 是否为关联数组
|  7  | Vipkwd\Utils | Arr   | toXml         | static | $input,$syntax=true              | [√] | 数组转XML



### [ -m method ] 查看方法详细
```shell

yipeng@mbp vipkwd-utils % ./artisan dump Arr -m toXml
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  7  | Vipkwd\Utils | Arr   | toXml  | static | $input,$syntax=true | [√] | 数组转XML

```php
/**
 * 数组转XML
 * 
 * -e.g: $arr=[];
 * -e.g: $arr[]=["name"=>"张叁","roomId"=> "2-2-301", "carPlace"=> ["C109","C110"] ];
 * -e.g: $arr[]=["name"=>"李思","roomId"=> "9-1-806", "carPlace"=> ["H109"] ];
 * -e.g: $arr[]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
 * -e.g: $arr["key"]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
 * -e.g: echo "含语法填充:";
 * -e.g: phpunit("Arr::toXml", [$arr]);
 * -e.g: echo "无语法填充:";
 * -e.g: phpunit("Arr::toXml", [$arr, false]);
 * 
 * @param array $input 数组
 * @param bool $syntax <true> 是否填充xml语法头
 * 
 * @return string
 */
Vipkwd\Utils\Arr::toXml(
    $input
    $syntax=true
)

```



### [--eg] 执行测试用例
```shell

yipeng@mbp vipkwd-utils % ./artisan dump Arr -m toXml --eg
------------------------------------------------------------------------
[01] [•] $arr=[];
[02] [•] $arr[]=["name"=>"张叁","roomId"=> "2-2-301", "carPlace"=> ["C109","C110"] ];
[03] [•] $arr[]=["name"=>"李思","roomId"=> "9-1-806", "carPlace"=> ["H109"] ];
[04] [•] $arr[]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
[05] [•] $arr["key"]=["name"=>"王武","roomId"=> "9-1-807", "carPlace"=> [] ];
[06] 含语法填充:
[07] Vipkwd\Utils\Arr::toXml(Array); // <?xml version="1.0" encoding="utf-8"?><vipkwd len="4"><idx0 len="3"><name>张叁</name><roomId>2-2-301</roomId><carPlace len="2"><idx0>C109</idx0><idx1>C110</idx1></carPlace></idx0><idx1 len="3"><name>李思</name><roomId>9-1-806</roomId><carPlace len="1"><idx0>H109</idx0></carPlace></idx1><idx2 len="3"><name>王武</name><roomId>9-1-807</roomId><carPlace len="0"></carPlace></idx2><key len="3"><name>王武</name><roomId>9-1-807</roomId><carPlace len="0"></carPlace></key></vipkwd>
[08] 无语法填充:
[09] Vipkwd\Utils\Arr::toXml(Array,false); // <vipkwd len="4"><idx0 len="3"><name>张叁</name><roomId>2-2-301</roomId><carPlace len="2"><idx0>C109</idx0><idx1>C110</idx1></carPlace></idx0><idx1 len="3"><name>李思</name><roomId>9-1-806</roomId><carPlace len="1"><idx0>H109</idx0></carPlace></idx1><idx2 len="3"><name>王武</name><roomId>9-1-807</roomId><carPlace len="0"></carPlace></idx2><key len="3"><name>王武</name><roomId>9-1-807</roomId><carPlace len="0"></carPlace></key></vipkwd>
---------------------------------------------------------------------------------------------------- 
yipeng@mbp vipkwd-utils %
```

## 实例

- 更多使用请参照example用例

欢迎`Star`，欢迎`Fork`
