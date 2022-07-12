<?php

/**
 * @name 公共入口
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

trait CommonTrait
{

  private $instances = [];
  /**
   * 入口
   *
   * @param array $config
   * @return self
   */
  static function instance(array $config = []): self
  {
    $k = md5(json_encode(array_merge(["_" => 0], $config)));
    if (!isset(self::$instances[$k])) {
      self::$instances[$k] = new self($config);
    }
    return self::$instances[$k];
  }
}
