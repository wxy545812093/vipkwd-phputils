<?php
/**
 * @name PHP callable tools
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */
declare(strict_types = 1);

namespace Vipkwd\Utils;

use Vipkwd\Utils\{Tools,Dev};

final class Callback{

	use ProtectedStaticClass;

	/**
	 * @param  string|object|callable  $callable  class, object, callable
	 * @deprecated use Closure::fromCallable()
	 */
	static function closure($callable, ?string $method = null): \Closure{
		trigger_error(__METHOD__ . '() is deprecated, use Closure::fromCallable().', E_USER_DEPRECATED);
		try {
			return \Closure::fromCallable($method === null ? $callable : [$callable, $method]);
		} catch (\TypeError $e) {
			throw new \Exception($e->getMessage());
		}
	}


	/**
	 * Invokes callback.
	 * @return mixed
	 * @deprecated
	 */
	static function invoke($callable, ...$args){
		trigger_error(__METHOD__ . '() is deprecated, use native invoking.', E_USER_DEPRECATED);
		self::check($callable);
		return $callable(...$args);
	}


	/**
	 * Invokes callback with an array of parameters.
	 * @return mixed
	 * @deprecated
	 */
	static function invokeArgs($callable, array $args = []){
		trigger_error(__METHOD__ . '() is deprecated, use native invoking.', E_USER_DEPRECATED);
		self::check($callable);
		return $callable(...$args);
	}


	/**
	 * Invokes internal PHP function with own error handler.
	 * @return mixed
	 */
	static function invokeSafe(string $function, array $args, callable $onError){
		$prev = set_error_handler(function ($severity, $message, $file) use ($onError, &$prev, $function): ?bool {
			if ($file === __FILE__) {
				$msg = ini_get('html_errors')
					? Html::htmlToText($message)
					: $message;
				$msg = preg_replace("#^$function\\(.*?\\): #", '', $msg);
				if ($onError($msg, $severity) !== false) {
					return null;
				}
			}

			return $prev ? $prev(...func_get_args()) : false;
		});

		try {
			return $function(...$args);
		} finally {
			restore_error_handler();
		}
	}


	/**
	 * Checks that $callable is valid PHP callback. Otherwise throws exception. If the $syntax is set to true, only verifies
	 * that $callable has a valid structure to be used as a callback, but does not verify if the class or method actually exists.
	 * @param  callable  $callable
	 * @return callable
	 * @throws \Exception
	 */
	static function check(callable $callable, bool $syntax = false){
		if (!is_callable($callable, $syntax)) {
			throw new \Exception(
				$syntax
				? 'Given value is not a callable type.'
				: sprintf("Callback '%s' is not callable.", self::toString($callable))
			);
		}
		return $callable;
	}


	/**
	 * Converts PHP callback to textual form. Class or method may not exists.
	 * @param  mixed  $callable
	 */
	static function toString($callable): string{
		if ($callable instanceof \Closure) {
			$inner = self::unwrap($callable);
			return '{closure' . ($inner instanceof \Closure ? '}' : ' ' . self::toString($inner) . '}');
		} elseif (is_string($callable) && $callable[0] === "\0") {
			return '{lambda}';
		} else {
			is_callable(is_object($callable) ? [$callable, '__invoke'] : $callable, true, $textual);
			return $textual;
		}
	}


	/**
	 * Returns reflection for method or function used in PHP callback.
	 * @param  callable  $callable  type check is escalated to ReflectionException
	 * @return \ReflectionMethod|\ReflectionFunction
	 * @throws \ReflectionException  if callback is not valid
	 */
	static function toReflection($callable): \ReflectionFunctionAbstract{
		if ($callable instanceof \Closure) {
			$callable = self::unwrap($callable);
		}

		if (is_string($callable) && strpos($callable, '::')) {
			return new \ReflectionMethod($callable);
		} elseif (is_array($callable)) {
			return new \ReflectionMethod($callable[0], $callable[1]);
		} elseif (is_object($callable) && !$callable instanceof \Closure) {
			return new \ReflectionMethod($callable, '__invoke');
		} else {
			return new \ReflectionFunction($callable);
		}
	}


	/**
	 * Checks whether PHP callback is function or static method.
	 */
	static function isStatic(callable $callable): bool{
		return is_array($callable) ? is_string($callable[0]) : is_string($callable);
	}


	/**
	 * Unwraps closure created by Closure::fromCallable().
	 * @return callable|array
	 */
	static function unwrap(\Closure $closure){
		$r = new \ReflectionFunction($closure);
		if (substr($r->name, -1) === '}') {
			return $closure;

		} elseif ($obj = $r->getClosureThis()) {
			return [$obj, $r->name];

		} elseif ($class = $r->getClosureScopeClass()) {
			return [$class->name, $r->name];

		} else {
			return $r->name;
		}
	}
}

/**
 * Static class.
 */
trait ProtectedStaticClass{
	/**
	 * @return never
	 * @throws \Error
	 */
	final public function __construct()
	{
		throw new \Error('Class ' . static::class . ' is static and cannot be instantiated.');
	}


	/**
	 * Call to undefined static method.
	 * @return void
	 * @throws \Exception
	 */
	static function __callStatic(string $name, array $args){
		self::strictStaticCall(static::class, $name);
	}

    /**
	 * @return never
	 * @throws \Exception
	 */
	static function strictStaticCall(string $class, string $method): void{
		$trace = debug_backtrace(0, 3); // suppose this method is called from __callStatic()
		$context = ($trace[1]['function'] ?? null) === '__callStatic'
			? ($trace[2]['class'] ?? null)
			: null;

		if ($context && is_a($class, $context, true) && method_exists($context, $method)) { // called parent::$method()
			$class = get_parent_class($context);
		}

		if (method_exists($class, $method)) { // insufficient visibility
			$rm = new \ReflectionMethod($class, $method);
			$visibility = $rm->isPrivate()
				? 'private '
				: ($rm->isProtected() ? 'protected ' : '');
			throw new \Exception("Call to {$visibility}method $class::$method() from " . ($context ? "scope $context." : 'global scope.'));

		} else {
			$hint = self::__getSuggestion(
				array_filter((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC), function ($m) { return $m->isStatic(); }),
				$method
			);
			throw new \Exception("Call to undefined static method $class::$method()" . ($hint ? ", did you mean $hint()?" : '.'));
		}
	}

    /**
	 * Finds the best suggestion (for 8-bit encoding).
	 * @param  (\ReflectionFunctionAbstract|\ReflectionParameter|\ReflectionClass|\ReflectionProperty|string)[]  $possibilities
	 * @internal
	 */
	static function __getSuggestion(array $possibilities, string $value): ?string{
		$norm = preg_replace($re = '#^(get|set|has|is|add)(?=[A-Z])#', '+', $value);
		$best = null;
		$min = (strlen($value) / 4 + 1) * 10 + .1;
		foreach (array_unique($possibilities, SORT_REGULAR) as $item) {
			$item = $item instanceof \Reflector ? $item->name : $item;
			if ($item !== $value && (
				($len = levenshtein($item, $value, 10, 11, 10)) < $min
				|| ($len = levenshtein(preg_replace($re, '*', $item), $norm, 10, 11, 10)) < $min
			)) {
				$min = $len;
				$best = $item;
			}
		}
		return $best;
	}
}