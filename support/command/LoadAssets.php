<?php

/**
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\{
	InputOption,
	InputArgument,
	InputInterface
};
use Vipkwd\Utils\Dev;
// use Vipkwd\Utils\Type\Str;
use Vipkwd\Utils\System\File;
use Vipkwd\Utils\Http;
// use \Exception;

class LoadAssets extends Command
{

	use TaskUtils9973200;

	private $mapsApi = "{%cdn%}/vipkwd-cdn/maps.php";

	protected function configure()
	{
		$this->setName("load:assets")
			->setDescription('Download/Update static resources remotely for utils.')
			->setHelp('Download static resources remotely for utils.')
			->addOption("cdn", "c", InputOption::VALUE_OPTIONAL, 'Test the method in "className" class.', "http://dl.vipkwd.com");
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$width = 120;
		//self::smartPad($width);
		echo "\r\n";

		$cdn = trim($input->getOption('cdn') ?? "");
		if (!$cdn) {
			$cdn = 'dl.vipkwd.com';
		}
		$api = str_replace('{%cdn%}', trim($cdn, '/'), $this->mapsApi);
		if (!preg_match("/^https?:\/\//", $api)) {
			$api = 'http://' . $api;
		}

		$apiHeaderInfo = Http::connectTest($api, [], 1000);
		if ($apiHeaderInfo['http_code'] == "0") {
			exit(sprintf(" --- " . Dev::colorPrint("CDN", 31) . " (" . Dev::colorPrint($cdn, 33) . ")" . Dev::colorPrint(" 已失效", 31) . "\r\n"));
		} elseif ($apiHeaderInfo['http_code'] == "404") {
			echo sprintf(" --- CDN连通性($cdn)：[http-code: " . Dev::colorPrint("OK", 32) . "\r\n");
			exit(sprintf(" --- 资源映射(%s): [http-code: " . Dev::colorPrint($apiHeaderInfo['http_code'], 31) . "\r\n", str_replace('{%cdn%}', '', $this->mapsApi)));
		} elseif ($apiHeaderInfo['http_code'] != "200") {
			exit(sprintf(" --- " . Dev::colorPrint("CDN", 31) . " (" . Dev::colorPrint($cdn, 33) . ")" . Dev::colorPrint(" 服务异常", 31) . " [http-code: {$apiHeaderInfo['http_code']}]\r\n"));
		}
		self::smartPad($width - 7);
		echo sprintf("--- CDN连通性($cdn)：" . Dev::colorPrint("Connected", 32) . "\r\n");
		echo sprintf("--- 资源映射(%s): " . Dev::colorPrint("Connected", 32) . "\r\n", str_replace('{%cdn%}', '', $this->mapsApi));
		self::smartPad($width - 7);
		echo "\r\n";
		unset($apiHeaderInfo);

		// echo "----".str_pad("任务构建",56,'·',STR_PAD_BOTH)."----".PHP_EOL;
		$maps = json_decode(file_get_contents($api), true);

		$idx = 1;
		$mapLength = count($maps);
		foreach ($maps as $file => $map) {
			$mapLength >= 5 && $idx = str_pad("$idx", strlen("$mapLength"), " ", STR_PAD_LEFT);
			$sfile = self::buildPath($file);
			$key = file_exists($sfile) ? hash_file('md5', $sfile) : null;
			self::smartPad($width, "--> [{$idx}] " . Dev::colorPrint($map['hash'], 35) . " " . Dev::colorPrint($file, "4;7;37"));
			if ($key != $map['hash']) {
				File::downloadHttpFile($map['url'], $sfile);
				self::smartPad($width, "   ### (" . ($key === null ? 'Download' : 'Update') . Dev::colorPrint(" completed", 32) . ")", "###", "  └-");
				usleep(600);
			} else {
				self::smartPad($width, "   ### (Exists" . Dev::colorPrint(" Skiped", 33) . ")", "###", "  └-");
			}
			$mapLength >= 10 && $idx = intval($idx);
			$idx++;
		}
		//self::smartPad($width);
		return 1;
	}

	/**
	 * console的标准配置demo
	 *
	 * @return void
	 */
	private function __default_configure()
	{
		// 命令的名称 ("php artisan" 后面的部分)
		// 运行 "php artisan list" 时的简短描述
		// 运行命令时使用 "--help" 选项时的完整命令描述
		// 配置一个参数
		// 配置一个可选参数
		$this->setName('model:create')
			->setDescription('Create a new model')
			->setHelp('This command allow you to create models...')
			->addArgument('name', InputArgument::REQUIRED, 'what\'s model you want to create?')
			->addArgument('optional', InputArgument::OPTIONAL, 'this is a optional argument', "")
			->addOption("show", null, InputOption::VALUE_OPTIONAL, "Overwrite the argument 'show'");
	}
	/**
	 * console的标准响应demo
	 *
	 * @param object $input
	 * @param object $output
	 * @return int
	 */
	private function __default_execute(&$input, &$output)
	{
		// 你想要做的任何操作
		$optional_argument = $input->getArgument('optional');
		$output->writeln('creating...');
		$output->writeln('created ' . $input->getArgument('name') . ' model success !');
		if ($optional_argument) {
			$output->writeln('optional argument is ' . $optional_argument);
		}
		$output->writeln('<info>the end.</info>' . $input->getOption('show'));
		return 1;
	}
}

trait TaskUtils9973200
{

	private static function buildPath($file)
	{
		$file = realpath(__DIR__ . '/../') . '/' . ltrim($file, '/');
		$dir = dirname($file);
		File::createDir($dir);
		return $file;
	}

	private static function smartPad($width, $text = null, $seper = '###', $prefix = '')
	{
		$width *= 1;
		if ($text === null) {
			echo str_pad("", $width, "-") . PHP_EOL;
		} else {
			$pad = implode('', array_pad([], ($width - strlen($text) - intval(strlen($prefix) / 3) + strlen($seper)), "·"));
			echo str_replace($seper, $prefix . $pad, $text) . PHP_EOL;
		}
	}
}
