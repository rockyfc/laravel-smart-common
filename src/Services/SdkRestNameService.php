<?php

namespace Smart\Common\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Class SdkApiNameService
 */
class SdkRestNameService
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * SdkService constructor.
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * 生成一个带有命名空间的rest类名称
     * @return string
     */
    public function generateApiName()
    {
        $suffix = 'Api';
        // $action = $this->getActionName();

        // $arr = $this->resolverNamespace();
        // $namespace = implode('\\', $this->getNamespaceArr());

        // return $namespace . '\\' . ucfirst($arr['controller'] . ucfirst($action) . $suffix);

        $tmp = [];
        foreach (explode('.', $this->route->getName()) as &$val) {
            $tmp[] = ucfirst(Str::camel($val));
        }

        $className = array_pop($tmp) . $suffix;
        $controllerName = array_pop($tmp);

        return implode('\\', $tmp) . '\\' . $controllerName . '\\' . $controllerName . $className;
    }

    /**
     * @return string
     */
    protected function method()
    {
        $methods = $this->route->methods();
        if (1 == count($methods)) {
            return strtoupper($methods[0]);
        }

        if (2 == count($methods)) {
            if (in_array('GET', $methods)) {
                return 'GET';
            }
        }

        return strtoupper($methods[0]);
    }

    /**
     * @return string[]
     */
    protected function methodMap()
    {
        return [
            'GET' => 'Get',
            'POST' => 'Create',
            'PUT' => 'Update',
            'PATCH' => 'Update',
            'DELETE' => 'Delete',
        ];
    }

    protected function getActionName()
    {
        list($namespace, $action) = explode('@', $this->route->getActionName());

        return $action;
    }

    /**
     * @return string[]
     */
    protected function resolverUri()
    {
        $data = [
            'module' => '',
            'controller' => '',
            'action' => '',
        ];

        $array = $this->uriToArray();

        if (2 == count($array)) {
            $data['controller'] = $array[1];
        }
        if (3 == count($array)) {
            $data['module'] = $array[1];
            $data['controller'] = $array[2];
        }
        if (4 == count($array)) {
            $data['module'] = $array[1];
            $data['controller'] = $array[2];
            $data['action'] = $array[3];
        }

        return $data;
    }

    /**
     * @return false|string[]
     */
    protected function uriToArray()
    {
        $uri = $this->route->uri();
        $arr = array_filter(explode('/', $uri), function ($value) {
            if ('' == $value) {
                return false;
            }
            if (preg_match('/{/', $value)) {
                return false;
            }

            return true;
        });

        return array_values($arr);
    }

    protected function resolverNamespace()
    {
        $data = [
            'module' => '',
            'controller' => '',
        ];

        $array = $this->getNamespaceArr();
        $len = count($array);
        if (1 == $len) {
            $data['controller'] = $array[0];
        }
        if ($len >= 2) {
            $data['module'] = $array[$len - 2];
            $data['controller'] = $array[$len - 1];
        }

        return $data;
    }

    protected function getNamespaceArr()
    {
        $action = $this->route->getActionName();
        list($namespace, $actionName) = explode('@', $action);

        $namespace = str_replace(['App\Http\\', 'Controllers\\', 'Controller'], '', $namespace);

        $namespace = array_filter(explode('\\', $namespace), function ($value) {
            if ('' == $value) {
                return false;
            }

            return true;
        });

        return array_values($namespace);
    }
}
