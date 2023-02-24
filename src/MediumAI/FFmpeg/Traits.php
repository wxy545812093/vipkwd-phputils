<?php

/**
 * @name Trait
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\MediumAI\FFmpeg;

use Vipkwd\Utils\System\File;
use Vipkwd\Utils\Type\Math as VipkwdMath;
use \Exception;

trait Traits
{

    private $opts;

    public function __construct($options = array())
    {
        $this->opts = array_merge([
            'command' => '/usr/local/bin/ffmpeg',
            'output_dir' => ''
        ], $options);
        if (!is_file($this->opts['command'])) {
            throw new Exception(sprintf("Ffmpeg command \"%s\" not found", $this->opts['command']));
        }
        if (!is_executable($this->opts['command'])) {
            throw new Exception(sprintf("%s: Permission denied", $this->opts['command']));
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
    public function getInfo(string $filepath, bool $rawInfo = false): array
    {

        if (true !== ($result =  $this->validateFile($filepath))) {
            return $result;
        }

        $shell = vsprintf('%s -i "%s" 2>&1', [
            $this->opts['command'],
            $filepath
        ]);

        $raws = $this->exec($shell);

        // 通过使用输出缓冲，获取到ffmpeg所有输出的内容。
        $ret = ['basic' => [], 'video' => [], 'audio' => []];
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
        unset($match);
        if (preg_match("/com\.apple\.quicktime\.model: (\S+)/is", $raws, $match)) {
            $ret['basic']['device_model'] = $match[1];
        }
        unset($match);
        if (preg_match("/com\.apple\.quicktime\.software: (\S+)/is", $raws, $match)) {
            $ret['basic']['device_software'] = $match[1];
        }
        unset($match);
        // Duration: 01:24:12.73, start: 0.000000, bitrate: 456 kb/s
        if (preg_match("/Duration: (.*?), start: (.*?),\ +bitrate: (\d*\ +kb\/s).*?/", $raws, $match)) {
            $da = explode(':', $match[1]);
            $ret['basic']['duration'] = $match[1]; // 提取出播放时间
            $ret['basic']['seconds'] = $da[0] * 3600 + $da[1] * 60 + $da[2]; // 转换为秒
            $ret['basic']['start'] = $match[2]; // 开始时间
            $ret['basic']['bitrate'] = $match[3]; // bitrate 码率 单位 kb
        }
        // Stream #0.1: Video: rv40, yuv420p, 512x384, 355 kb/s, 12.05 fps, 12 tbr, 1k tbn, 12 tbc
        // Stream #0:1(und): Video: h264 (High) (avc1 / 0x31637661), yuv420p(tv, smpte170m/bt470bg/smpte170m), 448x960, 1209 kb/s, 30.18 fps, 30.18 tbr, 180k tbn, 360k tbc (default)
        if (preg_match_all("/Stream\ (.*):\ Video:\ ([A-Z0-9\ \[\]\/\(\)\.,\:\-\#]+)/is", $raws, $videoMatchs)) {

            // devdump($videoMatchs,1);
            if (isset($videoMatchs[2]) && ($videoMatchs[0][0] == "Stream {$videoMatchs[1][0]}: Video: {$videoMatchs[2][0]}")) {
                preg_match("/.*?Video: .*?rotate\ +:\ +(\d+).*?/is", $videoMatchs[0][0], $rotate);
                preg_match("/.*?Video: .*?creation_time\ +:\ +([0-9-A-Za-z\:\.]+).*?/is", $videoMatchs[0][0], $creation_time); //持续事件
                preg_match("/.*?Video: .*?\ +(\d+x\d+)\ +.*?/is", $videoMatchs[0][0], $WxH); //宽高
                preg_match("/.*?Video: .*?\ +(\d*)\ +kb\/s.*?/is", $videoMatchs[0][0], $bitrate);
                preg_match("/.*?Video: .*?\ +([0-9\.]+)\ +fps.*?/is", $videoMatchs[0][0], $fps);
                preg_match("/.*?Video: .*?\ +([0-9\.]+)\ +tbr.*?/is", $videoMatchs[0][0], $tbr);
                preg_match("/.*?Video: .*?\ +([0-9\.]+)[kK]\ +tbn.*?/is", $videoMatchs[0][0], $tbn);
                preg_match("/.*?Video: .*?\ +([0-9\.]+)[kK]\ +tbc.*?/is", $videoMatchs[0][0], $tbc);
                preg_match("/.*?Video: .*?SAR (\d+:\d+)\ +DAR\ +(\d+:\d+).*?/is", $videoMatchs[0][0], $sar_dar);
                preg_match("/.*?Video: ([A-Z0-9]+).*?, ([A-Z0-9]+).*?,.*?/is", $videoMatchs[0][0], $type);

                $ret['video'] = array_merge($ret['video'], [
                    'stream' => "{$videoMatchs[1][0]}: Video: {$videoMatchs[2][0]}",
                    'index' => $videoMatchs[1][0],
                    'totals' => count($videoMatchs[0])
                ]);

                if (isset($rotate[1])) {
                    $ret['video'] = array_merge($ret['video'], ['rotate' => $rotate[1]]);
                }
                if (isset($creation_time[1])) {
                    $ret['video'] = array_merge($ret['video'], ['creation_time' => $creation_time[1]]);
                }
                if (isset($WxH[1])) {
                    list($width, $height) = explode('x', $WxH[1]);
                    $ret['video'] = array_merge($ret['video'], ['resolution' => $WxH[1], 'width' => $width, 'height' => $height]);
                }
                if (isset($bitrate[1])) {
                    $ret['video'] = array_merge($ret['video'], ['bitrate' => $bitrate[1]]);
                }
                if (isset($sar_dar[1]) && isset($sar_dar[2])) {
                    // storage aspect ratio就是对图像采集时，横向采集与纵向采集构成的点阵，横向点数与纵向点数的比值。比如VGA图像640/480 = 4:3，D-1 PAL图像720/576 = 5:4
                    // display aspect ratio就是视频播放时，我们看到的图像宽高的比例，缩放视频也要按这个比例来，否则会使图像看起来被压扁或者拉长了似的
                    $__s = function ($s) {
                        $s = explode(":", $s);
                        return intval(bcdiv($s[0], $s[1], 10));
                    };
                    list($_, $sar, $dar) = $sar_dar;
                    $ret['video'] = array_merge($ret['video'], [
                        'sar' => $sar,
                        'dar' => $dar,
                        'par' => str_replace('/', ':', VipkwdMath::instance($dar)->div($sar)->done(true, false))
                    ]);
                }
                if (isset($type[1]) && isset($type[2])) {
                    $ret['video'] = array_merge($ret['video'], ['vcodec' => $type[1], 'vformat' => $type[2]]);
                }
                if (isset($fps[1])) {
                    $ret['video'] = array_merge($ret['video'], ['fps' => $fps[1]]); // $fps帧每秒
                }
                if (isset($tbr[1])) {
                    $ret['video'] = array_merge($ret['video'], ['tbr' => $tbr[1]]); //是timebase的rate也就是帧率
                }
                if (isset($tbn[1])) {
                    $ret['video'] = array_merge($ret['video'], ['tbn' => $tbn[1]]); //文件层的时间精度为1S=90k;[是 AVStream->timebase，也就是流中一秒增加 90k]
                }
                if (isset($tbc[1])) {
                    $ret['video'] = array_merge($ret['video'], ['tbc' => $tbc[1]]); //视频层的时间精度为1S=50; [是 AVStream->Codec->timebase,也就是编码器中一秒 增加 50]
                }
            }
        }

        // Stream #0.0: Audio: cook, 44100 Hz, stereo, s16, 96 kb/s
        // Stream #0:1[0x101]: Audio: aac (LC) ([15][0][0][0] / 0x000F), 44100 Hz, stereo, fltp, 130 kb/s
        if (preg_match_all("/Stream\ (#[A-Z0-9:\-\ \[\]_\/\.\(\)]+):\ Audio:\ ([A-Z0-9\ \[\]\/\(\)\.,\:\-\#]+)/is", $raws, $audioMatchs)) {
            if (isset($audioMatchs[2]) && ($audioMatchs[0][0] == "Stream {$audioMatchs[1][0]}: Audio: {$audioMatchs[2][0]}")) {

                preg_match("/.*?Audio:\ +.*?\ +(\d*\ +kb\/s).*?/is", $audioMatchs[0][0], $bitrate);
                preg_match("/.*?Audio:\ +([A-Z0-9]+).*?/is", $audioMatchs[0][0], $type);
                preg_match("/.*?Audio:\ +.*?,\ +(\d*\ +[KM]?Hz).*?/is", $audioMatchs[0][0], $Hz);
                preg_match("/.*?Audio:\ +.*?\ +(stereo).*?/is", $audioMatchs[0][0], $stereo);
                preg_match("/.*?Audio:\ +.*?\ +(fltp).*?/is", $audioMatchs[0][0], $fltp);
                preg_match("/.*?Audio:\ +(.*)creation_time\ +:\ +([0-9-A-Za-z\:\.]+)/is", $audioMatchs[0][0], $creation_time);
                $ret['audio'] = array_merge($ret['audio'], [
                    'stream' => "{$audioMatchs[1][0]}: Audio: {$audioMatchs[2][0]}",
                    'index' => $audioMatchs[1][0],
                    'totals' => count($audioMatchs[0]),
                    'stereo' => isset($stereo[1]), //立体声
                    'fltp' => isset($fltp[1]), //重采样
                    'creation_time' => $creation_time[2] ?? ''
                ]);

                if (isset($type[1])) {
                    $ret['audio'] = array_merge($ret['audio'], ['acodec' => $type[1]]);
                }
                if (isset($bitrate[1])) {
                    $ret['audio'] = array_merge($ret['audio'], ['bitrate' => $bitrate[1]]); // 音频编码
                }
                if (isset($Hz[1])) {
                    $ret['audio'] = array_merge($ret['audio'], ['asamplerate' => $Hz[1]]); //音频采样频率
                }
            }
        }

        if (isset($ret['basic']['seconds']) && isset($ret['basic']['start'])) {
            $ret['basic']['play_time'] = $ret['basic']['seconds'] + $ret['basic']['start']; // 实际播放时间
        }

        $ret['basic']['stream_total'] = [
            'video' => $ret['video']['totals'] ?? 0,
            'audio' => $ret['audio']['totals'] ?? 0
        ];
        return $this->_reasponse($ret, $raws, $shell,  $rawInfo);
    }

    /**
     * 标准响应信息
     *
     * @param array $ret
     * @param string $raws ffmpeg原始信息
     * @param boolean $shell <false> 是不响应ffmpeg命令
     * @param boolean $raw <false> 是不响应媒体原始资源信息
     * @return array
     */
    private static function _reasponse(array $ret, string $raws, $shell = false, $raw = false)
    {
        if ($shell !== false) {
            $ret['shell'] = $shell;
        }
        if ($raw) {
            $ret['raws'] = "\r\n";
            $ret['raws'] .= str_pad("\r\n", 150, '-', STR_PAD_LEFT);
            $ret['raws'] .= $raws;
            $ret['raws'] .= str_pad("\r\n", 150, '-');
        }
        return $ret;
    }
    private static function _reasponse2(array $options, ?string $shell = null, ?bool $raw = false)
    {
        $ret = [];
        $raws = ($shell !== null && $shell) ? self::exec($shell) : '';
        $ret['shell'] = $shell;
        isset($options['file']) ? $ret['source'] = $options['file'] : (isset($options['files']) ? $ret['source'] = $options['files'] : '');
        if (!is_file($options['output'])) {
            $ret['msg'] = '处理错误';
        } else {
            $ret['output'] = $options['output'];
        }
        $ret = self::_reasponse($ret, $raws, false, $raw);

        if (isset($options['callback']) && is_callable($options['callback'])) {
            $r = call_user_func($options['callback'], $raws, $ret);
            if (is_array($r)) {
                $ret = array_merge($ret, $r);
            }
        }
        unset($raws, $options, $shell);
        return $ret;
    }
    private function output($options, $baseInfo = null)
    {
        $firstFile = $options['file'];
        if (is_array($firstFile)) {
            $firstFile = $firstFile[0];
        }
        $baseInfo = $baseInfo ?? self::fileBaseInfo($firstFile);

        if (!isset($options['output_dir']) || !$options['output_dir'] || !is_dir($options['output_dir'])) {
            $options['output_dir'] = $baseInfo['dir'];
        }

        if (!$options['output_dir'] && is_dir($this->opts['output_dir'])) {
            $options['output_dir'] = $this->opts['output_dir'];
        }
        $options['output_dir'] = File::realpath($options['output_dir'] ?? '');
        if (isset($options['save_name']) && $options['save_name']) {
            return File::pathToUnix($options['output_dir'] . '/' . $options['save_name'] . '.' . ($options['ext'] ?? $baseInfo['ext']));
        }
        $new_file_name = md5(json_encode($options)) . '.' . ($options['ext'] ?? $baseInfo['ext']);
        return File::pathToUnix($options['output_dir'] . '/' . $new_file_name);
    }

    private static function exec($shell)
    {
        ob_start();
        passthru($shell);
        $raw = ob_get_contents();
        ob_end_clean();
        return $raw;
    }

    private function validateFile(&$file)
    {
        $file = str_replace('\\', '/', $file);
        if (!is_file($file)) {
            return ['error' => "\"$file\" 媒体文件无效"];
        }
        return true;
    }

    private static function fileBaseInfo($filepath)
    {
        $ret = [];
        $ret['path'] = $filepath;
        $ret['dir'] = @dirname($filepath);
        $ret['filename'] = @basename($filepath);
        $ret['ext'] = @pathinfo($filepath)['extension'];
        $ret['size'] = @filesize($filepath); // 文件大小
        $ret['stream_total'] = 0;
        return $ret;
    }

    private function parsePathSeparator()
    {
        $this->opts['command'] = preg_replace("#(\/+)#", '/',  str_replace('\\', '/', trim($this->opts['command'])));
        if (!preg_match("/^([a-z]+:|)\/(.*)/i", $this->opts['command'])) {
            $code = "100";
        }
        if (!file_exists($this->opts['command'])) {
            $code = "101";
        }
        if (!is_readable($this->opts['command'])) {
            $code = "102";
        }
        if (!is_executable($this->opts['command'])) {
            $code = "103";
        }
        isset($code) && exit("[" . $this->opts['command'] . "]  ffmpeg 命令无效[E{$code}]");
    }

    /**
     * 时间格式化
     *
     * @param string|float $str
     * @param string $type <flip> 是否反向转换 默认(flip)是  flip/float/string
     * @return void
     */
    private function secondsFormat($str, $type = "flip")
    {
        $seconds = $str;
        $str = strval($str);
        if (strrpos($str, ":")) {
            if ($type == "flip" || $type == "float") {
                $seconds = explode('.', $str);
                list($h, $m, $s) = explode(":", $seconds[0]);
                $seconds = ($h * 3600 + $m * 60 + $s) . "." . ($seconds[1] ?? "000");
            }
        } else {
            if ($type == "flip" || $type == "string") {

                $seconds = explode('.', $str);
                $h = bcdiv(strval($seconds[0]), "3600", 0);
                $m = bcdiv(strval($seconds[0] % 3600), "60", 0);
                $s = $seconds[0] - $h * 3600 - $m * 60;

                $seconds = implode(":", array_map(function ($v) {
                    return str_pad(strval($v), 2, "0", STR_PAD_LEFT);
                }, [$h, $m, $s])) . "." . ($seconds[1] ?? "000");
            }
        }
        return $seconds;
    }
    private function defaults(array $options, array $merges = [])
    {
        return array_merge([
            "file" => "",
            "output_dir" => "",
            "ext" => ""
        ], $merges, $options);
    }
}
