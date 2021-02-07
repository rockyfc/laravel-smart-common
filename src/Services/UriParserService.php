<?php

namespace Smart\Common\Services;

use Illuminate\Routing\Route;

class UriParserService
{
    protected $route;

    protected $module;
    protected $controller;
    protected $action;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * 接口前缀
     * @return string
     */
    public function prefix(): string
    {
        return $this->route->getPrefix();
    }

    /**
     * 接口版本号
     * @return string
     */
    public function version(): string
    {
        if (preg_match('/(v\d{1})/', $this->prefix(), $machs)) {
            return $machs[1];
        }

        return '';
    }

    /**
     * 接口所属模块
     * @return string
     */
    public function module(): string
    {
        return $this->module;
    }

    public function controller(): string
    {
        return $this->controller;
    }

    public function action(): string
    {
        return $this->action;
    }

    protected function uri()
    {
        return $this->route->uri();
    }

    protected function format()
    {
        $array = $this->uriToArray();

        if (1 == count($array)) {
            $this->module = 'default';
            $this->controller = $array[1];
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
        $uri = str_ireplace($this->prefix(), '', $uri);

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
