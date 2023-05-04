<?php

/**
 * @name 对象操作
 * @author vipkwd <service@vipkwd.com>
 * @link https://github.com/wxy545812093/vipkwd-phputils
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @copyright The PHP-Tools
 */

declare(strict_types=1);

namespace Vipkwd\Utils\Type;

class Obj
{

    /**
     * 对象转数组
     * 
     * -e.g: $data=(object)[ "a"=>50, "b"=>true, "c"=>null ];
     * -e.g: phpunit("Vipkwd\Utils\Type\Obj::toArray", [$data]);
     * 
     * @param object|array $object 对象
     * 
     * @return array
     */
    static function toArray($object)
    {
        if (is_object($object)) {
            $arr = (array)$object;
        } else if (is_array($object)) {
            $arr = [];
            foreach ($object as $k => $v) {
                $arr[$k] = self::toArray($v);
            }
        } else {
            return $object;
        }
        unset($object);
        return $arr;
        //return json_decode(json_encode($object), true);
    }

    static function getClassMethods($class)
    {
        $class = new \ReflectionClass($class);
        $methods = $class->getMethods(\ReflectionMethod::IS_STATIC + \ReflectionMethod::IS_PUBLIC);
        $return = ['method' => [], 'class' => $class->getNamespaceName()];
        foreach ($methods as $k => $method) {
            if ($k === 0 && isset($method->class) && $method->class != $return['class']) {
                $return['class'] = $method->class;
            }
            if (!$method->isProtected() && !$method->isPrivate()) {
                $return['method'][] = $method->getName();
            }
        }
        return $return;
    }
}
