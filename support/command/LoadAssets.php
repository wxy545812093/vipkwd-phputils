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
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\{
	InputOption,
	InputArgument,
	InputInterface
};
use Vipkwd\Utils\Dev;
// use Vipkwd\Utils\Type\Str;
use Vipkwd\Utils\System\File;
use Vipkwd\Utils\Http;
use Vipkwd\Utils\System\Zip;

// use \Exception;

class LoadAssets extends Command
{

	use TaskUtils9973200;

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
	private $width = 150;
	private $mapsApi = "{%cdn%}/vipkwd-cdn/maps.json";
	private $cdnList = [
		'http://vipkwd.eu5.net',
		'http://vipkwd.totalh.net',
		'http://dl.vipkwd.com',
		'http://vipkwd.byethost13.com',
		'http://dl.dev.tcnas.cn',
	];

	protected function configure()
	{
		$this->setName("load:assets")
			->setDescription('Download/Update static resources remotely for utils.')
			->setHelp('Download static resources remotely for utils.')
			->addOption("cdn", "c", InputOption::VALUE_OPTIONAL, 'Test the method in "className" class.', "")
			->addOption("cdns", "l", InputOption::VALUE_OPTIONAL, 'Print the default support list of CDNS.', "list");
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		//self::smartPad($this->width);
		echo "\r\n";
		$cdn = trim($input->getOption('cdn') ?? "");

		if (!$cdn) {
			// $cdn = $this->$cdnList[0];
			$cdn = $this->dumpCdnList($input, $output, "");
			if ($cdn == null) {
				return 1;
			}
		}
		$api = str_replace('{%cdn%}', trim($cdn, '/'), $this->mapsApi);
		if (!preg_match("/^https?:\/\//", $api)) {
			$api = 'http://' . $api;
		}

		$apiHeaderInfo = Http::connectTest($api, [], 1000);
		if ($apiHeaderInfo['http_code'] == "0") {
			exit(sprintf(" --- " . Dev::colorPrint("CDN", 31) . " (" . Dev::colorPrint($cdn, 33) . ")" . Dev::colorPrint(" Lost efficacy", 31) . "\r\n"));
		} elseif ($apiHeaderInfo['http_code'] == "404") {
			echo sprintf(" --- CDN Ping( $cdn )：[" . Dev::colorPrint("OK", 32) . "]\r\n");
			exit(sprintf(" --- CDN Maps( %s ): [" . Dev::colorPrint('Not found', 31) . "]\r\n", str_replace('{%cdn%}', $cdn, $this->mapsApi)));
		} elseif ($apiHeaderInfo['http_code'] != "200") {
			exit(sprintf(" --- " . Dev::colorPrint("CDN", 31) . " (" . Dev::colorPrint($cdn, 33) . ")" . Dev::colorPrint(" Service exception", 31) . " [http-code: {$apiHeaderInfo['http_code']}]\r\n"));
		}
		self::smartPad($this->width - 7);
		echo sprintf("--- CDN Ping( $cdn )：" . Dev::colorPrint("Ok", 32) . "\r\n");
		echo sprintf("--- CDN Maps( %s ): " . Dev::colorPrint("Ok", 32) . "\r\n", str_replace('{%cdn%}', $cdn, $this->mapsApi));
		self::smartPad($this->width - 7);
		unset($apiHeaderInfo);

		// echo "----".str_pad("任务构建",56,'·',STR_PAD_BOTH)."----".PHP_EOL;
		$map = json_decode(file_get_contents($api, false, stream_context_create([
			'http' => [
				'method' => 'GET',
				// 'header' => 'Content-type:application/x-www-form-urlencoded',
				// 'content' => http_build_query([]),
				'timeout' => 5
			]
		])), true);
		if (!is_array($map) || empty($map)) {
			exit(sprintf("--- " . Dev::colorPrint("The CDN mapping content is invalid.", 31) . "\r\n"));
		}
		echo "\r\n";

		$idx = 1;
		$mapLength = count($map['maps']);
		foreach ($map['maps'] as $file => $Map) {
			$mapLength >= 5 && $idx = str_pad("$idx", strlen("$mapLength"), " ", STR_PAD_LEFT);
			$sfile = self::buildPath($file);
			$key = file_exists($sfile) ? hash_file('md5', $sfile) : null;
			self::smartPad($this->width, "--> [{$idx}] " . Dev::colorPrint($Map['hash'], 35) . " " . Dev::colorPrint($file, "4;7;37"));
			if ($key != $Map['hash']) {
				if (File::getExtension($Map['url']) == 'vkzip') {
					File::downloadHttpFile($Map['url'], $sfile . '.vkzip');
					Zip::unZip($sfile . '.vkzip',  dirname($sfile));
				} else {
					File::downloadHttpFile($Map['url'], $sfile);
				}
				self::smartPad($this->width, "   ### (" . ($key === null ? 'Download' : 'Update') . Dev::colorPrint(" completed", 32) . ")", "###", "  └-");
				usleep(600);
			} else {
				self::smartPad($this->width, "   ### (Exists" . Dev::colorPrint(" Skiped", 33) . ")", "###", "  └-");
			}
			File::delete($sfile . '.vkzip');
			$mapLength >= 10 && $idx = intval($idx);
			$idx++;
		}
		//self::smartPad($this->width);
		return 1;
	}


	private function dumpCdnList(InputInterface $input, OutputInterface $output, $cdn)
	{
		$style = new SymfonyStyle($input, $output);
		$style->block(sprintf("You must use option `-c https://domain.com` manually enter your own cdn address or specify one from the list of below"), null, 'error');
		if (!$cdn && !empty($this->cdnList)) {
			$cdn = $this->cdnList[0];
			if (count($this->cdnList) > 1) {
				$this->cdnList[] = "quit";
				$cdn = $style->choice(sprintf('But have found the following cdn maps, please choose[index/url] one of them?'), $this->cdnList, 0);
				if ($cdn == 'quit') {
					return;
				}
				return $cdn;
			}
		};
		if ($cdn !== null) {
			if ($style->confirm(sprintf('Do you want to run cdn[index/url]:"%s" instead? ', $cdn), false)) {
				return $cdn;
			}
		}
		return;
	}

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
