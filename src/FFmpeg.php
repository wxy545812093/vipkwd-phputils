<?php
/**
 * @name 媒体处理
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\{Tools, File, Ip, Str as VipkwdStr};
use Vipkwd\Utils\Libs\Upload as VipkwdUpload;
use \Exception;
class FFmpeg{

    private $opts;

    public function __construct($options = array()){
        $this->opts = array_merge([
            'command' => '/usr/local/bin/ffmpeg',
            'output_dir' => ''
        ], $options);
        if(!is_file($this->opts['command'])){
            throw new Exception(sprintf("Ffmpeg command \"%s\" not found",$this->opts['command']));
        }
        if(!is_executable($this->opts['command'])){
            throw new Exception(sprintf("%s: Permission denied",$this->opts['command']));
        }
        $this->parsePathSeparator();

        //TODO others
    }

    // public function instance(array $options = []){
    //   return new self($options);
    // }

    /**
     * 获取媒体信息
     *
     * @param string $filepath 文件绝对地址
     * @param boolean $rawInfo <false> 是否响应ffmpeg原始信息
     * @return array
     */
    public function getInfo(string $filepath, bool $rawInfo = false):array {

        if( true !== ($result =  $this->validateFile($filepath)) ){
            return $result;
        }

        $shell = vsprintf('%s -i "%s" 2>&1', [
            $this->opts['command'],
            $filepath
        ]);

        $raws = $this->exec($shell);

        // 通过使用输出缓冲，获取到ffmpeg所有输出的内容。
        $ret = ['basic'=>[], 'video'=>'', 'audio'=>''];
        $ret['basic'] = self::fileBaseInfo($filepath);

        // Metadata:
        // major_brand     : qt  
        // minor_version   : 0
        // compatible_brands: qt  
        // creation_time   : 2020-08-08T07:04:03.000000Z
        // com.apple.quicktime.location.ISO6709: +22.7832+114.4695+032.000/
        // com.apple.quicktime.make: Apple
        // com.apple.quicktime.model: iPhone X
        // com.apple.quicktime.software: 13.6
        // com.apple.quicktime.creationdate: 2020-08-08T15:04:03+0800
        if (preg_match("/com\.apple\.quicktime\.make: (\S+)/is", $raws, $match)) {
          $ret['basic']['device_make'] = $match[1];
        }
        if (preg_match("/com\.apple\.quicktime\.model: (\S+)/is", $raws, $match)) {
          $ret['basic']['device_model'] = $match[1];
        }
        if (preg_match("/com\.apple\.quicktime\.software: (\S+)/is", $raws, $match)) {
          $ret['basic']['device_software'] = $match[1];
        }
        // Duration: 01:24:12.73, start: 0.000000, bitrate: 456 kb/s
        if (preg_match("/Duration: (.*?), start: (.*?),\ +bitrate: (\d*) kb\/s/", $raws, $match)) {
            $da = explode(':', $match[1]);
            $ret['basic']['duration'] = $match[1]; // 提取出播放时间
            $ret['basic']['seconds'] = $da[0] * 3600 + $da[1] * 60 + $da[2]; // 转换为秒
            $ret['basic']['start'] = $match[2]; // 开始时间
            $ret['basic']['bitrate'] = $match[3]; // bitrate 码率 单位 kb
        }

        // Stream #0.1: Video: rv40, yuv420p, 512x384, 355 kb/s, 12.05 fps, 12 tbr, 1k tbn, 12 tbc
        // Stream #0:1(und): Video: h264 (High) (avc1 / 0x31637661), yuv420p(tv, smpte170m/bt470bg/smpte170m), 448x960, 1209 kb/s, 30.18 fps, 30.18 tbr, 180k tbn, 360k tbc (default)
        if (preg_match("/Video: (.*?), (.*?), (\d+x\d+).*?,\ +(\d*) kb\/s.*?,\ +([0-9\.]+)\ +fps/is", $raws, $match)) {
            preg_match("/Video: (.*)rotate\ +:\ +(\d+)/is", $raws, $rotate);
            preg_match("/Video: (.*)creation_time\ +:\ +([0-9-A-Za-z\:\.]+)/is", $raws, $creation_time);
            $a = explode('x', $match[3]);
            $ret['video'] = [
                'vcodec'  => $match[1], // 编码格式
                'vformat' => $match[2], // 视频格式
                'resolution' => $match[3], // 分辨率
                'width'  => $a[0],
                'height' => $a[1],
                'bitrate' => $match[4],
                'fps' => $match[5],
                'rotate' => isset($rotate[2]) ? $rotate[2] : '',
                'creation_time' => isset($creation_time[2]) ? $creation_time[2] : '',
            ];
            //dump($ret['video'], $match, $creation_time);
        }
        // Stream #0.0: Audio: cook, 44100 Hz, stereo, s16, 96 kb/s
        if (preg_match("/Audio: ([0-9a-zA-Z\(\)\ \/\.\-]+), (\d*) Hz(.*?, (\d*) kb\/s)?/is", $raws, $match)) {
            preg_match("/Audio: (.*)creation_time\ +:\ +([0-9-A-Za-z\:\.]+)/is", $raws, $creation_time);
            $ret['audio'] = [
                'acodec' => $match[1],       // 音频编码
                'asamplerate' => $match[2],  // 音频采样频率
                'bitrate' => $match[4],
                'creation_time' => isset($creation_time[2]) ? $creation_time[2] : '',
            ];
        }

        if (isset($ret['basic']['seconds']) && isset($ret['basic']['start'])) {
            $ret['basic']['play_time'] = $ret['basic']['seconds'] + $ret['basic']['start']; // 实际播放时间
        }

        return $this->_reasponse($ret, $raws, $shell,  $rawInfo);
    }

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
     * 
     * @return array
     */
    public function rotate(array $options):array {
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
        if( true !== ($result =  $this->validateFile($options['file'])) ){
            return $result;
        }

        if( ($mod = $options['degrees'] % 90 ) > 0){
            $options['degrees'] -= $mod;
        }
        if($options['direction'] == 'r'){
            $directionkey = 1;
        }else{
            $directionkey = 2;
        }

        $text = "transpose={$directionkey}";
        for($count = ($options['degrees'] / 90); $count>1; $count--){
            $text .=",transpose={$directionkey}";
        }
        
        $output = $this->output($options);

        $shell = vsprintf('%s -y -i "%s" -vf "%s" %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $text,
            $output
        ]);

        $raws = $this->exec($shell);

        if(is_file($output)){
            if(isset($options['replace']) && $options['replace'] === true){
                $file = dirname($output) .'/'. basename($options['file']);
                rename($output, $file);
            }
            $ret = [
                'source' => $options['file'],
                'nfile' => $file ?? $output
            ];
        }else{
            $ret = [
                'source' => $options['file'],
                'msg'=>'处理错误',
            ];
        }
        return $this->_reasponse($ret, $raws, $shell);
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
     * 
     * @return array
     */
    public function cut(array $options):array{

        $output = $this->output($options);

        $shell = vsprintf('%s -y -ss %s -t %s -accurate_seek -i "%s" -vcodec copy -acodec copy -avoid_negative_ts 1 %s 2>&1', [
            $this->opts['command'],
            $options['start'],
            $options['end'],
            $options['file'],
            $output
        ]);
        $raws = $this->exec($shell);

        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);
    }

    /**
     * 合并图片为视频
     *
     * @param array $options
     *                  -- rate <1> 每秒帧数
     *                  -- vcodec <mpeg4>
     *                  -- file <""> 图片列表正则 "/Users/Pictures/2018/A7_%05d.JPG"
     * 
     * @return array
     */
    public function mergeImage(array $options){
        $options = array_merge([
            "rate"      => 1,
            "vcodec"    => "mpeg4",// "huffyuv/libx264
            "file"      => "", // "/data/path/images/images%d.png",
            "save_name" => "",
            "output_dir"=> ""
        ], $options);

        $output = $raws = "";
        $msg = '图片列表正则解析无效';
        $i = preg_match("/\%(0)([1-9](\d)?)d/", $options['file'], $match);
        if(isset($match[2])){

            $number = str_pad("1", intval($match[2]), $match[1], STR_PAD_LEFT);
            $file = str_replace($match[0], $number, $options['file']);

            if(File::isImage($file)){
                if(!is_dir($options['output_dir']) || $options['output_dir'] == ""){
                    $options['output_dir'] = File::dirname($file);
                }
                if(!$options['save_name']){
                    $options['save_name'] = Tools::md5_16(Tools::uuid()).".mp4";
                }
                $output = $options['output_dir'] . '/' . $options['save_name'];

                $shell = vsprintf('%s -y -f image2 -r %s -i "%s" -vcodec %s %s 2>&1', [
                    $this->opts['command'],
                    $options['rate'],
                    $options['file'],
                    $options['vcodec'],
                    $output
                ]);
                $raws = $this->exec($shell);
            }
            $msg = '处理错误';
        }

        $ret['source'] = $options['file'];
        if( !$output || !is_file($output)){
            $ret['msg'] = $msg;
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);

        //./bin/ffmpeg.exe -y -r 1 -i 'images/%d.png' -vcodec huffyuv images.avi
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
     * @return array
     */
    public function concat(array $options):array{
        
        $output = $this->output($options);
        $tempFile = __DIR__.'/'.md5( uniqid() . mt_rand(100, 999)).'.plist';
        $plist = [];
        foreach($options['files'] as $file){
          $file = realpath($file).'/'. basename($file);
          (file_exists($file)) && $plist[] = "file '{$file}'";
        }
        $shell = $raws = '';
        if(!empty($plist)){
            file_put_contents($tempFile, implode("\r\n", $plist));
            unset($plist);
    
            $shell = vsprintf('%s -y -f concat -safe 0 -i "%s" -c copy %s 2>&1', [
                $this->opts['command'],
                $tempFile,
                $output
            ]);
            $raws = $this->exec($shell);
            
            @unlink($tempFile);
        }

        // $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);
    }

    /**
     * 拼接/同时显示
     * 
     * 适用于DY视频的同屏幕
     *
     * @param array $inputFileList 媒体文件列表绝对地址 一维数组
     * @param string $type <v>  h：水平(Horizontal)  v: 垂直(Vertical)
     * @param string|null $outputFile 输出文件(含扩展名的 绝对路径文件名)
     *                                如果不传此参数，将随机生成一个文件到第一个文件所在目录，并保存生成的媒体文件后缀与第一个文件相同
     * @param integer $vIndex <0> 输出文件视频流与 $inputFileList[$vIndex] 文件相同
     * @param integer $aIndex <0> 输出文件音频流与 $inputFileList[$aIndex] 文件相同
     * @return array
     */
    public function concatComplex(array $inputFileList, string $type = "v", string $outputFile="", int $vIndex=0, int $aIndex=0):array{
      $type = ($type == "v" || $type == "V") ? "v" : "h";
      $files = [];
      foreach($inputFileList as $file){
        $file = dirname($file).'/'. basename($file);
        (file_exists($file)) && $files[] = $file;
        unset($file);
      }
      $shell = $raws = '';
      if(!empty($files)){
          //最多支持4个媒体同频
          $files = array_shift(array_chunk($files,4));
          $options['files'] = implode(" -i ", $files);
          $count = count($files);

          switch($count){
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

          if(!$outputFile){
            $outputFile = dirname($files[0]).'/'.md5(json_encode($files)).'.'.pathinfo($files[0])['extension'];
          }

          // $shell = vsprintf('%s -y %s -filter_complex \'[0:v]pad=iw*2:ih*2[a];[a][1:v]overlay=w[b];[b][2:v]overlay=0:h[c];[c][3:v]overlay=w:h\' %s 2>&1', [
          $shell = vsprintf("%s -y -i %s -filter_complex '%s' %s 2>&1", [
              $this->opts['command'],
              trim($options['files']),
              $complex,
              $outputFile
          ]);
          $raws = $this->exec($shell);
      }
      
      if(!is_file($outputFile)){
          $ret['msg'] = '处理错误';
      }else{
          $ret['output'] = $outputFile;
      }
      return $this->_reasponse($ret, $raws, $shell, false);
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
    public function textWater(array $options, $raw = false){
        switch ($options['position']) {
            case '0':
                $axio = explode(',', $options['axio']);
                $x = '(w-tw)-'.abs($axio[0]??1);
                $y = '(h-text_h)-'.abs($axio[1]??1);
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

        $drawtext = 'drawtext=fontfile='.$options['ttf'];
        $drawtext .=": x=$x:y=$y";
        if($options['fontsize']){
            $drawtext .=": fontsize={$options['fontsize']}";
        }
        if($options['fontcolor']){
            $drawtext .=": fontcolor={$options['fontcolor']}";
        }
        if($options['box']){
            $drawtext .=": box={$options['box']}";
            $drawtext .=": boxborderw=7";
            $drawtext .=": boxcolor={$options['boxcolor']}@".(floatval($options['alpha'])??1);
        }
        if($options['text']){
            if(preg_match("/\{@time([0-9\:\ ]+)\}/", $options['text'], $result)){
                if(strrpos($result[1], ":")){
                    $result[1] = strtotime($result[1]);
                }else{
                    $result[1] = substr($result[1], 0, 10);
                }
                $options['text'] = str_replace($result[0], '%{pts:gmtime:'.($result[1]+3600 *8).'}' , $options['text']);
                $options['text'] = str_replace(':', '\\:', $options['text']);
            }
            $drawtext .=": text='{$options['text']}'"; 
        }
        $meta = self::getInfo($options['file']);
        $output = $this->output($options, $meta['basic']);
        $shell = vsprintf('%s -y -r %s -i "%s" -c:a copy -v:b %sk -vf "%s" %s 2>&1', [
            $this->opts['command'],
            ($meta['video']['fps']??24),
            $options['file'],
            $meta['basic']['bitrate'],
            $drawtext,
            $output
        ]);
        $raws = $this->exec($shell);

        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell, $raw);
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
     * 
     * @return array
     */
    public function scaleResize(array $options):array{

        $output = $this->output($options);

        $options['scale'] = $options['scale'] ?? "";
        preg_match("/^(\d+):(-1|\d+)?$/",trim($options['scale']),$matches);

        if(!isset($matches[2])){
          throw new \Exception("Video filter:scale Syntax error!");
        }

        // ./ffmpeg -i input.mp4 -vf scale=960:540 output.mp4
        $info = $this->getInfo($options['file']);
        $shell = vsprintf('%s -y -i %s -c:a copy -v:b %sk -vf scale=%s %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $info['basic']['bitrate'],
            $matches[0],
            $output
        ]);

        $raws = $this->exec($shell);

        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);
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
     * 
     * @return array
     */
    public function cropPlus(array $options):array{

        $output = $this->output($options);

        $crop = str_replace(['x',"X",',',"|","_"],':', str_replace(' ','', $options['scale']));
        if(isset($options['axis']) && $options['axis']){
            $crop .= ":" . str_replace(['x',"X",',',"|","_","."],':', str_replace(' ','', $options['axis']));
        }
        $info = $this->getInfo($options['file']);
        $shell = vsprintf("%s -y -i %s -vf crop='%s'%s -c:a copy -v:b %sk %s 2>&1", [
            $this->opts['command'],
            $options['file'],
            $crop,
            (isset($options['seconds']) && $options['seconds'] > 0) ? (' -t '.intval($options['seconds'])) : '',
            $info['basic']['bitrate'],
            $output
        ]);
        $raws = $this->exec($shell);
        
        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret = array_merge($ret, $this->getInfo($output));
        }
        return $this->_reasponse($ret, $raws, $shell);
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
     * @return array
     */
    public function imageWater(array $options){
        $options = array_merge([
            "position" => "lt", // lt/rt/rb/lb/lr/rl
            "file" => "",
            "image" => "",
            "output_dir"=> "",
            "save_name" => ""
        ], $options);

        $output = $this->output($options);

        // 加 1px 是为解决在去除水印时能规避 贴边(0,0) 选取框选水印时命令报错的问题
        switch($options['position']){
            case "lr": $options['position'] = "overlay=x='if(gte(t,1), -w+(t-2)*200, NAN)':y=40"; break; //左到右
            case "rl": $options['position'] = "overlay=x='if(gte(t,1), W-(t-2)*200, NAN)':y=40"; break; //右到左
            case "rt": $options['position'] = "overlay=W-w"; break; //右上角
            case "rb": $options['position'] = "overlay=W-w:H-h"; break; //右下角
            case "lb": $options['position'] = "overlay=1:H-h"; break; //左下角
            case "lt": //左上角
            default: $options['position'] = "overlay=1:1"; break; //
            // default: $options['position'] = "overlay=0+t*20:0+t*10"; break;
        }
        $options['file'] = File::realpath($options['file']);
        $options['image'] = File::realpath($options['image']);

        $shell = $raws = ""; $msg ='处理错误';
        if(!is_file($options['file']) || !File::isImage($options['image'])){
            $msg = "媒体或水印文件不存在";
        }else{
            $info = $this->getInfo($options['file']);
            $shell = vsprintf("%s -y -i %s -i %s -filter_complex \"%s\" -c:a copy -b:v %sk %s 2>&1", [
                $this->opts['command'],
                $options['file'],
                $options['image'],
                $options['position'],
                $info['basic']['bitrate'],
                $output
            ]);
            $raws = $this->exec($shell);
        }
        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = $msg;
        }else{
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
     * @return array
     */
    public function removeImageWater(array $options){
      // 语法：-vf delogo=x:y:w:h[:show]
      // x:y 离左上角的坐标
      // w:h logo的宽和高
      // show：若设置为1有一个绿色的矩形，默认值0。
      // ffmpeg -i input.mp4 -vf delogo=0:0:220:90:1 output.mp4
        $options = array_merge([
            "file" => "",
            "xy" => "",
            "wh" => "",
            "show"=> 0
        ], $options);

        $options['file'] = File::realpath($options['file']);
        $output = $this->output($options);

        preg_match("/(\d+)[\:xX\-\.](\d+)/", $options['xy'], $xy);
        if(!isset($xy[2])){
           return $this->_reasponse(["msg"=>"离左上角的坐标无效"], "", ""); 
        }
        $options['xy'] = "x=".($xy[1]+1).":y=".($xy[2]+1);

        preg_match("/(\d+)[\:xX\-\.](\d+)/", $options['wh'], $wh);
        if(!isset($wh[2])){
           return $this->_reasponse(["msg"=>"水印宽高设置无效"], "", ""); 
        }
        $options['wh'] = "w={$wh[1]}:h={$wh[2]}";
        $options['show'] = "show=".intval($options['show']);

        $info = $this->getInfo($options['file']);

        //Todo kb
        $shell = vsprintf('%s -y -i %s -c:a copy -b:v %sk -vf "delogo=%s:%s:%s" %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $info['basic']['bitrate'],
            $options["xy"],
            $options["wh"],
            $options["show"],
            $output
        ]);

        $raws = $this->exec($shell);

        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);
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
    public function exportImages(string $file, int $rate = 1, string $output_dir = "", string $ss = "0", int $t = 0){
        // ffmpeg –i test.avi –r 1 –f image2 image-%3d.jpeg        //提取图片
        // ffmpeg -i input.mp4 -ss 00:00:20 -t 10 -r 1 -q:v 2 -f image2 pic-%03d.jpeg
        // -ss 表示开始时间 -t表示共要多少时间。 
        // 如此，ffmpeg会从input.mp4的第20s时间开始，往下10s，即20~30s这10秒钟之间，每隔1s就抓一帧，总共会抓10帧
        // pic-001.jpeg
        // ...
        // pic-010.jpeg
        $rate = $rate > 1 ? intval($rate) : 1;
        $file = File::realpath($file);

        $output = $this->output([
            "file" => $file,
            "output_dir" => $output_dir,
        ]);
        
        //生成临时目录
        $temp_hash = '.'.VipkwdStr::md5_16( VipkwdStr::uuid() . time());
        $_output = File::dirname($output).'/'.$temp_hash;
        File::createDir($_output);
        $output = str_replace(File::dirname($output), $_output, $output);

        $name_rule = "pic_".date('md')."_%05d.jpg";
        $output = File::dirname($output).'/'.$name_rule;

        $t = ($t > 0) ? "-t ".intval($t) : "";

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
        $debug=false;
        if(!is_file(str_replace('%05d',"00001", $output))){
            $ret['msg'] = '处理错误';
            $debug = true;
        }else{
            foreach(glob( str_replace('_%05d.','*.', $output) ) as $index => $file){
                $new = str_replace($temp_hash.'/', '', $file);
                File::rename($file, $new);
                $ret['images'][] = basename($new);
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
        return $this->_reasponse($ret, $raws, $shell, $debug);
    }

    /**
     * 去除片头、片尾
     *
     * @param array $options
     *                  -- file
     *                  -- start <0> 片头持续秒数 默认0秒
     *                  -- end <0>  片尾持续秒数 默认0秒
     *                  -- output_dir <''>
     * @return array
     */
    public function cropTitleEnding(array $options){
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
        $output = $this->output($options);
        
        $t = $info['basic']['seconds'] - $options['start'] - $options['end'];
        if($t < 1){
            return $this->_reasponse(["msg"=>"时长设置溢出"], "", "");
        }
        $options['start'] =  $this->secondsFormat($options['start'], "string");
        $shell = vsprintf('%s -y -i %s -b:v %sk -c:v copy -c:a copy -ss %s -t %s %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $info['basic']['bitrate'],
            $options['start'],
            $t,
            $output
        ]);

        $raws = $this->exec($shell);
        $ret['source'] = $options['file'];

        $debug = false;
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
            $debug = true;
        }else{
            $ret['output'] = $output;
        }
        unset(
            $options,
            $info,
            $output,
            $t,
        );
        return $this->_reasponse($ret, $raws, $shell, $debug);
    }

    /**
     * 获取媒体yuv源
     *
     * @param array $options
     *                  -- file
     * @return array
     */
    public function getOriginYuv(array $options){
      
        $options['file'] = File::realpath($options['file']);
        $output = $this->output($options).'.yuv';
        $info = $this->getInfo($options['file']);
        $shell = vsprintf('%s -y -i %s -c:a copy -b:v %sk %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $info['basic']['bitrate'],
            $output
        ]);

        $raws = $this->exec($shell);

        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);

      // ffmpeg -i input.mp4 output.yuv
    }

    private function imageToOriginYuv(){
      //保持原始宽高
      // ffmpeg -i pic-001.jpeg -pix_fmt yuv420p xxx3.yuv
      // 指定输出宽高
      // ffmpeg -i pic-001.jpeg -s 1440x1440 -pix_fmt yuv420p xxx3.yuv
      // ffmpeg -i pic-001.jpeg -s 1440x1440 -yuv422p yuv420p xxx3.yuv
    }

    private function defaults(array $options, array $merges = []){
        return array_merge([
            "file" => "",
            "output_dir" => "",
            "ext" => ""
        ], $merges, $options);
    }

    // 推rtmp 流
    private function rtmp(array $options){

        $options = $this->defaults($options, [
            "brand" => 10000000,
            "path" => "myapp/test1",
            "server" => Ip::getLocalIp()
        ]);

        $shell = $raws = "";
        if(File::exists($options['file'])){
            $shell = vsprintf('%s -y -re -i %s -c copy -f flv -b %s rtmp://%s 2>&1', [
                $this->opts['command'],
                $options['file'],
                $options['brand'],
                $options['server'].'/'.trim($options['path'],'/'),
            ]);
    
            $raws = $this->exec($shell);
            if(preg_match("/(.*)Connection refused(.*)/", $raws, $matches)){
                $ret['msg'] = $matches[0];
            }else{
                $ret['output'] = "rtmp://".$options['server'].'/'.trim($options['path'],'/');
            }
        }else{
            $ret['msg'] = '媒体文件不存在';
        }
        $ret['source'] = $options['file'];
        return $this->_reasponse($ret, $raws, $shell, false);

      // ffmpeg -re -i ~/2012.flv -c copy -f flv rtmp://192.168.1.102/myapp/test1

      // ffmpeg -re -i RealStream.fifo -c copy -f flv -b 20000000 rtmp://localhost/myapp/test1
    }

    // https://blog.csdn.net/wh8_2011/article/details/52117932#
    private function extract(array $options){
        $options = $this->defaults($options,[
            "type" => "video",
            "ext" => ($options['type'] ?? "video") == "video" ? "mp4" : "wav"
        ]);
        //TODO 音频提取未完成
        $output = $this->output($options);

        // return $this->getInfo($options['file']);

        if(File::exists($options['file'])){
            $cmd = $options['type'] == "video" ? " -vcodec copy -an " : " -acodec copy -vn ";
            $shell = vsprintf('%s -y -i %s '.$cmd.' %s 2>&1', [
                $this->opts['command'],
                $options['file'],
                $output
            ]);
            $raws = $this->exec($shell);
        }
        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell, true);
        // ffmpeg -i input_file -vcodec copy -an output_file_video　　//分离视频流
        // ffmpeg -i input_file -acodec copy -vn output_file_audio　　//分离音频流
    }

    /**
     * 合并m3u-TS为MP4
     *
     * @param array $options
     *                  -- file m3u8文件地址(本地绝对路径或http网络地址)
     * @return array
     */
    public function m3u8ToMp4(array $options){

        $options = $this->defaults($options,[
            "ext" => "mp4"
        ]);
        $output = $this->output($options);
        $shell = vsprintf('%s -y -i %s %s 2>&1', [
            $this->opts['command'],
            $options['file'],
            $output
        ]);
        $raws = $this->exec($shell);
        $ret['source'] = $options['file'];
        if(!is_file($output)){
            $ret['msg'] = '处理错误';
        }else{
            $ret['output'] = $output;
        }
        return $this->_reasponse($ret, $raws, $shell);

      // ffmpeg -i http://www.xxx.com/xxx.m3u8 name.mp4
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
      // ffmpeg -r 25 -f dshow -s 640×480 -i video=”video source name”:audio=”audio source name” -vcodec libx264 -b 600k -vpre slow -acodec libfaac -ab 128k -f flv rtmp://server/application/stream_name

      // 9、将一个JPG图片经过h264压缩循环输出为mp4视频
      // ffmpeg.exe -i INPUT.jpg -an -vcodec libx264 -coder 1 -flags +loop -cmp +chroma -subq 10 -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -flags2 +dct8x8 -trellis 2 -partitions +parti8x8+parti4x4 -crf 24 -threads 0 -r 25 -g 25 -y OUTPUT.mp4

      // 10、将普通流视频改用h264压缩，音频不变，送至高清流服务(新版本FMS live=1)
      // ffmpeg -i rtmp://server/live/originalStream -c:a copy -c:v libx264 -vpre slow -f flv “rtmp://server/live/h264Stream live=1〃<br style="box-sizing: border-box;" /><br style="box-sizing: border-box;" /><br style="box-sizing: border-box;" />





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

    /**
     * 标准响应信息
     *
     * @param array $ret
     * @param string $raws ffmpeg原始信息
     * @param boolean $shell <false> 是不响应ffmpeg命令
     * @param boolean $raw <false> 是不响应媒体原始资源信息
     * @return array
     */
    private static function _reasponse(array $ret, string $raws, $shell = false, $raw = false){
      if($shell !== false){
          $ret['shell'] = $shell;
      }
      if($raw){
          $ret['raws'] = "\r\n";
          $ret['raws'] .= str_pad("\r\n", 150 , '-', STR_PAD_LEFT);
          $ret['raws'] .=$raws;
          $ret['raws'] .=str_pad("\r\n", 150 , '-');
      }
      return $ret;
    }

    private function output($options, $baseInfo = null){
        $baseInfo = $baseInfo ?? self::fileBaseInfo($options['file']);

        if(!$options['output_dir'] || !is_dir($options['output_dir'])){
            $options['output_dir'] = $baseInfo['dir'];
        }

        if(!$options['output_dir'] && is_dir($this->opt['output_dir'])){    
            $options['output_dir'] = $this->opt['output_dir'];
        }
        $options['output_dir'] = File::realpath($options['output_dir'] ?? '');
        if(isset($options['save_name']) && $options['save_name']){
            return $options['output_dir'].'/'.$options['save_name'].'.'.($options['ext'] ?? $baseInfo['ext']);
        }
        $new_file_name = md5(json_encode($options)).'.'.($options['ext'] ?? $baseInfo['ext']);
        return $options['output_dir'].'/'.$new_file_name;
    }

    private function exec($shell){
        ob_start();
        passthru($shell);
        $raw = ob_get_contents();
        ob_end_clean();
        return $raw;    
    }

    private function validateFile(&$file){
        $file = str_replace('\\','/', $file);
        if(!is_file($file)){
            return ['error' => "\"$file\" 媒体文件无效"];
        }
        return true;
    }

    private static function fileBaseInfo($filepath){
        $ret = [];
        $ret['path'] = $filepath;
        $ret['dir'] = dirname($filepath);
        $ret['filename'] = basename($filepath);
        $ret['ext'] = pathinfo($filepath)['extension'];
        $ret['size'] = filesize($filepath); // 文件大小
        return $ret;
    }

    private function parsePathSeparator(){
        $this->opts['command'] = preg_replace("#(\/+)#", '/',  str_replace('\\','/', trim($this->opts['command'])) );
        if(!preg_match("/^([a-z]+:|)\/(.*)/i",$this->opts['command']) ){
          $code ="100";
        }
        if(!file_exists($this->opts['command'])){
          $code = "101";
        }
        if(!is_readable($this->opts['command'])){
          $code = "102";
        }
        if(!is_executable($this->opts['command'])){
          $code = "103";
        }
        isset($code) && exit("[".$this->opts['command']."]  ffmpeg 命令无效[E{$code}]");
    }

    /**
     * 时间格式化
     *
     * @param string|float $str
     * @param string $type <flip> 是否反向转换 默认(flip)是  flip/float/string
     * @return void
     */
    private function secondsFormat($str, $type = "flip"){
        $seconds = $str;
        $str = strval($str);
        if(strrpos($str, ":")){
            if($type == "flip" || $type == "float"){
                $seconds = explode('.', $str);
                list($h, $m, $s) = explode(":", $seconds[0]);
                $seconds = ($h * 3600 + $m * 60 + $s ) . "." . ($seconds[1] ?? "000");
            }
        }else{
            if($type == "flip" || $type == "string"){
                
                $seconds = explode('.', $str);
                $h = bcdiv(strval($seconds[0]), "3600", 0);
                $m = bcdiv(strval($seconds[0] % 3600), "60", 0);
                $s = $seconds[0] - $h * 3600 - $m * 60;

                $seconds = implode(":", array_map(function($v){
                    return str_pad(strval($v), 2, "0", STR_PAD_LEFT);
                } , [$h, $m, $s])) . "." . ($seconds[1] ?? "000");
            }
        }
        return $seconds;
    }
}

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