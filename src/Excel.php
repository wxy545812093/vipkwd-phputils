<?php
/**
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class Excel{
    
    /**
     * Excel导出
     *
     * @param array  $datas      导出数据，格式[['name' => 'alibaba','age' => 18]]
     * @param string $fileName   导出文件名称
     * @param array  $options    操作选项，例如：
     *                  -- print          bool <false>    设置打印格式
     *                  -- freezePane     string <null>   冻结单元格，例如表头为第一行，则锁定表头输入A2
     *                  -- setWidth       array <null>    设置宽度，例如['A' => 30, 'C' => 20]
     *                  -- setBorder      bool <true>     设置单元格边框
     *                  -- mergeCells     array <null>    设置合并单元格，例如['A1:J1' => 'A1:J1']
     *                  -- formula        array <null>    设置公式，例如['F2' => '=IF(D2>0,E42/D2,0)']
     *                  -- format         array <null>    设置格式，整列设置，例如['A' => 'General']
     *                  -- bold           array <true>    设置加粗样式，例如['A1', 'A2']
     *                  -- savePath       string <null>   保存路径，设置后则文件保存到服务器，不通过浏览器下载
     *                  -- sheetName       string <null>   设置工作表标题
     *                  -- setSize        array <null>    设置字号大小
     *                  -- setARGB        array <true>    设置背景色，例如['A1', 'C1']
     *                  -- alignCenter    array <true>    设置居中样式 例如['A1', 'A2']
     *                  -- firstDataRowIndex   int <1>    第一个数据行
     */
    static function export($datas, $fileName = '', $options = []){
        try {
            if (empty($datas)) {
                return false;
            }
            //根据此参数获取数据中的值，要与表格标题键名对应
            $params = array_keys($datas[0]);
            //列名
            $rows = self::getTdTagName(-1);
            
            set_time_limit(0);
            /** @var Spreadsheet $objSpreadsheet */
            $objSpreadsheet = new Spreadsheet();
            //设置默认文字居左，上下居中
            $styleArray = [
                'alignment' => [
                    'horizontal' =>  ( isset($options['alignCenter']) && ($options['alignCenter'] === true)) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ];
            if( isset($options['bold']) && $options['bold'] === true ){
                $options['bold'] = [];
            }
            if( isset($options['setARGB']) && $options['setARGB'] === true ){
                $options['setARGB'] = [];
            }
            if( isset($options['alignCenter']) && $options['alignCenter'] === true ){
                $options['alignCenter'] = [];
            }
            $objSpreadsheet->getDefaultStyle()->applyFromArray($styleArray);
            //设置Excel Sheet
            $activeSheet = $objSpreadsheet->setActiveSheetIndex(0);

            //打印设置
            if (isset($options['print']) && $options['print'] === true) {
                //设置打印为A4效果
                $activeSheet->getPageSetup()->setPaperSize(PageSetup:: PAPERSIZE_A4);
                //设置打印时边距
                $pValue = 1 / 2.54;
                $activeSheet->getPageMargins()->setTop($pValue / 2);
                $activeSheet->getPageMargins()->setBottom($pValue * 1);
                $activeSheet->getPageMargins()->setLeft($pValue / 2);
                $activeSheet->getPageMargins()->setRight($pValue / 2);
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
                for ($i = 0;$i < count($params);$i++) {
                    $activeSheet->setCellValueExplicit($rows[$i] .$sKey, $sItem[$params[$i]], $pDataType);
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
                if(isset($options['firstDataRowIndex']) && $options['firstDataRowIndex'] > 1){

                    //分析已指定的行锁定位置
                    preg_match("/([A-Z]+)([1-9](\d+)?)$/i", $options['freezePane'], $reg);

                    // dump($reg,1);

                    if(isset($reg[1]) && isset($reg[2])){
                        //如果表头没有全部纳入锁定范围（例如：表头占5行，前序指定锁定前3行, 即还有2行表头部分没有被锁定)
                        $options['firstDataRowIndex'] *= 1;
                        // $options['firstDataRowIndex'] += 1;
                        if( ($reg[2] * 1) < ($options['firstDataRowIndex']-1) ){
                            //扩展锁定行范围（即把全部表头行锁定住)
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
                    $activeSheet->getColumnDimension($swKey)->setWidth( $swItem * 1 + 0.71);
                }
                unset($options['setWidth']);
            }

            //设置背景色
            if ( isset($options['setARGB']) ) {
                //背景应用范围之制定特定单元格时，则应用于全部标题范围
                $param = array_values($params);
                if( isset($options['firstDataRowIndex']) && $options['firstDataRowIndex'] > 1){
                    for($i=1;$i<$options['firstDataRowIndex'];$i++){
                        foreach($param as $key=>$field){
                            //TODO 多行表头 加背景未实现
                            $options['setARGB'][] = ($rows[$key].$i);
                            $options['bold'][] = ($rows[$key].$i);
                            $options['alignCenter'][] = ($rows[$key].$i);
                        }
                    }
                }else{
                    foreach($param as $key=>$field){
                        //TODO 多行表头 加背景未实现
                        $options['setARGB'][] = ($rows[$key]."1");
                        $options['bold'][] = ($rows[$key]."1");
                        $options['alignCenter'][] = ($rows[$key]."1");
                    }
                }

                if(!empty($options['setARGB'])){
                    foreach ($options['setARGB'] as $sItem) {
                        $activeSheet->getStyle($sItem)
                            ->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB(Color::COLOR_YELLOW);
                    } 
                }
                unset($options['setARGB']);
            }

            //设置公式
            /*if (isset($options['formula']) && !empty($options['formula'])) {
                foreach ($options['formula'] as $fKey => $fItem) {
                    $activeSheet->setCellValue($fKey, $fItem);
                }

                unset($options['formula']);
            }*/

            //合并行列处理
            if (isset($options['mergeCells']) && !empty($options['mergeCells'])) {
                $activeSheet->setMergeCells($options['mergeCells']);
                unset($options['mergeCells']);
            }

            //设置居中
            if (isset($options['alignCenter']) && !empty($options['alignCenter']) && is_array($options['alignCenter']) ) {
                $styleArray = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ];

                // if( !empty($options['alignCenter']) ){
                    foreach ($options['alignCenter'] as $acItem) {
                        $activeSheet->getStyle($acItem)->applyFromArray($styleArray);
                    }
                // }
                unset($options['alignCenter']);
            }

            //设置加粗
            if (isset($options['bold']) ) {
                //加粗应用范围之指定特定单元格时，则应用于全部标题范围
                if (empty($options['bold'])) {
                    // $options['bold'] = [];
                    $param = array_values($params);
                    foreach($param as $key=>$field){
                        $options['bold'][] = ($rows[$key]. "1");
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
                    $maxCell = $rows[(count($params) - 1)];
                    //A1: I75;
                    $setSizeArea = 'A1:' . $maxCell . count($datas); 
                    $options['setSize'][$setSizeArea] = 11;
                }

                foreach($options['setSize'] as $ssItem => $size){
                    $activeSheet->getStyle($ssItem)->getFont()->setSize($size);
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



            $fileName = !empty($fileName) ? $fileName : (date('YmdHis') . '.xlsx');

            if (!isset($options['savePath'])) {
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
            } else {
                $savePath = $options['savePath'];
            }

            ob_clean();
            ob_start();
            $objWriter = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
            $objWriter->save($savePath);
            //释放内存
            $objSpreadsheet->disconnectWorksheets();
            unset($objSpreadsheet);
            ob_end_flush();

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
    public function exportDemo(bool $confirm = false){
        if($confirm !== true) return;
        $result = array(
            [ "key"=> "relname" , "field" => "姓名"],
            [ "key"=> "city" , "field" => "社保城市"],
            [ "key"=> "mobile" , "field" => "手机号码"],
            [ "key"=> "ref" , "field" => "推荐人数"],
        );

        // 通常我们将“index” 序号列放在 Sheet 的A列（即: $title[0])
        // 语法： title [ db_field ] = [ Sheet表头单元格显示文字, 表头列宽 ]
        $title = array("index" => ["No.", 6]);
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

        $totals = 0;
        foreach($db_list as $k => $item){
            $db_list[$k]['index'] = $k+1;
            $totals += $item['ref'];
            unset($k, $item);
        }

        //计算实际数据行数（即：不含标题、合计行等）
        $dataRows = count($db_list);

        $header = self::parseHeaderSettings($title, "通栏大标题文字,留空表不启用大标题");
        unset($title);

        //通栏大标题
        $h1 = $header['h1'];

        //筛选标题 置入队列首位
        array_unshift($db_list, $header['title']);

        //定位第一条数据行号;
        //默认有筛选标题，故有效数据行号是2
        //如果还有大标题，则有效数据行号是3
        $firstDataRowIndex = count($h1) == 0 ? 2 : 3;

        //通栏大标题 置入队列首位
        $firstDataRowIndex == 3 && array_unshift($db_list, $h1);

        // 获取最大列序号
        $maxTdTagName = self::getTdTagName(count($header['title']));

        // 合并单元格
        $merge_cells = [];
        // 设置字号
        $set_size = [];

        //  全局内容字号
        $size_area = "A".$firstDataRowIndex.":".$maxTdTagName.($dataRows+$firstDataRowIndex);
        $set_size[$size_area] = 10;
        // 筛选标题字号
        $size_area = "A".($firstDataRowIndex-1).":".$maxTdTagName.($firstDataRowIndex-1);
        $set_size[$size_area] = 12;

        if($firstDataRowIndex == 3){
            // 通栏大标题字号
            $h1_area ="A1:{$maxTdTagName}1";
            $set_size[$h1_area] = 14;

            $merge_cells[$h1_area] = $h1_area;//通栏合并
        }

        // 复用大标题格式，创建底部合计
        $h1['index'] = '合计';
        $h1['relname'] = "一";
        $h1['ref'] = $totals;
        $h1['cdate'] = "一";
        $db_list = array_merge($db_list, [$h1]);

        //数据行合并
        $merge1 = 'B'.($dataRows+$firstDataRowIndex).":D".($dataRows+$firstDataRowIndex);
        $merge_cells[$merge1] = $merge1;

        // dump($merge_cells);
        // dump($db_list,1);
        self::export($db_list, date("Ymd-His").'.xlsx', [

            'alignCenter' => true,
            'setBorder' => true,
            'setARGB' => true,
            'bold' => true,
            'print'=> true,

            'setWidth'=>$header['width'],
            'setSize' => $set_size,
            'sheetName' => '快推表单收集汇总表',
            'mergeCells' => $merge_cells,

            'freezePane' => 'B'.$firstDataRowIndex,//冻结位置
            'firstDataRowIndex' => $firstDataRowIndex,
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
            $currSheet = $obj->getSheet($sheetIndex);

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

            /* 读取内容 */
            for ($_row = 1; $_row <= $rowCnt; $_row++) {
                $isNull = true;

                for ($_column = 1; $_column <= $columnCnt; $_column++) {
                    $cellName = Coordinate::stringFromColumnIndex($_column);
                    $cellId   = $cellName . $_row;
                    $cell     = $currSheet->getCell($cellId);

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
     * @param integer $tds 有效数据行数
     * @return string|array
     */
    static function getTdTagName(int $tds){
        $default = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
        $code = $default;
        $deep = 3;// A ~ AZ ~ BZ (共77列)
        for($i=0; $i<$deep-1; $i++){
            foreach($default as $txt){
                $code[] = $default[$i]. $txt;
            }
        }
        $tds = $tds > 0 ? $tds : false;
        if($tds === false){
            return $code;
        }
        return $code[ $tds -1 ];
    }

    /**
     * 解析大小标题
     *
     * @param array $title 筛选标题
     * @param string $h1_txt 大标题内容 默认空不显示大标题
     * @return array
     */
    static function parseHeaderSettings(array $title, $h1_txt = ""){
        $h1 = [];
        $width=[];
        $tdTagNames = self::getTdTagName(-1);
        $i = 0;
        foreach($title as $field => $name){
            //通栏大标题
            if($h1_txt){
                $h1[$field] = ($field == "index") ? $h1_txt : "";
            }
            //如果筛选标题的值是数组，说明有配置标题列宽度
            if(is_array($name)){
                if(isset($name[1])){
                    //设置列宽 name[1]是宽度尺寸
                    $width[ $tdTagNames[$i] ] = $name[1];
                }
                //重置列标题文字
                $title[$field] = $name[0];
            }
            $i++;
            unset($field, $name);
        }
        unset($i, $tdTagNames, $h1_txt);
        return [
            "h1" => $h1,
            "title" => $title,
            "width" => $width
        ];
    }
}