
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

## Artisan Command: vipkwd
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd
vipkwd/utils 3.0.0
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
  completion   Dump the shell completion script
  dump         Show the class list of Vipkwd/utils package
  help         Display help for a command
  list         List commands
 load
  load:assets  Install/update assetes for utils
```

### [dump] 查看根空间类列表(默认)
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | -----
|  1  | Vipkwd\Utils | Algorithm | 10     | :)   | :)        | :) | 经典排序/查找算法                        |
|  2  | Vipkwd\Utils | Async     | 4      | :)   | :)        | :) | PHP异步回调                              |
|  3  | Vipkwd\Utils | Calendar  | 9      | :)   | :)        | :) | 阴、阳历法                               |
|  4  | Vipkwd\Utils | Callback  | 11     | :)   | :)        | :) | PHP callable tools                       |
|  5  | Vipkwd\Utils | Color     | 3      | :)   | :)        | :) | Rgb/Hex颜色值处理                        |
|  6  | Vipkwd\Utils | Crypt     | 15     | :)   | :)        | :) | #                                        |
|  7  | Vipkwd\Utils | Dev       | 8      | :)   | :)        | :) | 开发调试函数                             |
|  8  | Vipkwd\Utils | Excel     | 5      | :)   | :)        | :) | Excel表格工具                            |
|  9  | Vipkwd\Utils | Fenci     | 6      | :)   | :)        | :) | 中文分词组件                             |
| 10  | Vipkwd\Utils | Http      | 6      | :)   | :)        | :) | http请求                                 |
| 11  | Vipkwd\Utils | Idcard    | 15     | :)   | :)        | :) | 证件号码(大陆/港/澳/台)                  |
| 12  | Vipkwd\Utils | Ip        | 8      | :)   | :)        | :) | #                                        |
| 13  | Vipkwd\Utils | Page      | 3      | :)   | :)        | :) | 通用分页类                               |
| 14  | Vipkwd\Utils | Position  | 12     | :)   | :)        | :) | 经纬度操作类                             |
| 15  | Vipkwd\Utils | Tools     | 18     | :)   | :)        | :) | 常用工具集合                             |
| 16  | Vipkwd\Utils | Validate  | 32     | :)   | :)        | :) | (regexp)验证类                           |

### [Class] 查看根空间类方法列表
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump color   
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  1  | Vipkwd\Utils | Color | colorHexFix | static | $color | [√] |  16进制色值检测/修补 |
|  2  | Vipkwd\Utils | Color | hex2rgb     | static | $color | [√] |  16进制色值转RGB数值                  |
|  3  | Vipkwd\Utils | Color | rgb2hex     | static | $r=255, $g=255, $b=255 | [√] |  RGB数值转16进制色值  |


### [ --Dir|-d ] 查看子空间列表
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump -d 
```
Idx | Dir | Namespace | Cli 
--- | -------- | ----- | -----
|  1   | Db               | \Vipkwd\Utils\Db               | php vendor/bin/vipkwd dump `Db`          |
|  2   | Image            | \Vipkwd\Utils\Image            | php vendor/bin/vipkwd dump `Image`       |
|  3   | Libs             | \Vipkwd\Utils\Libs             | php vendor/bin/vipkwd dump `Libs`        |
|  4   | MediumAI         | \Vipkwd\Utils\MediumAI         | php vendor/bin/vipkwd dump `MediumAI`    |
|  5   | Mq               | \Vipkwd\Utils\Mq               | php vendor/bin/vipkwd dump `Mq`          |
|  6   | System           | \Vipkwd\Utils\System           | php vendor/bin/vipkwd dump `System`      |
|  7   | Type             | \Vipkwd\Utils\Type             | php vendor/bin/vipkwd dump `Type`        |
|  8   | Wx               | \Vipkwd\Utils\Wx               | php vendor/bin/vipkwd dump `Wx`          |

### [ dump. ] 查看子空间类列表
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump Db 
```
Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  1  | Vipkwd\Utils\Db | Mongo | 31     | :)   | :)        | :) | Mongo                                    |
|  2  | Vipkwd\Utils\Db | Mysql | 36     | :)   | :)        | :) | Mysql                                    |
|  3  | Vipkwd\Utils\Db | Redis | 85     | :)   | :)        | :) | Redis                                    |

### [ dump. ] 查看子空间类方法列表
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump Db.mysql
```
Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  1  | Vipkwd\Utils\Db | Mysql | instance     | static | $options    | [x] |  单例入口      |
|  2  | Vipkwd\Utils\Db | Mysql | action       | public | $callback   | [x] |  启动一个事务                            |
|  3  | Vipkwd\Utils\Db | Mysql | avg          | public |             | [x] |  获得某个列字段的平均值                  |
|  4  | Vipkwd\Utils\Db | Mysql | beginDebug   | public |             | [x] |  开启调试模式                            |
|  5  | Vipkwd\Utils\Db | Mysql | chunk        | public | $limit=10, $callback, $stime=null | [x] |  chunk分块操作数据 |
|  6  | Vipkwd\Utils\Db | Mysql | count        | public |             | [x] |  获取数据表中的行数                      |
|  7  | Vipkwd\Utils\Db | Mysql | data         | public | $data       | [x] |  设置操作目标数据                        |
|  8  | Vipkwd\Utils\Db | Mysql | debugLog     | public |             | [x] |  获取调试模式下SQL语句                   |
|  9  | Vipkwd\Utils\Db | Mysql | delete       | public |             | [x] |  删除表中条件内的数据                    |
| 10  | Vipkwd\Utils\Db | Mysql | field        | public | $fields="*" | [x] |  配置查询字段                            |
| 11  | Vipkwd\Utils\Db | Mysql | get          | public |             | [x] |  返回条件内的一行数据                    |
| 12  | Vipkwd\Utils\Db | Mysql | group        | public | $group      | [x] |  GROUP                                   |
| 13  | Vipkwd\Utils\Db | Mysql | has          | public |             | [x] |  检测条件内数据是否存在                  |
| 14  | Vipkwd\Utils\Db | Mysql | having       | public | $havingArr  | [x] |  having                                  |
| 15  | Vipkwd\Utils\Db | Mysql | info         | public |             | [x] |  获取数据库连接信息                      |
| 16  | Vipkwd\Utils\Db | Mysql | insert       | public | $primaryKey=null | [x] |  插入数据到表中                     |
| 17  | Vipkwd\Utils\Db | Mysql | insertAll    | public |             | [x] |  批量插入数据到表中                      |
| 18  | Vipkwd\Utils\Db | Mysql | join         | public | $join=[]    | [x] |  配置链表关系                            |
| 19  | Vipkwd\Utils\Db | Mysql | last         | public |             | [x] |  获取最后一条查询语句                    |
| 20  | Vipkwd\Utils\Db | Mysql | lastInsertId | public |             | [x] |  返回最后插入的行ID                      |
| 21  | Vipkwd\Utils\Db | Mysql | limit        | public | $limit=10, $offset=0 | [x] |  按偏移量获取limit条数记录      |
| 22  | Vipkwd\Utils\Db | Mysql | log          | public |             | [x] |  获取前序所有SQL                         |
| 23  | Vipkwd\Utils\Db | Mysql | max          | public |             | [x] |  获得某个列中的最大的值                  |
| 24  | Vipkwd\Utils\Db | Mysql | min          | public |             | [x] |  获得某个列中的最小的值                  |
| 25  | Vipkwd\Utils\Db | Mysql | order        | public | $order      | [x] |  order by                                |
| 26  | Vipkwd\Utils\Db | Mysql | page         | public | $page=1, $limit=10 | [x] |  按页码获取limit条数记录          |
| 27  | Vipkwd\Utils\Db | Mysql | pdo          | public |             | [x] |  获取标准PDO接口                         |
| 28  | Vipkwd\Utils\Db | Mysql | query        | public | $sql        | [x] |                                          |
| 29  | Vipkwd\Utils\Db | Mysql | random       | public |             | [x] |  随机获取条件内数据                      |
| 30  | Vipkwd\Utils\Db | Mysql | raw          | public | $expression, $map=[] | [x] |  生成原始SQL表达式优化语句      |
| 31  | Vipkwd\Utils\Db | Mysql | replace      | public | $columns    | [x] |  批量替换字段的数据                      |
| 32  | Vipkwd\Utils\Db | Mysql | select       | public | $callback=null   | [x] |  多条查询，支持回调遍历获取         |
| 33  | Vipkwd\Utils\Db | Mysql | sum          | public |             | [x] |  某个列字段相加                          |
| 34  | Vipkwd\Utils\Db | Mysql | table        | public | $tbName     | [x] |  切换/选择数据表                         |
| 35  | Vipkwd\Utils\Db | Mysql | update       | public | $data=[]    | [x] |  修改表数据                              |
| 36  | Vipkwd\Utils\Db | Mysql | where        | public | $where=[]   | [x] |  设置操作过滤条件                        |

### [ -m method ] 查看方法详细
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump color -m rgb2hex
```

Idx | Namespace | Class | Method | Type | Arguments | Eg | Comment
--- |-------- | ----- | ----- | ----- | ----- | ----- | ---------
|  1  | Vipkwd\Utils | Color | rgb2hex | static | $r=255, $g=255, $b=255 | [√] |  RGB数值转16进制色值 |

```shell
/**
 * RGB数值转16进制色值
 *
 * -e.g: phpunit("Color::rgb2hex",[255,255,255]);
 * -e.g: phpunit("Color::rgb2hex",[1,10,100]);
 * -e.g: phpunit("Color::rgb2hex",[9,0,1]);
 *
 * @param integer $r
 * @param integer $g
 * @param integer $b
 * @return string
 */
Struct: \Vipkwd\Utils\Color::rgb2hex(
    $r=255,
    $g=255,
    $b=255
);
```

### [--eg|-e ] 执行测试用例
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump color -m rgb2hex --eg

[01] \Vipkwd\Utils\Color::rgb2hex(255, 255, 255); //<string:>“#ffffff”
[02] \Vipkwd\Utils\Color::rgb2hex(1, 10, 100); //<string:>“#010a64”
[03] \Vipkwd\Utils\Color::rgb2hex(9, 0, 1); //<string:>“#090001”
------------------------------------------------------------------------------------

yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump ip -m getInfo --eg

[01] \Vipkwd\Utils\Ip::getInfo("1.2.4.8"); //array(8) {
    [state] =>    “中国”
    [region] =>   “-”
    [province] => “北京”
    [city] =>     “北京市”
    [isp] =>      “CNNIC权威云解析(CDNS.CN)全球Anycast节点”
    [ip] =>       “1.2.4.8”
    [beginip] =>  “1.2.4.0”
    [endip] =>    “1.2.4.255”
}
[02] \Vipkwd\Utils\Ip::getInfo("127.0.0.1"); //array(8) {
    [state] =>    “-”
    [region] =>   “-”
    [province] => “-”
    [city] =>     “内网IP”
    [isp] =>      “内网IP”
    [ip] =>       “127.0.0.1”
    [beginip] =>  “127.0.0.1”
    [endip] =>    “127.0.0.1”
}
[03] \Vipkwd\Utils\Ip::getInfo("120.235.131.155"); //array(8) {
    [state] =>    “中国”
    [region] =>   “-”
    [province] => “广东省”
    [city] =>     “惠州市”
    [isp] =>      “移动”
    [ip] =>       “120.235.131.155”
    [beginip] =>  “120.235.129.0”
    [endip] =>    “120.235.141.255”
}
[04] \Vipkwd\Utils\Ip::getInfo("236.230.35.38/29"); //array(8) {
    [state] =>    “-”
    [region] =>   “-”
    [province] => “-”
    [city] =>     “内网IP”
    [isp] =>      “内网IP”
    [ip] =>       “236.230.35.38”
    [beginip] =>  “225.0.0.0”
    [endip] =>    “239.255.255.255”
}
------------------------------------------------------------------------------------
```

### 无效空间/类
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump 10.musume.com
[Notice] Undefined constant "\Vipkwd\Utils\10\Musume\Com::class" with /data/wwwroot/10musume.com/vendor\vipkwd\utils/src/10/Musume/Com.php

yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump porn
[Notice] Undefined constant "\Vipkwd\Utils\Porn::class" with /data/wwwroot/10musume.com/vendor\vipkwd\utils/src/Porn.php
```

### [load::assets ] 下载/更新静态资源
```shell
yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd dump 10.musume.com
[Notice] Undefined constant "\Vipkwd\Utils\10\Musume\Com::class" with /data/wwwroot/10musume.com/vendor\vipkwd\utils/src/10/Musume/Com.php

yipeng@mbp vipkwd-framework % php vendor/bin/vipkwd load::assets
-> 1 Update ttfs/1.ttf ································ (Skiped)
-> 2 Update ttfs/2.ttf ································ (Skiped)
-> 3 Update ttfs/3.ttf ································ (Skiped)
-> 4 Update ttfs/4.ttf ································ (Skiped)
-> 5 Update ttfs/5.ttf ································ (Skiped)
-> 6 Update ttfs/6.ttf ································ (Skiped)
-> 7 Update ttfs/msyh.ttf ··························· ··· (Skiped)
-> 8 Update qqwry.dat ······················· ········· (Skiped)
```

## 实例

- 更多使用请参照example用例

欢迎`Star`，欢迎`Fork`