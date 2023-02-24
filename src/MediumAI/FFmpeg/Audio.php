<?php

/**
 * @name FFmpeg处理音频
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\MediumAI\FFmpeg;

use \Exception;

class Audio
{
    use Traits;

    /**
     * 音频提取
     * 
     * @param array $options
     *      -- file 视频源绝对路劲
     *      -- bitrate <null>码率 如 128k, 默认原样导出
     *      -- hz <null>采样频率 如 44k, 默认原样导出
     *      -- ext <acc> 指定导出格式。 自定义 码率与采样率时，强制导出mp3格式音频
     * @param bool $raw <false>
     * @return array
     */
    public function extract(array $options, bool $raw = false)
    {
        $options = $this->defaults($options, [
            "bitrate" => null, //码率
            "hz" => null, //采样频率
            "ext" => 'm4a'
        ]);

        $cmds = ["%s", "-y -i", '"%s"', "-vn"];
        $args = [$this->opts['command'], $options['file']];
        if ($options['bitrate'] && $options['hz']) {
            $options['ext'] = 'mp3';
            $cmds[] = "-b:a %s -ar %s";

            $args[] = $options['bitrate'];
            $args[] = $options['hz'];
        }
        $cmds[] = ($options['ext'] == 'mp3') ? '-acodec libmp3lame -aq 0' : '-codec copy';
        // $cmds[] = '-acodec copy';
        // $cmds[] = '-acodec libmp3lame -aq 0'; //缺少MP3编码库（ffmpeg默认仅含编译MP3解码库）

        $options['output'] = $this->output($options);
        // return $this->getInfo($options['file'], !!0);

        $cmds[] = '"%s" 2>&1';
        $args[] = $options['output'];

        $shell = vsprintf(implode(' ', $cmds), $args);
        return self::_reasponse2($options, $shell, $raw);
    }
}
