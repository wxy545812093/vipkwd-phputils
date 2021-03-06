<?php
/**
 * @name Excel表格工具
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use PhpOffice\PhpSpreadsheet\Cell\{Coordinate,DataType};
use PhpOffice\PhpSpreadsheet\{IOFactory,Spreadsheet};
use PhpOffice\PhpSpreadsheet\Reader\{Xls,Xlsx};
use PhpOffice\PhpSpreadsheet\Style\{Alignment,Border,Color,Fill,NumberFormat};
use PhpOffice\PhpSpreadsheet\Worksheet\{PageSetup,Drawing};
use Phpoffice\PhpSpreadsheet\Shared\Date;

class Excel{

    private static $sheetMaxColumnName;
    private static $sheetColumnNames;
    private static $currentSerialKey;
    private static $maxColumnDeep = 10;
    private static $activeSheet = null;
    /**
     * Excel导出
     *
     * @param array  $datas      导出数据，格式[['name' => 'alibaba','age' => 18]]
     * @param string $fileName   导出文件名称
     * @param array  $options    操作选项，例如：
     *                  -- filterTitle    array           数据表头（sheet各列数据 根据 filterTitle.db_field 从datas内读取）
     *                       [
     *                          "db_field1" => ["列显示标题1", "列宽数值"],
     *                          "db_field2" => ["列显示标题2"],
     *                          "db_fiild3" => "列显示标题3"
     *                       ];
     *                  -- hideTitle      bool <false>    是否隐藏筛选表头
     *                  -- index          bool <true>     是否显示数据行号
     *                  -- print          bool <false>    设置打印格式
     *                  -- setBorder      bool <true>     设置单元格边框
     *                  //-- formula        array <null>    设置公式，例如['F2' => '=IF(D2>0,E42/D2,0)']
     *                  //-- format         array <null>    设置格式，整列设置，例如['A' => 'General']
     *                  -- mergeCells     array <null>    设置合并单元格，例如['A1:J1' => 'A1:J1']
     *                  -- bold           array <true>    设置加粗样式，例如['A1', 'A2']
     *
     *                  -- setARGB        array <true>    设置表头背景色，例如['A1', 'C1']
     *                                                      --  true
     *                                                      --  ['A1', 'C1']
     *                                                      --  [["color"=>argb, "fontFill" => "solid", "cell" => "A1" ]]
     *
     *                  -- alignCenter    array <true>    设置居中样式,默认全局居中 例如['A1', 'A2']
     *                  -- sumFields      array <null>    底部求和字段 ["db_field1" =>1000, "db_field2" => 19.34]
     *                  -- savePath       string <null>   保存路径，设置后则文件保存到服务器，不通过浏览器下载
     *                  -- sheetName      string <null>   设置工作表标题
     *                  -- largeTitle     string <null>   通栏大标题
     *                  -- freezeField    string <null>   冻结某个字段左侧全部区域 "db_field_name"
     *
     *                  -- dataFontSize   number <10>     数据区域字号
     *                  -- filterTitleFontSize number <12>     筛选表头字号
     *                  -- largeTitleFontSize number <14>     通栏大标题字号
     *
     * @return boolean
     * @return Exception
     */
    static function export($datas, $fileName = '', $options = []){
        // Dev::dump($options,1);
        try {
            if (empty($datas)) {
                return false;
            }

            set_time_limit(0);

            self::optionsDefaultSettings($options);

            $datas = array_values($datas);
            //计算实际数据行数（即：不含标题、合计行等）
            $dataRows = count($datas);

            $header = self::parseHeaderSettings($options);

            //预定义列名
            self::$sheetColumnNames = self::buildSheetColumnName(-1);

            //设置列宽
            $options['setWidth'] = $header['width'];

            //筛选标题 置入队列首位
            if($options['hideTitle']){
                //array_unshift($datas, $header['title'][0]);
                // $firstDataRowIndex = $_firstRowIndex = count($header['title']) + 1;
                $firstDataRowIndex = $_firstRowIndex = 1;
                $filterTitle = $options["filterTitle"];
            }else{
                array_unshift($datas, $header['title'][0]);
                // $firstDataRowIndex = $_firstRowIndex = count($header['title']) + 1;
                $firstDataRowIndex = $_firstRowIndex = 1 + 1;

                // 默认将紧挨firstDataRowIndex 的前一行理解为字段最全的表头配置(暂不支持多行筛选表头)
                // 而 firstDataRowIndex 是DB数据行，如果没有特殊处理的情况下，DB数据行字段一般是多余表头字段，故不能使用fristDataRowIndex DB数据行字段多少来判定sheet列多少的依据
                $filterTitle = $datas[$firstDataRowIndex-2];
            }

            // 获取最大列序号
            self::$sheetMaxColumnName = self::buildSheetColumnName(count(array_keys($filterTitle)));

            // 置入大标题文字
            if(is_array($header['largeTitle']) && !empty($header['largeTitle'])){
                //计算大标题行数
                $totals = ( isset($header['largeTitle'][0]) ? count($header['largeTitle']) : 1 );
                $firstDataRowIndex += $totals;
                if( $totals >= 1){
                    krsort($header['largeTitle']);
                    foreach($header['largeTitle'] as $v){
                        array_unshift($datas, $v);
                    }
                }
            }
            $options['firstDataRowIndex'] = $firstDataRowIndex;

            //根据此参数获取数据中的值，要与表格标题键名对应
            $headers = array_keys($filterTitle);

            // 检测底部合计开启状态
            self::checkSumArea($datas, $options, $headers, $header, $dataRows, $firstDataRowIndex, $_firstRowIndex);

            //计算冻结单元格
            self::checkFreezePane($options, $filterTitle, $firstDataRowIndex);

            // return $options;

            unset(
                $header,
                $options['filterTitle'],
                $options['largeTitle'],
                $options['largeTitleFontSize'],
                $options['dataFontSize'],
                $options['filterTitleFontSize']
            );

            /** @var Spreadsheet $objSpreadsheet */
            $objSpreadsheet = new Spreadsheet();

            // https://www.cnblogs.com/zx-admin/p/11653863.html

            $objSpreadsheet->getProperties()->setCreator("vipkwd.com");
            //$resp = $objSpreadsheet->getSheetCount();//工作表总数
            //$resp = $objSpreadsheet->getSheetNames();//工作表名数组
            //$sheet = $objSpreadsheet->getSheetByName('Sheet1');//根据表名获取工作表
            //$sheet = $objSpreadsheet->getSheet(0);//根据表索引获取工作表

            //设置默认文字居左，上下居中
            $styleArray = [
                'alignment' => [
                    'horizontal' =>  empty($options['alignCenter']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ];

            $objSpreadsheet->getDefaultStyle()->applyFromArray($styleArray);
            //切换指定工作表
            static::$activeSheet = $activeSheet = $objSpreadsheet->setActiveSheetIndex($options['sheetIndex']);

            // 在工作簿中创建工作表
            // $worksheet1 = $spreadSheet->createSheet();
            // $worksheet1->setTitle('Another sheet');

            // 工作表标签颜色
            $activeSheet->getTabColor()->setRGB('FF0000');

            // 设置工作表缩放级别
            $activeSheet->getSheetView()->setZoomScale(100);

            // 自动列宽
            $activeSheet->getDefaultColumnDimension()->setAutoSize(true);

            //设置列默认宽度
            // $activeSheet->getDefaultColumnDimension()->setWidth(50);

            //设置行默认高度 但不会修改已设置过高度/已有值的行
            // $activeSheet->getDefaultRowDimension()->setRowHeight(50);

            //设置单元格换行
            // $activeSheet->getStyle('B2')->getAlignment()->setWrapText(true);

            //设置A链接
            // $activeSheet->setCellValue('B2',"百度");
            // $activeSheet->getCell('B2')->getHyperlink()->setUrl('https://www.baidu.com');

            // $activeSheet->setCellValue('E26', 'www.百度.com');
            // $activeSheet->getCell('E26')->getHyperlink()->setUrl("sheet://'Sheetname'!A1");

            // 设置换行
            // $activeSheet->getCell('A1')->setValue("hello\nworld");
            // $activeSheet->getStyle('A1')->getAlignment()->setWrapText(true);


            //打印设置
            if (isset($options['print']) && $options['print'] === true) {
                // 纸张方向（横向打印）
                $activeSheet->getPageSetup()->setPaperSize(PageSetup:: ORIENTATION_LANDSCAPE);

                //设置打印为A4效果
                $activeSheet->getPageSetup()->setPaperSize(PageSetup:: PAPERSIZE_A4);
                //页面水平居中
                $activeSheet->getPageSetup()->setHorizontalCentered(true);
                //关闭页面垂直居中
                $activeSheet->getPageSetup()->setVerticalCentered(false);

                //设置打印时边距
                $pValue = 1 / 2.54;
                $activeSheet->getPageMargins()->setTop($pValue / 2 * 3.5);
                $activeSheet->getPageMargins()->setBottom($pValue / 2 * 3.5);
                $activeSheet->getPageMargins()->setLeft($pValue / 2);
                $activeSheet->getPageMargins()->setRight($pValue / 2);

                // &C 居中
                // &H 阴影样式
                $activeSheet->getHeaderFooter()->setOddHeader('&CDocument create by Vipkwd.com with Phpoffice');
                // $activeSheet->getHeaderFooter()->setOddFooter('&L&B' . $objSpreadsheet->getProperties()->getTitle() . '&RPage &P of &N');
                $activeSheet->getHeaderFooter()->setOddFooter('&L&B&D &T &RPage &P of &N');
            }

            //行数据处理
            foreach ($datas as $sKey => $sItem) {
                //默认文本格式
                $pDataType = DataType::TYPE_STRING;
                //设置单元格格式
                /*if (isset($options['format']) && !empty($options['format'])) {
                    $colRow = Coordinate::coordinateFromString($sKey);
                    //存在该列格式并且有特殊格式
                    if (isset($options['format'][$colRow[0]]) &&
                        NumberFormat::FORMAT_GENERAL != $options['format'][$colRow[0]]) {
                        $activeSheet->getStyle($sKey)->getNumberFormat()
                            ->setFormatCode($options['format'][$colRow[0]]);

                        if (false !== strpos($options['format'][$colRow[0]], '0.00') &&
                            is_numeric(str_replace(['￥', ','], '', $sItem))) {
                            //数字格式转换为数字单元格
                            $pDataType = DataType::TYPE_NUMERIC;
                            $sItem     = str_replace(['￥', ','], '', $sItem);
                        }
                    } elseif (is_int($sItem)) {
                        $pDataType = DataType::TYPE_NUMERIC;
                    }
                }*/
                $sKey = $sKey + 1;
                if($sKey >= $options['firstDataRowIndex'] && $sKey < ($dataRows + $options['firstDataRowIndex']) ){
                    $sItem[self::buildDataBufferSerialKey()] = $sKey - $options['firstDataRowIndex'] + 1;
                }
                //根据表头解析sheet每一行数据
                for ($i = 0;$i < count($headers);$i++) {
                    $activeSheet->setCellValueExplicit(self::$sheetColumnNames[$i] .$sKey, $sItem[$headers[$i]], $pDataType);
                }

                //存在:形式的合并行列，例如A1:B2，则对应合并
                /*if (false !== strstr($sKey, ":")) {
                    $options['mergeCells'][$sKey] = $sKey;
                }*/
            }

            unset($datas);

            //设置锁定行
            if (isset($options['freezePane']) && !empty($options['freezePane'])) {

                //如果手动指定表头 占用行数
                if($options['firstDataRowIndex'] > 1){

                    //分析已指定的行锁定位置
                    preg_match("/([A-Z]+)([1-9](\d+)?)$/i", $options['freezePane'], $reg);

                    if(isset($reg[1]) && isset($reg[2])){
                        //如果表头没有全部纳入锁定范围（例如：表头占5行，前序指定锁定前3行, 即还有2行表头部分没有被锁定)
                        $options['firstDataRowIndex'] *= 1;
                        // $options['firstDataRowIndex'] += 1;
                        if( ($reg[2] * 1) < $options['firstDataRowIndex'] ){
                            //扩展锁定行范围（即把全部表头行锁定)
                            $options['freezePane'] = ($reg[1]. ($options['firstDataRowIndex']));
                        }
                    }
                }
                $activeSheet->freezePane($options['freezePane']);
                unset($options['freezePane']);
            }

            //设置列度
            if (isset($options['setWidth']) && !empty($options['setWidth'])) {
                foreach ($options['setWidth'] as $swKey => $swItem) {
                    // 设置 swKey列的宽度
                    $activeSheet->getColumnDimension($swKey)->setWidth( $swItem * 1 + 0.71);
                    // 自动列宽
                    // $activeSheet->getColumnDimension($swKey)->setAutoSize(true);
                }
                unset($options['setWidth']);
            }

            //设置背景色
            if ( isset($options['setARGB']) && is_array($options['setARGB']) ) {
                //背景应用范围之制定特定单元格时，则应用于全部标题范围
                $param = array_values($headers);
                $__opts = ["setARGB" => [], "bold" => [], "alignCenter" => [] ];
                if( isset($options['firstDataRowIndex']) && $options['firstDataRowIndex'] > 1){
                    for($i=1;$i<$options['firstDataRowIndex'];$i++){
                        foreach($param as $key=>$field){
                            $__opts['setARGB'][] = (self::$sheetColumnNames[$key].$i);
                            $__opts['bold'][] = (self::$sheetColumnNames[$key].$i);
                            $__opts['alignCenter'][] = (self::$sheetColumnNames[$key].$i);
                        }
                    }
                }else{
                    foreach($param as $key=>$field){
                        $__opts['setARGB'][] = (self::$sheetColumnNames[$key]."1");
                        $__opts['bold'][] = (self::$sheetColumnNames[$key]."1");
                        $__opts['alignCenter'][] = (self::$sheetColumnNames[$key]."1");
                    }
                }
                $options['setARGB'] = array_merge($__opts['setARGB'], $options['setARGB']);
                $options['bold'] = array_merge($__opts['bold'], $options['bold']);
                $options['alignCenter'] = array_merge($__opts['alignCenter'], $options['alignCenter']);

                if(!empty($options['setARGB'])){
                    foreach ($options['setARGB'] as $k => $sItem) {
                        (is_string($sItem)) && $sItem = ["cell" => $sItem];
                        $options['setARGB'][$k] = $default = array_merge([
                            "color" => Color::COLOR_YELLOW,  "fontFill" => Fill::FILL_SOLID, "cell" => ""
                        ], $sItem);
                        if(!$default["cell"])
                            continue;
                        $activeSheet->getStyle($default["cell"])
                            ->getFill()->setFillType($default["fontFill"] ?? Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($default["color"] ?? Color::COLOR_YELLOW);
                    }
                }
                // return $options;
                unset($options['setARGB']);
            }

            //设置公式
            /*if (isset($options['formula']) && !empty($options['formula'])) {
                foreach ($options['formula'] as $fKey => $fItem) {
                    $activeSheet->setCellValue($fKey, $fItem);
                }

                unset($options['formula']);
            }*/
            // return $options;

            //合并行列处理
            if (isset($options['mergeCells']) && is_array($options['mergeCells']) && !empty($options['mergeCells'])) {
                if(!$options['index']){
                    $mergeCells = [];
                    $h1_area = ["A1:".self::$sheetMaxColumnName."1","A2:".self::$sheetMaxColumnName."2","A3:".self::$sheetMaxColumnName."3"];
                    foreach($options['mergeCells'] as $v){
                        if(!in_array($v, $h1_area)){
                            $vv = explode(":",preg_replace("/\d+/","", $v));
                            foreach(self::$sheetColumnNames as $ck => $cv){
                                if(strtoupper($vv[0]) == "A"){
                                    $vv[3] = "A";
                                }elseif($cv == $vv[0]){
                                    $vv[3] = self::$sheetColumnNames[$ck-1];
                                }
                                if(strtoupper($vv[1]) == "A"){
                                    $vv[4] = "A";
                                }elseif($cv == $vv[1]){
                                    $vv[4] = self::$sheetColumnNames[$ck-1];
                                }
                            }
                            // $h1_area ="A1:".self::$sheetMaxColumnName."1";
                            $v = str_replace([$vv[0], $vv[1]], [$vv[3], $vv[4]], $v);
                        }
                        $mergeCells[$v] = $v;
                        unset($vv,$v);
                    }
                    $options['mergeCells'] = $mergeCells;
                    unset($mergeCells);
                }

                // return $options['mergeCells'];

                $activeSheet->setMergeCells($options['mergeCells']);
                unset($options['mergeCells']);
            }

            //设置居中
            if (isset($options['alignCenter']) && is_array($options['alignCenter']) && !empty($options['alignCenter']) ) {
                $styleArray = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ];
                foreach ($options['alignCenter'] as $acItem) {
                    $activeSheet->getStyle($acItem)->applyFromArray($styleArray);
                }
                unset($options['alignCenter']);
            }

            //设置加粗
            if (isset($options['bold']) ) {
                //加粗应用范围之指定特定单元格时，则应用于全部标题范围
                if (empty($options['bold'])) {
                    // $options['bold'] = [];
                    $param = array_values($headers);
                    foreach($param as $key=>$field){
                        $options['bold'][] = (self::$sheetColumnNames[$key]. "1");
                    }
                }
                foreach ($options['bold'] as $bItem) {
                    $activeSheet->getStyle($bItem)->getFont()->setBold(true);
                }
                unset($options['bold']);
            }

            //设置工作表标题
            if( isset($options['sheetName']) && $options['sheetName'] ){
                $activeSheet->setTitle($options['sheetName']);
            }
            //设置字号大小
            if( isset($options['setSize']) && $options['setSize']){
                if($options['setSize'] === true){
                    $options['setSize'] = [];
                    //A1: I75;
                    // $maxCell = self::$sheetColumnNames[count($headers)];
                    // $setSizeArea = 'A1:' . $maxCell . count($datas);
                    $setSizeArea = 'A1:' . $activeSheet->getHighestColumn() . $activeSheet->getHighestRow();
                    $options['setSize'][$setSizeArea] = 11;
                }

                foreach($options['setSize'] as $ssItem => $size){
                    $activeSheet->getStyle($ssItem)->getFont()->setSize($size > 0 ? $size : 11);
                    // 设置字体加粗大小
                    // $activeSheet->getStyle($ssItem)->getFont()->setBold(true)->setName('Arial')->setSize(10);
                    // 字体名
                    // $fontName = $activeSheet->getStyle($ssItem)->getFont()->getName();
                    // 设置颜色
                    // $activeSheet->getStyle($ssItem)->getFont()->getColor()->setRGB('#AEEEEE');
                    // 获取颜色值
                    // $activeSheet->getStyle($ssItem)->getFont()->getColor()->getRGB();
                    // 设置日期
                    // $activeSheet->getCell($ssItem)->setValue(Date::PHPToExcel(time()));
                }
            }

            //设置单元格边框，整个表格设置即可，必须在数据填充后才可以获取到最大行列
            if (isset($options['setBorder']) && $options['setBorder']) {
                $border    = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN, // 设置border样式
                            'color'       => ['argb' => 'FF000000'], // 设置border颜色
                        ],
                    ],
                ];
                $setBorder = 'A1:' . $activeSheet->getHighestColumn() . $activeSheet->getHighestRow();
                $activeSheet->getStyle($setBorder)->applyFromArray($border);
                unset($options['setBorder']);
            }

            $bulidFileName = function($fileName){
                $fileName = !empty($fileName) ? preg_replace("/(\.[A-Za-z0-9]+)$/",'', $fileName) : date('YmdHis');
                return $fileName . '.xlsx';
            };

            $fileName = $bulidFileName($fileName ?? ($options['sheetName'] ?? ""));
            if(isset($options['savePath']) && @is_readable($options['savePath']) && is_dir($options['savePath'])){
                $savePath = realpath($options['savePath']). DIRECTORY_SEPARATOR .$fileName;
            }else{

                //直接导出Excel，无需保存到本地，输出07Excel文件
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header(
                    "Content-Disposition:attachment;filename=" . iconv(
                        // "utf-8", "GB2312//TRANSLIT", $fileName
                        "utf-8", "gb2312//IGNORE", $fileName
                    )
                );
                header("Content-Disposition:attachment;filename=" .$fileName);
                header('Cache-Control: max-age=0');//禁止缓存
                $savePath = 'php://output';
            }
            unset($bulidFileName);
            ob_clean();
            ob_start();
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            $objWriter->save($savePath);
            //释放内存
            $objSpreadsheet->disconnectWorksheets();
            unset($objSpreadsheet);
            ob_end_flush();
            if(strrpos($savePath, $fileName)){
                return $savePath;
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Excel导出（Demo）
     *
     * @param boolean $confirm
     * @return void
     */
    static function exportDemo(bool $confirm = false){
        if($confirm !== true) return;
        $result = array(
            [ "key"=> "relname" , "field" => "姓名"],
            [ "key"=> "city" , "field" => "社保城市"],
            [ "key"=> "mobile" , "field" => "手机号码"],
            [ "key"=> "ref" , "field" => "推荐人数"]
        );

        // 语法： $title[db_field] = [ "Sheet表头单元格显示文字", "表头列宽" ]
        $title = [];

        // 依次构建 B/C/D/E...列表头
        foreach($result as $item){
            $title[ $item['key'] ]= [$item['field'],20];
        }
        //指定最后1列表头
        $title["cdate"] = ["登记时间", 17];

        $db_list = [
            ["id"=>1, "relname"=>"皇甫赵钱", "ref"=>10 ,"city"=>"广州", "mobile"=>"13000000000", "cdate"=>"2019-12-30"],
            ["id"=>2, "relname"=>"欧阳孙李", "ref"=>11 ,"city"=>"北京", "mobile"=>"13200000001", "cdate"=>"2019-12-30"],
            ["id"=>3, "relname"=>"司马周吴", "ref"=>12 ,"city"=>"上海", "mobile"=>"13400000002", "cdate"=>"2019-12-30"],
            ["id"=>4, "relname"=>"上官郑王", "ref"=>13 ,"city"=>"深圳", "mobile"=>"13600000003", "cdate"=>"2019-12-30"],
        ];

        $refs = 0;
        foreach($db_list as $k => $item){
            //求和指定列字段
            $refs += $item['ref'];

            unset($k, $item);
        }

        self::export($db_list, date("Ymd-His").'.xlxs', [
            'hideTitle' => true,
            'filterTitle' => $title,
            'largeTitle' => "大标题",
            'alignCenter' => true,
            'setBorder' => true,
            'setARGB' => true,
            'bold' => true,
            'print'=> true,
            'setWidth'=>['A'=>5.14,'B'=>7.71,'C'=>12.14,'D'=>20.14,'E'=> 11,'J'=>8.29,'M'=>4],
		    'freezePane' => 'C3',
		    'firstDataRowIndex' => 0,
		    'setSize' => [],
            'sheetName' => '工作表名称',
            'mergeCells' => [],
            'setTitle' => '佣金小票',
            'freezeField' => 'relname',
            'sumFields' => [
                "ref" => $refs,
            ]
        ]);
    }

    /**
     * Excel导入(xslx|xls)
     *
     * @param string $file      文件地址
     * @param int    $sheetIndex     工作表sheet(传0则获取第一个sheet)
     * @param int    $columnCnt 列数(传0则自动获取最大列)
     * @param array  $options   操作选项
     *                  -- mergeCells          array <null>  申明已合并的单元格
     *                  -- formula             array <null>  公式数组
     *                  -- format              array <null>  单元格格式数组
     *                  -- ignoreEmptyLine     bool <true>   是否忽略空行 默认忽略
     *
     * @return array
     * @throws Exception
     */
    static function import($file = '', $sheetIndex = 0, $columnCnt = 0, $rows = 0, &$options = []){
        try {
            /* 转码 */
            $file = iconv("utf-8", "gb2312//IGNORE", $file);

            if (empty($file) OR !file_exists($file)) {
                $msg['code'] = 9404;
                $msg['msg'] = '文件不存在!';
                return $msg;
                //throw new \Exception('文件不存在!');
            }

            /** @var Xlsx $objRead */
            $objRead = IOFactory::createReader('Xlsx');

            if (!$objRead->canRead($file)) {
                /** @var Xls $objRead */
                $objRead = IOFactory::createReader('Xls');

                if (!$objRead->canRead($file)) {
                    $msg['code'] = 9401;
                    $msg['msg'] = '只支持导入Excel文件!';
                    return $msg;
                    //throw new \Exception('只支持导入Excel文件！');
                }
            }

            /* 如果不需要获取特殊操作，则只读内容，可以大幅度提升读取Excel效率 */
            empty($options) && $objRead->setReadDataOnly(true);
            /* 建立excel对象 */
            $obj = $objRead->load($file);
            /* 获取指定的sheet表 */
            static::$activeSheet = $currSheet = $obj->getSheet($sheetIndex);

            if (isset($options['mergeCells'])) {
                /* 读取合并行列 */
                $options['mergeCells'] = $currSheet->getMergeCells();
            }

            if (0 == $columnCnt) {
                /* 取得最大的列号 */
                $columnH = $currSheet->getHighestColumn();
                /* 兼容原逻辑，循环时使用的是小于等于 */
                $columnCnt = Coordinate::columnIndexFromString($columnH);
            }

            /* 获取总行数 */
            $rowCnt = $rows ? $rows : $currSheet->getHighestRow();
            $data   = [];

            // 拆分单元格
            // $currSheet->unmergeCells('B7:C10');

            /* 读取内容 */
            for ($_row = 1; $_row <= $rowCnt; $_row++) {
                $isNull = true;

                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId   = $cellName . $_row;
                    $cell     = $currSheet->getCell($cellId);

                    //获取数据类型
                    // $type = $cell->getDataType();

                    if (isset($options['format'])) {
                        /* 获取格式 */
                        $format = $cell->getStyle()->getNumberFormat()->getFormatCode();
                        /* 记录格式 */
                        $options['format'][$_row][$cellName] = $format;
                    }

                    if (isset($options['formula'])) {
                        /* 获取公式，公式均为=号开头数据 */
                        $formula = $currSheet->getCell($cellId)->getValue();

                        if (0 === strpos($formula, '=')) {
                            $options['formula'][$cellName . $_row] = $formula;
                        }
                    }

                    $f1="m{1,2}(\/|\-)d{1,2}(\/|\-)y{1,4}";
                    $f2="y{1,4}(\/|\-)m{1,2}(\/|\-)d{1,2}";
                    $f3="d{1,2}(\/|\-)m{1,2}(\/|\-)y{1,4}";
                    if (isset($format) && preg_match("/^({$f1}|{$f2}|{$f3})$/i",$format) === 1) {
                        /* 日期格式翻转处理 */
                        $cell->getStyle()->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                    }
                    unset($f1, $f2, $f3);

                    $data[$_row][$cellName] = trim($currSheet->getCell($cellId)->getFormattedValue());

                    if (!empty($data[$_row][$cellName])) {
                        $isNull = false;
                    }
                }

                /* 判断是否整行数据为空，是的话删除该行数据 */
                if ($isNull && (!isset($options['ignoreEmptyLine']) || $options['ignoreEmptyLine'] === true) ) {
                    unset($data[$_row]);
                }
            }
            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 根据表头列长度获取最大英文列号
     *
     * @param integer $columnTotals 有效数据总列数
     * @return string|array
     */
    static function buildSheetColumnName(int $columnTotals){
        $default = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $code = $default;
        $deep = self::$maxColumnDeep;// A ~ AZ ~ BZ (默认最大支持77列)
        if($columnTotals > 0){
            //数据列不大于26列则不构建扩展列名
            $deep = ceil( $columnTotals / 26 );
        }
        for($i=0; $i<$deep-1; $i++){
            foreach($default as $txt){
                $code[] = $default[$i]. $txt;
            }
        }
        unset($default);
        $columnTotals = $columnTotals > 0 ? $columnTotals : false;
        if($columnTotals === false){
            return $code;
        }
        return $code[ $columnTotals -1 ];
    }

    /**
     * 解析大小标题
     *
     * @param array $title 筛选标题
     * @param string $h1_txt 大标题内容 默认空不显示大标题
     * @return array
     */
    static function parseHeaderSettings(array $options){
        $titles = $options['filterTitle'];
        $h1_txt = $options['largeTitle'] ?? "";
        $___index = self::buildDataBufferSerialKey();
        $h1 = [];
        $width=[];
        $tdTagNames = self::buildSheetColumnName(-1);
        if(!isset($titles[0])){
            $titles = [$titles];
        }
        $f = 0;
        foreach($titles as $key => $title){
            $i = 0;
            $options['index'] && $titles[$key] = $title = array_merge([
                "$___index" => ["No.",6]
            ], $title);
            foreach($title as $field => $name){

                //通栏大标题
                if($h1_txt && $f === 0){
                    if(is_array($h1_txt)){
                        foreach($h1_txt as $h1_key => $h1_item){
                            if($options['index']){
                                $h1[$h1_key][$field] = ($field == $___index) ? $h1_item["text"] : "";
                            }else{
                                $h1[$h1_key][$field] = ($field == array_key_first($title)) ? $h1_item['text'] : "";
                            }
                        }
                    }else{
                        if($options['index']){
                            $h1[0][$field] = ($field == $___index) ? $h1_txt : "";
                        }else{
                            $h1[0][$field] = ($field == array_key_first($title)) ? $h1_txt : "";
                        }
                    }
                }
                //如果筛选标题的值是数组，说明有配置标题列宽度
                if(is_array($name)){
                    if(isset($name[1])){
                        //设置列宽 name[1]是宽度尺寸
                        $width[ $tdTagNames[$i] ] = $name[1];
                    }
                    //重置列标题文字
                    $titles[$key][$field] = $name[0];
                }else if(is_string($name)){
                    $titles[$key][$field] = $name;
                }
                $i++;
                unset($field, $name);
            }
            $f = 1;
        }
        unset($i, $tdTagNames, $h1_txt);
        return [
            "largeTitle" => $h1,
            "title" => $titles,
            "width" => $width
        ];
    }

    /**
     * 自定义序号（不依托外部指定序列）
     *
     * @return string
     */
    private static function buildDataBufferSerialKey(){

        if(self::$currentSerialKey == null){
            self::$currentSerialKey = "__idx" . md5( time().__FUNCTION__);
        }
        return self::$currentSerialKey;
    }
    /**
     * 检测底部统计求和与设置表格字号
     *
     * @param array $datas
     * @param array $options
     * @param array $headers
     * @param array $header
     * @param integer $dataRows
     * @param integer $firstDataRowIndex
     * @param integer $_firstRowIndex
     * @return boolean
     */
    private static function checkSumArea(array &$datas, array &$options, array $headers, array $header, int $dataRows, int $firstDataRowIndex, int $_firstRowIndex){
        $sumActionStatus = false;
        if(isset($options['sumFields']) && is_array($options['sumFields']) && !empty($options['sumFields']) ){
            $serialIndex = self::buildDataBufferSerialKey();
            $data = array_fill(0, count($headers), "一");
            $filterTitle = array_combine($headers, $data);
            $filterTitle[$serialIndex] = '合计';
            unset($data);
            foreach($options['sumFields'] as $field => $totals){
                $filterTitle[$field] = $totals;
            }
            //统计行合并
            $_column=1;
            $merge_start_column = '';
            $sumColumns = array_keys($options['sumFields']);

            foreach($filterTitle as $field => $v){
                if(in_array($field, $sumColumns) || $field == $serialIndex){
                    if($merge_start_column != ""){
                        $merge_end_column = self::buildSheetColumnName($_column-1);
                        if($merge_start_column != $merge_end_column){
                            $merge_area = $merge_start_column. ($dataRows+$firstDataRowIndex).":".$merge_end_column.($dataRows+$firstDataRowIndex);
                            $options['mergeCells'][$merge_area] = $merge_area;
                            unset($merge_area);
                        }
                        $merge_start_column = $merge_end_column = "";
                    }
                }else{
                    if($merge_start_column == ""){
                        $merge_start_column = self::buildSheetColumnName($_column);
                    }else{
                        unset($filterTitle[$field]);
                    }
                }
                $_column++;
                unset($field,$v);
            }
            //闭合合并动作
            if($merge_start_column != ""){
                $merge_end_column = self::buildSheetColumnName($_column-1);
                if($merge_start_column != $merge_end_column){
                    $merge_area = $merge_start_column. ($dataRows+$firstDataRowIndex).":".$merge_end_column.($dataRows+$firstDataRowIndex);
                    $options['mergeCells'][$merge_area] = $merge_area;
                    unset($merge_area);
                }
            }
            $datas = array_merge($datas, [$filterTitle]);

            unset($_column, $merge_start_column, $merge_end_column, $merge_area, $sumColumns,$options['sumFields']);
            //  全局内容字号
            $size_area = "A".$firstDataRowIndex.":".self::$sheetMaxColumnName.($dataRows+$firstDataRowIndex-0);

            $sumActionStatus=true;
        }else{
            //  全局内容字号
            $size_area = "A".$firstDataRowIndex.":".self::$sheetMaxColumnName.($dataRows+$firstDataRowIndex-1);
        }
        //  全局内容字号
        $options['setSize'][$size_area] = $options['dataFontSize'];

        // 筛选标题字号
        $size_area = "A".($firstDataRowIndex - count($header['title'])).":".self::$sheetMaxColumnName.($firstDataRowIndex-1);
        $options['setSize'][$size_area] = $options['filterTitleFontSize'];

        // 通栏大标题字号
        if($firstDataRowIndex > $_firstRowIndex){
            for($i =1; $i <= ($firstDataRowIndex - $_firstRowIndex); $i++){
                $h1_area =  "A{$i}:".self::$sheetMaxColumnName.$i;
                $options['setSize'][$h1_area] = $options['largeTitleFontSize'];
                //通栏合并
                $options['mergeCells'][$h1_area] = $h1_area;
            }
        }
        return $sumActionStatus;
    }

    /**
     * 计算冻结区域
     *
     * @param array $options
     * @param array $filterTitle
     * @param integer $firstDataRowIndex
     * @return boolean
     */
    private static function checkFreezePane(array &$options, array $filterTitle, int $firstDataRowIndex){
        if(isset($options['freezeField'])){
            $_column=0;
            foreach($filterTitle as $field=>$v){
                $_column++;
                unset($v);
                if($field == $options['freezeField']){
                    $columnName = self::buildSheetColumnName($_column);
                    $options['freezePane'] = $columnName.$firstDataRowIndex;
                    break;
                }
            }
            unset($_column, $options['freezeField']);
            return true;
        }
        return false;
    }

    /**
     * 初始化选项
     *
     * @param array $options
     * @return void
     */
    private static function optionsDefaultSettings(array &$options){

        $options['sheetIndex'] = 0;
        if( !isset($options['setSize']) || !is_array($options['setSize']) ){
            $options['setSize'] = [];
        }

        if( !isset($options['bold']) || $options['bold'] === true ){
            $options['bold'] = [];
        }
        if( !isset($options['setARGB']) || $options['setARGB'] === true){
            $options['setARGB'] = [];
        }
        if( !isset($options['alignCenter']) || $options['alignCenter'] === true ){
            $options['alignCenter'] = [];
        }
        if(!isset($options['dataFontSize' ]) || $options['dataFontSize'] < 10 ){
            $options['dataFontSize'] = 10;
        }
        if(!isset($options['filterTitleFontSize']) || $options['filterTitleFontSize'] < 10 ){
            $options['filterTitleFontSize'] = 12;
        }
        if(!isset($options['largeTitleFontSize']) || $options['largeTitleFontSize'] < 10 ){
            $options['largeTitleFontSize'] = 14;
        }
        if( !isset($options['mergeCells']) || !is_array($options['mergeCells'])){
            $options['mergeCells'] = [];
        }
        $options['setBorder'] = (!isset($options['setBorder']) || $options['setBorder']) ? true : false;
        $options['print'] = (isset($options['print']) && $options['print']) ? true : false;
        $options['index'] = (!isset($options['index']) || $options['index']) ? true : false;
        $options['hideTitle'] = (isset($options['hideTitle']) && $options['hideTitle']) ? true : false;
    }

    /**
     * 在指定单元格写入图片
     *
     * @param string $imgPath 图片路径
     * @param string $cell 单元格
     * @param array $options
     *                  -- width integer <100> 图片宽
     *                  -- height integer <100> 图片高
     *                  -- offsetx integer <50> 图片偏移量
     * @return void
     */
    private static function putImageToCell(string $imgPath, string $cell, array $options = []){
        $options = array_merge([
            "width" => 100,//图片宽
            "height" => 100,//图片高
            "offsetx" => 50,//设置图片偏移量
        ], $options);
        //图像写入操作
        $drawing = new Drawing();
        $drawing->setWorksheet(static::$activeSheet);
        $drawing->setPath(realpath($imgPath));
        $drawing->setWidth($options['width']);
        $drawing->setHeight($options['height']);
        $drawing->setOffsetX($options['offsetx']);
        //将图片放置于单元格
        $drawing->setCoordinates($cell);
        unset($drawing, $options, $imageFileName, $cell);
    }

    /**
     * 获取表格中的图片
     *
     * @param string $savePath 本地存储图片的位置
     * @return array 返回各单元格图片存储在本地的位置
     */
    private static function getImageFromSheet(string $savePath):array{
        //获取所有图像
        $draws = static::$activeSheet->getDrawingCollection();
        $savePath = rtrim(str_replace('\\','/',realpath($savePath)),'/').'/';
        $list = [];
        foreach ($draws as $drawing) {//$drawing 为 PhpOffice\PhpSpreadsheet\Worksheet\Drawing类的实例;
            $coordi = $drawing->getCoordinates();//获取图像坐标 eg A4
            //拆分成 ['A','4'];
            // list($column, $row) = Coordinate::coordinateFromString($coordi);
            $saveName = $savePath.$coordi.'_'. mt_rand(1000, 9999);
            switch ($drawing->getExtension()) {
                case 'jpg':
                case 'jpeg':
                    $saveName .= '.jpg';
                    imagejpeg(imagecreatefromjpeg($drawing->getPath()),  $saveName);
                    $list[$coordi] = $saveName;
                    break;
                case 'gif':
                    $saveName .= '.gif';
                    imagegif(imagecreatefromgif($drawing->getPath()),  $saveName);
                    $list[$coordi] = $saveName;
                    break;
                case 'png':
                    $saveName .= '.png';
                    imagepng(imagecreatefrompng($drawing->getPath()), $saveName);
                    $list[$coordi] = $saveName;
                    break;
            }
            unset($coordi, $saveName, $drawing);
        }
        unset($draws, $savePath);
        return $list;
    }
}