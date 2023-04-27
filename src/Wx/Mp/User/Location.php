<?php

/**
 * @name 基础能力 - 用户管理 - 用户地理位置
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license MIT
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Wx\Mp\User;
use Vipkwd\Utils\Wx\Mp\__Traits as Traits;

class Location
{
    use Traits;

    /**
     * 接收用户地理位置
     * 
     * @link https://developers.weixin.qq.com/doc/offiaccount/User_Management/Gets_a_users_location.html
     * 
     * @return array
     */
    public function userLocationNotify()
    {
        $data = static::xmlToArray(file_get_contents("php://input"));
        return static::response($data);
    }
}
