<?php

namespace Smart\Common\Comment;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionClass;

/**
 * Controller解析
 */
class ControllerComment extends Comment
{
    /**
     * @var ReflectionClass
     */
    protected $reflector;

    /**
     * @var
     */
    protected $actions;

    /**
     * ControllerComment constructor.
     * @param $controllerClass
     * @throws \ReflectionException
     */
    public function __construct($controllerClass)
    {
        $this->reflector = new \ReflectionClass($controllerClass);

        $docComment = $this->reflector->getDocComment();
        if ($docComment) {
            $factory = DocBlockFactory::createInstance();
            $this->docblock = $factory->create(
                $docComment,
                (new ContextFactory())->createFromReflector($this->reflector)
            );
        }
    }

    /**
     * @return ReflectionClass
     */
    public function getReflector()
    {
        return $this->reflector;
    }

    /**
     * @return null|Context|DocBlock\Context
     */
    public function context()
    {
        if ($this->docblock) {
            return $this->docblock->getContext();
        }
    }

    /**
     * 通过名称获取action
     * @param $name
     * @return \ReflectionMethod
     */
    public function actionByName($name)
    {
        /** @var \ReflectionMethod $actions */
        $actions = $this->actions();

        foreach ($actions as $action) {
            if ($action->name === $name) {
                return $action;
            }
        }
    }

    /**
     * 获取所有有效的action
     * @return mixed
     */
    public function actions()
    {
        if ($this->actions) {
            return $this->actions;
        }

        $actions = $this->reflector->getMethods(\ReflectionMethod::IS_PUBLIC);

        $parentReflector = $this->reflector->getParentClass();
        $parentMethods = $parentReflector->getMethods();

        $names = [];
        foreach ($parentMethods as $method) {
            $names[] = $method->getName();
        }

        foreach ($actions as $action) {
            if (in_array($action->getName(), $names)) {
                continue;
            }

            if ($action->isStatic()) {
                continue;
            }

            if (preg_match('/^__/', $action->getName())) {
                continue;
            }

            $this->actions[] = $action;
        }

        return $this->actions;
    }
}
