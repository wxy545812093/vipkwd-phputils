<?php

/**
 * @name FFmpeg处理视频
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\MediumAI\FFmpeg;

// use Vipkwd\Utils\Ip;
use Vipkwd\Utils\Tools;
use Vipkwd\Utils\System\File;
use Vipkwd\Utils\Type\Str as VipkwdStr;
// use Vipkwd\Utils\Type\Math as VipkwdMath;
use \Exception;

class Video
{
    use Traits;

    /**
     * 旋转
     *
     * @param array $options
     *                  -- file 媒体文件绝对地址
     *                  -- degrees <180> 角度 [0 | 90 | 18 | 270 | 360]
     *                  -- direction 旋转方向 l:向左 r: 向右
     *
     *                  -- output_dir <"./"> 输出目录（建议指定绝对路径）
     *                  -- ext <null> 输入文件格式  默认保留原始格式
     * @param bool $raw <false>
     *
     * @return array
     */
    public function rotate(array $options, bool $raw = false): array
    {
        /*
          #逆时针旋转画面90度水平翻转
          ffmpeg -i test.mp4 -vf "transpose=0" out.mp4

          #顺时针旋转画面90度
          ffmpeg -i test.mp4 -vf "transpose=1" out.mp4

          #逆时针旋转画面90度
          ffmpeg -i test.mp4 -vf "transpose=2" out.mp4

          #顺时针旋转画面90度再水平翻转
          ffmpeg -i test.mp4 -vf "transpose=3" out.mp4

          #水平翻转视频画面
          ffmpeg -i test.mp4 -vf hflip out.mp4

          #垂直翻转视频画面
          ffmpeg -i test.mp4 -vf vflip out.mp4
        */
        if (true !== ($result =  $this->validateFile($options['file']))) {
            return $result;
        }

        if (($mod = $options['degrees'] % 90) > 0) {
            $options['degrees'] -= $mod;
        }
        if ($options['direction'] == 'r') {
            $directionkey = 1;
        } else {
            $directionkey = 2;
        }

        $text = "transpose={$directionkey}";
        for ($count = ($options['degrees'] / 90); $count > 1; $count--) {
            $text .= ",transpose={$directionkey}";
        }

        $options['output'] = $this->output($options);

        $shell = vsprintf('%s -y -i "%s" -vf "%s" %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $text,
            $options['output']
        ]);
        $ret = self::_reasponse2($options, $shell, $raw);
        if (isset($ret['output'])) {
            if (isset($options['replace']) && $options['replace'] === true) {
                $file = dirname($options['output']) . '/' . basename($options['file']);
                @rename($options['output'], $file);
            }
            $ret['nfile'] = (isset($file) && is_file($file)) ? $file : $options['output'];
        }
        return $ret;
    }

    /**
     * 视频剪切
     *
     * @param array $options
     *                  -- file {string} 媒体文件绝对地址
     *                  -- start {integer|string} 开始位置 (1、可以 以秒为单位计数： 80；2、可以是 H:i:s 格式 00:01:20)
     *                  -- end {integer|string}  结束位置 (1、可以 以秒为单位计数： 80；2、可以是 H:i:s 格式 00:01:20)
     *
     *                  -- ext {string} <null> 输入文件格式  默认保留原始格式
     *                  -- output_dir {string} <"./"> 输出目录（建议指定绝对路径）
     *                  -- save_name {string} <null> 输出媒体文件名称(不含扩展名)
     * @param bool $raw <false>
     *
     * @return array
     */
    public function cut(array $options, bool $raw = false): array
    {

        $options['output'] = $this->output($options);

        $shell = vsprintf('%s -y -ss %s -t %s -accurate_seek -i "%s" -vcodec copy -acodec copy -avoid_negative_ts 1 %s 2>&1', [
            $this->opts['command'],
            $options['start'],
            $options['end'],
            File::pathToUnix($options['file']),
            $options['output']
        ]);
        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 拼接 (如 ts媒体)
     *
     * 适用于视频切割产生的分段，被合并的视频必须是相同的参数
     *
     * @param array $options
     *                  -- files {array} 媒体文件列表绝对地址 一维数组
     *
     *                  -- ext {string} <null> 输入文件格式  默认保留原始格式
     *                  -- output_dir {string} <"./"> 输出目录（建议指定绝对路径)
     *                  -- save_name {string} <null> 输出媒体文件名称(不含扩展名)
     * @param bool $raw <false>
     * @return array
     */
    public function concat(array $options, bool $raw = false): array
    {

        $options['output'] = $this->output($options);
        $tempFile = __DIR__ . '/' . md5(uniqid() . mt_rand(100, 999)) . '.plist';
        $plist = [];
        foreach ($options['files'] as $file) {
            $file = realpath($file) . '/' . basename($file);
            (file_exists($file)) && $plist[] = "file '{$file}'";
        }
        $shell = $raws = '';
        if (!empty($plist)) {
            file_put_contents($tempFile, implode("\r\n", $plist));
            unset($plist);

            $shell = vsprintf('%s -y -f concat -safe 0 -i "%s" -c copy %s 2>&1', [
                $this->opts['command'],
                $tempFile,
                $options['output']
            ]);

            $options['callback'] = function ($raws, $ret) use ($tempFile) {
                @unlink($tempFile);
            };
        }
        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 拼接/同时显示
     *
     * 适用于DY视频的同屏幕
     *
     * @param array $inputFileList 媒体列表绝对地址 一维数组
     * @param string $type <v>  h：水平(Horizontal)  v: 垂直(Vertical)
     * @param string|null $outputFile 输出文件(含扩展名的 绝对路径文件名)
     *                                如果不传此参数，将随机生成一个文件到第一个文件所在目录，并保存生成的媒体文件后缀与第一个文件相同
     * @param integer $vIndex <0> 输出文件视频流与 $inputFileList[$vIndex] 文件相同
     * @param integer $aIndex <0> 输出文件音频流与 $inputFileList[$aIndex] 文件相同
     * @return array
     */
    public function concatComplex(array $inputFileList, string $type = "v", string $outputFile = "", int $vIndex = 0, int $aIndex = 0): array
    {
        $type = ($type == "v" || $type == "V") ? "v" : "h";
        $files = [];
        foreach ($inputFileList as $file) {
            $file = dirname($file) . '/' . basename($file);
            (file_exists($file)) && $files[] = $file;
            unset($file);
        }
        $shell = $raws = '';
        if (!empty($files)) {
            //最多支持4个媒体同频
            $files = array_shift(array_chunk($files, 4));
            $options['files'] = implode(" -i ", $files);
            $count = count($files);

            switch ($count) {
                case 2:
                    //pad是将合成的视频宽高，iw: 第一个视频的宽，iw*2: 合成后的视频宽度加倍，ih: 第一个视频的高，合成的两个视频最好分辨率一致。overlay是覆盖，[a][1:v]overlay=w，后面代表是覆盖位置w:0
                    //水平方向
                    // $type == "v" && $complex = "[0:v]pad=iw*2:ih*1[a];[a][1:v]overlay=w;concat=n={$count}:v=${vIndex}:a={$aIndex}";
                    $type == "v" && $complex = "[0:v]pad=iw*2:ih*1[a];[a][1:v]overlay=w";
                    //垂直方向
                    $type == "h" && $complex = "[0:v]pad=iw:ih*2[a];[a][1:v]overlay=0:h";
                    break;
                case 3:
                    //水平方向
                    $type == "v" && $complex = "[0:v]pad=iw*3:ih*1[a];[a][1:v]overlay=w[b];[b][2:v]overlay=2.0*w";
                    //垂直方向
                    $type == "h" && $complex = "[0:v]pad=iw:ih*3[a];[a][1:v]overlay=0:h[b];[b][2:v]overlay=0:2.0*h";
                    break;

                case 4: //2x2方式排列
                    $complex = "[0:v]pad=iw*2:ih*2[a];[a][1:v]overlay=w[b];[b][2:v]overlay=0:h[c];[c][3:v]overlay=w:h";
                    break;
            }

            if (!$outputFile) {
                $outputFile = dirname($files[0]) . '/' . md5(json_encode($files)) . '.' . pathinfo($files[0])['extension'];
            }

            // $shell = vsprintf('%s -y %s -filter_complex \'[0:v]pad=iw*2:ih*2[a];[a][1:v]overlay=w[b];[b][2:v]overlay=0:h[c];[c][3:v]overlay=w:h\' %s 2>&1', [
            $shell = vsprintf("%s -y -i %s -filter_complex '%s' %s 2>&1", [
                $this->opts['command'],
                trim($options['files']),
                $complex,
                $outputFile
            ]);
        }
        $options['output'] = $outputFile;
        return self::_reasponse2($options, $shell, false);
    }

    /*
        $info = $ffmpeg->textWater([
            'file' => $file,
            'fontsize' => 20,
            'fontcolor' => '00ff00',// #00ff00
            'box' => 1, //是否开启文字边框 1是 0否
            'boxcolor' => 'black',//边框背景
            'alpha' => '0.4',
            'text' => 'CAM: {@time2020-12-12 12:13:14}',
            'output_dir' => '',
            'save_name' => '',
            'position' => '0',
            'axio' => '50,50',
            'ttf' => 'ttfs/1.ttf',
            'ext' => 'mp4'
        ]);
        dump($info);
    */
    /**
     * 文字水印
     *
     * @param array $options
     * @param boolean $raw
     * @return array
     */
    public function textWater(array $options, bool $raw = false)
    {
        switch ($options['position']) {
            case '0':
                $axio = explode(',', $options['axio']);
                $x = '(w-tw)-' . abs($axio[0] ?? 1);
                $y = '(h-text_h)-' . abs($axio[1] ?? 1);
                break;
            case '1':
                $x = '1';
                $y = '1';
                break;
            case '2':
                $x = '(w-tw)/2';
                $y = '1';
                break;
            case '3':
                $x = '(w-tw)-1';
                $y = '1';
                break;
            case '4':
                $x = '1';
                $y = '(h-text_h)/2';
                break;
            case '5':
                $x = '(w-tw)/2';
                $y = '(h-text_h)/2';
                break;
            case '6':
                $x = '(w-tw)-1';
                $y = '(h-text_h)/2';
                break;
            case '7':
                $x = '1';
                $y = '(h-text_h)';
                break;
            case '8':
                $x = '(w-tw)/2';
                $y = '(h-text_h)';
                break;
            case '9':
            default:
                $x = '(w-tw)-1';
                $y = '(h-text_h)';
                break;
        }

        $drawtext = 'drawtext=fontfile=' . $options['ttf'];
        $drawtext .= ": x=$x:y=$y";
        if ($options['fontsize']) {
            $drawtext .= ": fontsize={$options['fontsize']}";
        }
        if ($options['fontcolor']) {
            $drawtext .= ": fontcolor={$options['fontcolor']}";
        }
        if ($options['box']) {
            $drawtext .= ": box={$options['box']}";
            $drawtext .= ": boxborderw=7";
            $drawtext .= ": boxcolor={$options['boxcolor']}@" . (floatval($options['alpha']) ?? 1);
        }
        if ($options['text']) {
            if (preg_match("/\{@time([0-9\:\ ]+)\}/", $options['text'], $result)) {
                if (strrpos($result[1], ":")) {
                    $result[1] = strtotime($result[1]);
                } else {
                    $result[1] = substr($result[1], 0, 10);
                }
                $options['text'] = str_replace($result[0], '%{pts:gmtime:' . ($result[1] + 3600 * 8) . '}', $options['text']);
                $options['text'] = str_replace(':', '\\:', $options['text']);
            }
            $drawtext .= ": text='{$options['text']}'";
        }
        $meta = self::getInfo($options['file']);
        $options['output'] = $this->output($options, $meta['basic']);
        $shell = vsprintf('%s -y -r %s -i "%s" -c:a copy -v:b %sk -vf "%s" %s 2>&1', [
            $this->opts['command'],
            ($meta['video']['fps'] ?? 24),
            File::pathToUnix($options['file']),
            $meta['basic']['bitrate'],
            $drawtext,
            $options['output']
        ]);

        return self::_reasponse2($options, $shell, $raw);
        // /usr/bin/ffmpeg -y -r 30 -i 111.mp4 -vf "drawtext=fontfile=ttfs/1.ttf: x=w-tw-10:y=10: fontsize=36:fontcolor=yellow: box=1:boxcolor=black@0.4: text='Wall Clock Time\: %{pts\:gmtime\:1456007118}'" 111.mp4
    }

    /*
        $info = $ffmpeg->scaleReset([
            'file' => $file,
            'scale' => '320x240',
            'output_dir' => '',
        ]);
        dump($info);
    */
    /**
     * 调整尺寸(filter:scale)
     *
     * @param array $options
     *                  -- file {string} 媒体文件列表绝对地址
     *                  -- scale {string} 目标尺寸 （固定缩放到: 320x240 、320:240 ； 自适应缩放到: 320: 、320:-1
     *
     *                  -- ext {string} <null> 输入文件格式  默认保留原始格式
     *                  -- output_dir {string} <"./"> 输出目录（建议指定绝对路径)
     *                  -- save_name {string} <null> 输出媒体文件名称(不含扩展名)
     * @param bool $raw <false>
     *
     * @return array
     */
    public function scaleResize(array $options, bool $raw = false): array
    {

        $options['output'] = $this->output($options);

        $options['scale'] = $options['scale'] ?? "";
        preg_match("/^(\d+):(-1|\d+)?$/", trim($options['scale']), $matches);

        if (!isset($matches[2])) {
            throw new \Exception("Video filter:scale Syntax error!");
        }

        // ./ffmpeg -i input.mp4 -vf scale=960:540 output.mp4
        $info = $this->getInfo($options['file']);
        $shell = vsprintf('%s -y -i %s -c:a copy -v:b %sk -vf scale=%s %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $info['basic']['bitrate'],
            $matches[0],
            $options['output']
        ]);
        unset($info, $matches);
        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 视频裁剪(filter:crop)
     *
     * https://blog.csdn.net/caohang103215/article/details/72638751?utm_medium=distribute.pc_relevant.none-task-blog-searchFromBaidu-3.control&depth_1-utm_source=distribute.pc_relevant.none-task-blog-searchFromBaidu-3.control
     * https://www.cnblogs.com/yongfengnice/p/7095846.html ffmpeg调整缩放裁剪视频的基础知识
     * https://www.cnblogs.com/yongfengnice/p/7099172.html ffmpeg填充、翻动、旋转视频的基础知识
     * https://blog.csdn.net/ternence_hsu/article/details/109705234
     *
     * @param array $options
     *                  -- file {string} 媒体文件列表绝对地址
     *                  -- scale {string} 目标尺寸 （固定缩放到: 320x240 、320:240 ； 自适应缩放到: 320: 、320:-1
     *                  -- axis {string} <0,0> 裁剪的左上边坐标
     *                  -- seconds {integer} <null> 裁剪时长（秒数） 默认全部时长
     *
     *                  -- ext {string} <null> 输入文件格式  默认保留原始格式
     *                  -- output_dir {string} <"./"> 输出目录（建议指定绝对路径)
     *                  -- save_name {string} <null> 输出媒体文件名称(不含扩展名)
     * @param bool $raw <false>
     *
     * @return array
     */
    public function cropPlus(array $options, bool $raw = false): array
    {
        $options['output'] = $this->output($options);

        $crop = str_replace(['x', "X", ',', "|", "_"], ':', str_replace(' ', '', $options['scale']));
        if (isset($options['axis']) && $options['axis']) {
            $crop .= ":" . str_replace(['x', "X", ',', "|", "_", "."], ':', str_replace(' ', '', $options['axis']));
        }
        $info = $this->getInfo($options['file']);
        $shell = vsprintf("%s -y -i %s -vf crop='%s'%s -c:a copy -v:b %sk %s 2>&1", [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $crop,
            (isset($options['seconds']) && $options['seconds'] > 0) ? (' -t ' . intval($options['seconds'])) : '',
            $info['basic']['bitrate'],
            $options['output']
        ]);

        //TODO vipkwd
        $options['callback'] = function ($raws, $ret) use ($options) {
            $r = null;
            if (is_file($options['output'])) {
                $r = $this->getInfo($options['output']);
            }
            unset($raws, $ret, $options);
            return $r;
        };
        return self::_reasponse2($options, $shell, $raw);

        // $raws = $this->exec($shell);

        // $ret['source'] = $options['file'];
        // if (!is_file($output)) {
        //     $ret['msg'] = '处理错误';
        // } else {
        //     $ret = array_merge($ret, $this->getInfo($output));
        // }
        // return $this->_reasponse($ret, $raws, $shell);
    }
    /**
     * 添加图片水印(如 电视台标)
     *
     * @param array $options
     *                  -- position"  <lt> 水印位置 // lt/rt/rb/lb/lr/rl
     *                  -- file <''> 媒体文件
     *                  -- image <''> 水印文件
     *                  -- output_dir <''> 输出目录 默认输出到媒体文件目录
     *                  -- save_name <''> 默认随机名
     * @param bool $raw <false>
     * @return array
     */
    public function imageWater(array $options, bool $raw = false)
    {
        $options = array_merge([
            "position" => "lt", // lt/rt/rb/lb/lr/rl
            "file" => "",
            "image" => "",
            "output_dir" => "",
            "save_name" => ""
        ], $options);

        $options['output'] = $this->output($options);

        // 加 1px 是为解决在去除水印时能规避 贴边(0,0) 选取框选水印时命令报错的问题
        switch ($options['position']) {
            case "lr":
                $options['position'] = "overlay=x='if(gte(t,1), -w+(t-2)*200, NAN)':y=40";
                break; //左到右
            case "rl":
                $options['position'] = "overlay=x='if(gte(t,1), W-(t-2)*200, NAN)':y=40";
                break; //右到左
            case "rt":
                $options['position'] = "overlay=W-w";
                break; //右上角
            case "rb":
                $options['position'] = "overlay=W-w:H-h";
                break; //右下角
            case "lb":
                $options['position'] = "overlay=1:H-h";
                break; //左下角
            case "lt": //左上角
            default:
                $options['position'] = "overlay=1:1";
                break; //
                // default: $options['position'] = "overlay=0+t*20:0+t*10"; break;
        }
        $options['file'] = File::realpath($options['file']);
        $options['image'] = File::realpath($options['image']);

        $shell = $raws = "";
        $msg = '处理错误';
        if (!is_file($options['file']) || !File::isImage($options['image'])) {
            $msg = "媒体或水印文件不存在";
        } else {
            $info = $this->getInfo($options['file']);
            $shell = vsprintf("%s -y -i %s -i %s -filter_complex \"%s\" -c:a copy -b:v %sk %s 2>&1", [
                $this->opts['command'],
                File::pathToUnix($options['file']),
                $options['image'],
                $options['position'],
                $info['basic']['bitrate'],
                $options['output']
            ]);
            return self::_reasponse2($options, $shell, $raw);
        }
        $ret['source'] = $options['file'];
        if (!is_file($options['output'])) {
            $ret['msg'] = $msg;
        } else {
            //$ret = array_merge($ret, $this->getInfo($output));
        }
        return $this->_reasponse($ret, $raws, $shell);
    }

    //delogo过滤器
    /**
     * 去除静态水印
     *
     * @param array $options
     *                  -- file <"">
     *                  -- xy <1x1> 水印在视频中的左上角位置
     *                  -- wh <> 水印宽高
     * @param bool $raw <false>
     * @return array
     */
    public function removeImageWater(array $options, bool $raw = false)
    {
        // 语法：-vf delogo=x:y:w:h[:show]
        // x:y 离左上角的坐标
        // w:h logo的宽和高
        // show：若设置为1有一个绿色的矩形，默认值0。
        // ffmpeg -i input.mp4 -vf delogo=0:0:220:90:1 output.mp4
        $options = array_merge([
            "file" => "",
            "xy" => "",
            "wh" => "",
            "show" => 0
        ], $options);

        $options['file'] = File::realpath($options['file']);
        $options['output'] = $this->output($options);

        preg_match("/(\d+)[\:xX\-\.](\d+)/", $options['xy'], $xy);
        if (!isset($xy[2])) {
            return $this->_reasponse(["msg" => "离左上角的坐标无效"], "", "");
        }
        $options['xy'] = "x=" . ($xy[1] + 1) . ":y=" . ($xy[2] + 1);

        preg_match("/(\d+)[\:xX\-\.](\d+)/", $options['wh'], $wh);
        if (!isset($wh[2])) {
            return $this->_reasponse(["msg" => "水印宽高设置无效"], "", "");
        }
        $options['wh'] = "w={$wh[1]}:h={$wh[2]}";
        $options['show'] = "show=" . intval($options['show']);

        $info = $this->getInfo($options['file']);

        //Todo kb
        $shell = vsprintf('%s -y -i %s -c:a copy -b:v %sk -vf "delogo=%s:%s:%s" %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $info['basic']['bitrate'],
            $options["xy"],
            $options["wh"],
            $options["show"],
            $options['output']
        ]);

        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 去除片头、片尾
     *
     * @param array $options
     *                  -- file
     *                  -- start <0> 片头持续秒数 默认0秒
     *                  -- end <0>  片尾持续秒数 默认0秒
     *                  -- output_dir <''>
     * @param bool $raw <false>
     * @return array
     */
    public function cropTitleEnding(array $options, bool $raw = false)
    {
        $options = array_merge([
            "file"  => "",
            "start" => 0,
            "end"   => 0
        ], $options);
        // $options['start'] = $options['start'] > 0 ? intval($options['start']) : 0;
        $options['start'] = $this->secondsFormat($options['start'], 'float');
        $options['end'] = $options['end'] > 0 ? intval($options['end']) : 0;
        $options['file'] = File::realpath($options['file']);
        $info = $this->getInfo($options['file']);
        $options['output'] = $this->output($options);

        $t = $info['basic']['seconds'] - $options['start'] - $options['end'];
        if ($t < 1) {
            return $this->_reasponse(["msg" => "时长设置溢出"], "", "");
        }
        $options['start'] =  $this->secondsFormat($options['start'], "string");
        $shell = vsprintf('%s -y -i %s -b:v %sk -c:v copy -c:a copy -ss %s -t %s %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $info['basic']['bitrate'],
            $options['start'],
            $t,
            $options['output']
        ]);
        unset(
            $info,
            $t,
        );
        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 视频抽取图片
     *
     * @param string $file <> 视频地址
     * @param integer $rate <1> 抽取帧数
     * @param string $output_dir <> 保存地址，默认空，保存到视频所在目录
     * @param string $ss <"0"> 截取位置 默认0秒（即视频开头位置）
     * @param int $t <0> 默认0 表截取到视频结尾 单位秒
     * @return array
     */
    public function exportImage(string $file, int $rate = 1, string $output_dir = "", string $ss = "0", int $t = 0)
    {
        // ffmpeg –i test.avi –r 1 –f image2 image-%3d.jpeg        //提取图片
        // ffmpeg -i input.mp4 -ss 00:00:20 -t 10 -r 1 -q:v 2 -f image2 pic-%03d.jpeg
        // -ss 表示开始时间 -t表示共要多少时间。
        // 如此，ffmpeg会从input.mp4的第20s时间开始，往下10s，即20~30s这10秒钟之间，每隔1s就抓一帧，总共会抓10帧
        // pic-001.jpeg
        // ...
        // pic-010.jpeg
        set_time_limit(0);
        $rate = $rate > 1 ? intval($rate) : 1;
        $file = File::realpath($file);

        $output = $this->output([
            "file" => $file,
            "output_dir" => $output_dir,
        ]);

        //生成临时目录
        $temp_hash = '.' . VipkwdStr::md5_16(VipkwdStr::uuid() . time());
        $_output = File::dirname($output) . '/' . $temp_hash;
        File::createDir($_output);
        $output = str_replace(File::dirname($output), $_output, $output);

        $name_rule = "pic_" . date('Ymd') . "_%05d.jpg";
        $output = File::dirname($output) . '/' . $name_rule;

        $t = ($t > 0) ? "-t " . intval($t) : "";

        $shell = vsprintf('%s -y -i %s -ss %s %s -r %s -q:v 2 -f image2 %s 2>&1', [
            $this->opts['command'],
            $file,
            $ss,
            $t,
            $rate,
            $output
        ]);

        $raws = $this->exec($shell);

        $ret['source'] = $file;
        $ret['output'] = File::dirname(File::dirname($output));
        $ret['shell'] = '';
        $ret['images'] = [];
        $raw_debug = false;
        if (!is_file(str_replace('%05d', "00001", $output))) {
            $ret['msg'] = '处理错误';
            $raw_debug = true;
        } else {
            foreach (glob(str_replace('_%05d.', '*.', $output)) as $index => $file) {
                $new = str_replace($temp_hash . '/', '', $file);
                File::rename($file, $new);
                $ret['images'][] = File::pathToUnix(File::realpath($new));
                unset($new, $index, $file);
            }
            File::delete($_output);
        }
        unset(
            $rate,
            $temp_hash,
            $_output,
            $output,
            $t,
            $name_rule
        );
        return $this->_reasponse($ret, $raws, $shell, $raw_debug);
    }

    /**
     * 图片(批量)合并为视频
     * 
     * @param array options
     * @param bool $raw <false>
     * 
     * @return array
     */
    public function mergeImage(array $options, bool $raw = false)
    {
        $options = array_merge([
            // 等待合并的图片文件路劲（文件名以 占位符 标识）
            'file' => 'pic_%05d.jpg', // 则目录在必须有 pic_00001.jpg的图片存在
            'ext' => 'mp4',
            // -r 调整帧率： 不指定帧率的话，ffmpeg会使用默认的25帧，也就是1秒钟拼接25张图片，我们可以通过调整帧率的大小来控制最终生成视频的时长
            'rate' => 25,
            // -crf 调整视频质量：用以平衡视频质量和文件大小的参数，FFMPEG里取值范围为0-51，取值越高内容损失越多，视频质量更差。 ffmpeg的默认值是23，建议的取值范围是17-28。
            'crf' => 23,
            // -b:v 调整视频码率：注意 改变码率会影响到视频清晰度，但并不意味着高码率的视频一定比低码率的视频清晰度更高，这还取决于视频编码格式，比如h265编码可以用更小的码率生成h264同等的视频质量，像av1、v8、v9等编码也优于h264
            'bv' => null,
            // -c:v 调整视频的编码格式：目前ffmpeg针对于mp4默认使用的是h264，你可以使用-c:v libx265生成同等质量，但文件更小的h265视频
            'cv' => 'libx265', //,'h264'
            //调整视频分辨率, 支持参数: 调整视频分辨率:  640x480固定分辨率；-1:480 缩放宽度固定高度；640:-1固定宽度缩放高度; iw/1:ih/2 宽高各一半
            'vf' => null,

        ], $options);

        $options['file'] = File::realpath($options['file']);;

        $commandK = ["%s -y -f image2", "-i %s"];
        $commandV = [$this->opts['command'], $options['file']];

        if ($options['vf'] !== null) {
            $options['vf'] = str_replace(' ', '', $options['vf']);
            if (preg_match("/^(\d+)[xX](\d+)$/", $options['vf'], $matche1)) {
                $options['vf'] = $matche1[0];
                $commandK[] = "-s %s";
                $commandV[] = $matche1[0];
            } elseif (preg_match("/^(\d+)(\:\-1:)$/", $options['vf'], $matche2)) {
                $options['vf'] = $matche2[0];
                $commandK[] = "-filter:v scale=%s";
                $commandV[] = $matche2[0];
            } elseif (preg_match("/^(\-1\:)(\d+)$/", $options['vf'], $matche3)) {
                $options['vf'] = $matche3[0];
                $commandK[] = "-filter:v scale=%s";
                $commandV[] = $matche3[0];
            } elseif (preg_match("/^(iw\/\d+:)(ih\/\d+)$/", $options['vf'], $matche4)) { //iw/1:ih/2
                $options['vf'] = $matche4[0];
                $commandK[] = "-vf scale=%s";
                $commandV[] = $matche4[0];
            } else {
                $options['vf'] = null;
            }
        }
        if ($options['ext'] == 'webm') {
            $options['cv'] = 'libvpx-vp9';
        }
        if ($options['bv'] !== null) {
            $options['bv'] = str_replace(' ', '', $options['bv']);
            $commandK[] = "-b:v %s";
            $commandV[] = $options['bv'];
        }

        if ($options['cv']) {
            $commandK[] = "-c:v %s";
            $commandV[] = $options['cv'];
        }
        // 
        if ($options['rate'] > 0) {
            $commandK[] = "-r %d";
            $commandV[] = $options['rate'];
        }

        if ($options['crf'] >= 0 && $options['crf'] <= 51) {
            $commandK[] = "-crf %d";
            $commandV[] = intval($options['crf']);
        }

        $commandK[] = "%s 2>&1";
        $commandV[] = $options['output'] = $this->output($options);
        $shell = vsprintf(implode(' ', $commandK), $commandV);
        return self::_reasponse2($options, $shell, $raw);
    }

    /**
     * 获取媒体yuv源
     *
     * @param array $options
     *                  -- file
     * @param bool $raw <false>
     * @return array
     */
    public function getOriginYuv(array $options, bool $raw = false)
    {

        $options['file'] = File::realpath($options['file']);
        $options['output'] = $this->output($options) . '.yuv';
        $info = $this->getInfo($options['file']);
        $shell = vsprintf('%s -y -i %s -c:a copy -b:v %sk %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $info['basic']['bitrate'],
            $options['output']
        ]);
        return self::_reasponse2($options, $shell, $raw);
        // ffmpeg -i input.mp4 output.yuv
    }


    /**
     * 合并m3u-TS为MP4
     *
     * @param array $options
     *                  -- file m3u8文件地址(本地绝对路径或http网络地址)
     * @param bool $raw <false>
     * @return array
     */
    public function m3u8ToMp4(array $options, bool $raw = false)
    {

        $options = $this->defaults($options, [
            "ext" => "mp4"
        ]);
        $options['output'] = $this->output($options);
        $shell = vsprintf('%s -y -i %s %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $options['output']
        ]);
        return self::_reasponse2($options, $shell, $raw);
        // ffmpeg -i http://www.xxx.com/xxx.m3u8 name.mp4
    }

    /**
     * 音/视频封装新视频（合并多轨）
     * 
     * @param array $options 各媒体文件列表, 生成后的视频各媒体轨道媒体按options一维数组自然序列呈现
     * @param string $ext <mp4> 封装完成的媒体文件类型
     * @param bool $raw <false> 是否响应FFmpeg原始处理信息
     */
    public function package(array $options, string $ext = 'mp4', bool $raw = false)
    {
        set_time_limit(0);
        if (!isset($options['file'])) {
            $options = ['file' => $options];
        }
        $options = $this->defaults($options, [
            "ext" => $ext ? $ext : 'mp4'
        ]);
        $options['output'] = $this->output($options);

        $files = [];
        foreach ($options['file'] as $file) {
            $files[] = '"' . File::pathToUnix($file) . '"';
        }

        $shell = vsprintf('%s -y -i %s –vcodec copy –acodec copy %s 2>&1', [
            $this->opts['command'],
            implode(' -i ', $files),
            $options['output']
        ]);
        return self::_reasponse2($options, $shell, $raw);
    }

    public function imgToMp4(string $imgPath, bool $raw = false)
    {
        $options = $this->defaults(['file' => $imgPath], [
            "ext" => 'mp4'
        ]);
        $options['output'] = $this->output($options);

        $shell = vsprintf('%s -y -i %s -an -vcodec libx264 -coder 1 -flags +loop -cmp +chroma -subq 10 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -trellis 2 -partitions +parti8x8+parti4x4 -crf 24 -threads 0 -r 25 -g 25 -y %s 2>&1', [
            $this->opts['command'],
            File::pathToUnix($options['file']),
            $options['output']
        ]);
        return self::_reasponse2($options, $shell, $raw);
        // ffmpeg.exe -i INPUT.jpg -an -vcodec libx264 -coder 1 -flags +loop -cmp +chroma -subq 10 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -flags2 +dct8x8 -trellis 2 -partitions +parti8x8+parti4x4 -crf 24 -threads 0 -r 25 -g 25 -y OUTPUT.mp4
    }

    // https://blog.csdn.net/wh8_2011/article/details/52117932#
    // 视频封装
    // ffmpeg –i video_file –i audio_file –vcodec copy –acodec copy output_file

    // 视频剪切
    // ffmpeg –i test.avi –r 1 –f image2 image-%3d.jpeg        //提取图片
    // ffmpeg -ss 0:1:30 -t 0:0:20 -i input.avi -vcodec copy -acodec copy output.avi    //剪切视频
    // //-r 提取图像的频率，-ss 开始时间，-t 持续时间

    // 视频录制
    // ffmpeg –i rtsp://192.168.3.205:5555/test –vcodec copy out.avi

    // 直播媒体保存至本地文件
    // ffmpeg -i rtmp://server/live/streamName -c copy dump.flv

    // 将其中一个直播流，视频改用h264压缩，音频不变，送至另外一个直播服务流
    // ffmpeg -i rtmp://server/live/originalStream -c:a copy -c:v libx264 -vpre slow -f flv rtmp://server/live/h264Stream


    // 将其中一个直播流，视频改用h264压缩，音频改用faac压缩，送至另外一个直播服务流
    // ffmpeg -i rtmp://server/live/originalStream -acodec libfaac -ar 44100 -ab 48k -vcodec copy -f flv rtmp://server/live/h264_AAC_Stream

    // 6、将一个高清流，复制为几个不同视频清晰度的流重新发布，其中音频不变
    // ffmpeg -re -i rtmp://server/live/high_FMLE_stream -acodec copy -vcodec x264lib -s 640×360 -b 500k -vpre medium -vpre baseline rtmp://server/live/baseline_500k -acodec copy -vcodec x264lib -s 480×272 -b 300k -vpre medium -vpre baseline rtmp://server/live/baseline_300k -acodec copy -vcodec x264lib -s 320×200 -b 150k -vpre medium -vpre baseline rtmp://server/live/baseline_150k -acodec libfaac -vn -ab 48k rtmp://server/live/audio_only_AAC_48k

    // 8、将当前摄像头及音频通过DSSHOW采集，视频h264、音频faac压缩后发布
    // ffmpeg -r 25 -f dshow -s 640×480 -i video="video source name" audio="audio source name" -vcodec libx264 -b 600k -vpre slow -acodec libfaac -ab 128k -f flv rtmp://server/application/stream_name

    // 9、将一个JPG图片经过h264压缩循环输出为mp4视频
    // ffmpeg.exe -i INPUT.jpg -an -vcodec libx264 -coder 1 -flags +loop -cmp +chroma -subq 10 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -flags2 +dct8x8 -trellis 2 -partitions +parti8x8+parti4x4 -crf 24 -threads 0 -r 25 -g 25 -y OUTPUT.mp4

    // 10、将普通流视频改用h264压缩，音频不变，送至高清流服务(新版本FMS live=1)
    // ffmpeg -i rtmp://server/live/originalStream -c:a copy -c:v libx264 -vpre slow -f flv “rtmp://server/live/h264Stream live=1〃





    // YUV序列播放 ffplay -f rawvideo -video_size 1920x1080 input.yuv

    // YUV序列转AVI ffmpeg –s w*h –pix_fmt yuv420p –i input.yuv –vcodec mpeg4 output.avi

    // 常用参数说明：
    //   主要参数：
    //     -i 设定输入流
    //     -f 设定输出格式
    //     -ss 开始时间
    //   视频参数：
    //     -b 设定视频流量，默认为200Kbit/s
    //     -r 设定帧速率，默认为25
    //     -s 设定画面的宽与高
    //     -aspect 设定画面的比例
    //     -vn 不处理视频
    //     -vcodec 设定视频编解码器，未设定时则使用与输入流相同的编解码器
    //   音频参数：
    //     -ar 设定采样率
    //     -ac 设定声音的Channel数
    //     -acodec 设定声音编解码器，未设定时则使用与输入流相同的编解码器
    //     -an 不处理音频


    // https://blog.csdn.net/weixin_30904593/article/details/96070167 ffmpeg加文字水印并控制水印显示时间或显示周期

    // https://blog.csdn.net/kingvon_liwei/article/details/79271361
    // https://blog.csdn.net/u014162133/article/details/86705656 FFmpeg的filter基本用法
    // https://blog.csdn.net/thomashtq/article/details/44940457#
    // 实现慢速播放，声音速度是原始速度的50% ffplay p629100.mp3 -af atempo=0.5
    // 如testsrc视频按顺时针方向旋转90度 ffplay -f lavfi -i testsrc -vf transpose=1
    // 如testsrc视频水平翻转(左右翻转) ffplay -f lavfi -i testsrc -vf hflip
    // 顺时针旋转90度并水平翻转 ffplay -f lavfi -i testsrc -vf transpose=1,hflip
    // 第一步： 源视频宽度扩大两倍。 ffmpeg -i jidu.mp4 -t 10 -vf pad=2*iw output.mp4
    // 第二步：源视频水平翻转 ffmpeg -i jidu.mp4 -t 10 -vf hflip output2.mp4
    // 第三步：水平翻转视频覆盖output.mp4 ffmpeg -i output.mp4 -i output2.mp4 -filter_complex overlay=w compare.mp4

    // ffmpeg参数使用说明
    // https://blog.csdn.net/ctthen/article/details/4299104
    // https://blog.csdn.net/fanyun_01/article/details/103299866
}
