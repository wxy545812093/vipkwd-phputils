<?php

/**
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils\Libs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vipkwd\Utils\Tools;

class Console extends Command {

	protected function configure(){
		// return $this->__default_configure();
		$this->setName('dump:class')
			->setDescription('Show the class list of <info>Vipkwd-Utils</info> package')
			->setHelp('This command allow you to View/Show the doc of class methods list')
			//->addArgument('name', InputArgument::REQUIRED, 'what\'s model you want to create?')

			->addArgument('className', InputArgument::OPTIONAL, 'Show the method list of "className", give "list" and return the class of all', "--help")
			//->addOption("type", null, InputOption::VALUE_OPTIONAL,"Overwrite the argument 'type'")
		;

	}
	protected function execute(InputInterface $input, OutputInterface $output){

		// 你想要做的任何操作
		$class_argument = $input->getArgument('className');
		if ($class_argument){
			if($class_argument == "--help"){
				$output->writeln("");
				$output->writeln("<info> dump:class --help</info>");
				$output->writeln("");
				$output->writeln("<info> dump:class list</info> -- Show the Classes in Package");
				$output->writeln("");
				$output->writeln("<info> dump:class [<className>]</info> -- Show the methods of \"className\"");
				$output->writeln("");
				return 1;
			}
			return self::buildMethodListDoc($input, $output, trim($class_argument));
		}
		return 1;

		// return $this->__default_execute($input, $output);
		
	}

	private static function buildMethodListDoc(&$input, &$output, $cmd){
		
		$path = realpath(__DIR__."/../");
		$showList = true;
		if($cmd !="list"){
			$cmd = ucfirst($cmd);
			if(!file_exists($path.'/'.$cmd.".php")){
				$output->writeln('');
				$output->writeln('[Notice] Class "<info>'.$cmd.'</info>" not found in Package.');
				$output->writeln('');
				return 1;
			}
			$showList = false;
		}

		$output->writeln(self::createTRLine("+", "-"));
		$output->writeln(self::createTRLine("|",true, true));
		$output->writeln(self::createTRLine("+", "-"));

		foreach(glob($path ."/*.php") as $index => $classFile){
			if($showList === false){
				if( substr($classFile, 0 - strlen("{$cmd}.php") ) != "{$cmd}.php" ){
					continue;
				}
			}
			$classFile = str_replace('\\','/', $classFile);
			$classFile = explode("/", $classFile);
			$filename=array_pop($classFile);
			unset($classFile);
			self::parseClass( str_replace(".php","", $filename), $input, $output, $index, $showList);
		};
		$output->writeln(self::createTRLine("+", "-"));
		return 1;
	}

	private static function parseClass($class, &$input, &$output, $index, $showList){
		$class = str_replace('Libs', $class, __NAMESPACE__);
		$class = new \ReflectionClass($class);
		$methods = $class->getMethods(\ReflectionMethod::IS_STATIC + \ReflectionMethod::IS_PUBLIC);
		//剔除未公开的方法
		foreach($methods as $k => $method){;
			if($method->isProtected() || $method->isPrivate()){
				unset($methods[$k]);
			}
			unset($k,$method);
		}
		if( $showList === true){
			$output->writeln(self::createTRLine("|", [
				"No." => ($index+1)."",
				"Namespace" => $class->getNamespaceName(),
				"Class" => $class->getShortName(),
				"Method" => count($methods)." (s)",
				"Type" => "#",
				"Arguments" => "#",
				"Comment" => "#",
			]));
			return;
		}

		//遍历所有的方法
		foreach ($methods as $index => $method) {
			$comment = $method->getDocComment();
			//获取并解析方法注释
			$doc = explode("\r\n", is_string($comment)? $comment : "");
			if(count($doc) < 2){
				$doc = explode("\n", is_string($comment)? $comment : "");
			}
			$doc = str_replace(["/**","*"," "],"", trim( $doc[1] ?? "" ));
			//获取方法的类型
			//$method_flag = $method->isProtected();//还可能是public,protected类型的
			//获取方法的参数
			$params = $method->getParameters();
			//print_r($params);
			$position=0;    //记录参数的次序
			$arguments=[];
			$defaults=[];
			foreach ($params as $param){
				$arguments[$position] = $param->getName();
				//参数是否设置了默认参数，如果设置了，则获取其默认值
				$defaults[$position] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
				$position++;
			}
		
			/*$call = array(
				'class_name'=>$class->getName(),
				'method_name'=>$method->getName(),
				'arguments'=>$arguments,
				'defaults'=>$defaults,
				'metadata'=>$metadata,
				'method_flag'=>$method_flag
			);*/
			// var_dump(["args"=>$arguments, "def" => $defaults]);
			// ------args-------
			$args = "";
			if(!empty($arguments)){
				foreach($arguments as $idx => $field){
					$args .=',$'.$field;
					switch(strtolower(gettype($defaults[$idx]))){
						case "null": break;
						case "boolean": $args .= ('='.($defaults[$idx] === true ? "true" : "false")); break;
						case "string": 	$args .= ('="'.$defaults[$idx].'"'); break;
						case "array": 	$args .= ('=[]'); break;
						case "object": 	$args .= ('={}'); break;
						default: 		$args .= ('='.$defaults[$idx]); break;
					}
				}
				$args = ltrim($args, ', ');
			}
			$output->writeln(self::createTRLine("|", [
				"No." => ($index+1)."",
				"Namespace" => $class->getNamespaceName(),
				"Class" => $class->getShortName(),
				"Method" => $method->getName(),
				"Type" => $method->isStatic() ? "static" : "public",
				"Arguments" => $args,
				"Comment" => $doc,
			]));
		}
		return ;
	}

	private static function createTRLine(string $septer, $data=" ", $isTitle=false){
		$conf = [
			"No." => 5,
			"Namespace" => 18,
			"Class" => 10,
			"Method" => 25,
			"Type" => 8,
			"Arguments" => 72,
			"Comment" => 40,
		];
		$list = [];
		$list[] ="";
		foreach($conf as $title => $with){
			if($isTitle === true){
				$field = $title;
			}else{
				$field = (is_array($data)) ? @$data[$title] : $data;
			}
			$list[] = self::createTDText( $with, $field, $isTitle === true );
		}
		$list[] = "";
		return implode($septer, $list);
	}

	private static function createTDText(int $len, string $txt ="-", bool $setColor=false){
		$septer = "-";
		if($txt != "-"){
			$septer = " ";
			$len -= 2;
		}
		$txt = Tools::strPadPlus($txt, $len, $septer);
		if($setColor === true){
			//$txt = str_pad($txt, $len, $septer, STR_PAD_BOTH);
			$txt = "<info>" .$txt. "</info>";
		}
		if($septer != "-") $txt = " {$txt} ";
		return $txt;
	}

	/**
	 * console的标准配置demo
	 *
	 * @return void
	 */
	private function __default_configure(){
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
			->addOption("show", null, InputOption::VALUE_OPTIONAL,"Overwrite the argument 'show'")
			;
	}
	/**
	 * console的标准响应demo
	 *
	 * @param object $input
	 * @param object $output
	 * @return int
	 */
	private function __default_execute(&$input, &$output){
		// 你想要做的任何操作
		$optional_argument = $input->getArgument('optional');
		$output->writeln('creating...');
		$output->writeln('created ' . $input->getArgument('name') . ' model success !');
		if ($optional_argument){
			$output->writeln('optional argument is ' . $optional_argument);
		}
		$output->writeln('<info>the end.</info>'.$input->getOption('show'));
		return 1;
	}
}