<?php

namespace Smart\Common\Comment;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use ReflectionException;
use ReflectionMethod;

/**
 * 解析resource
 */
class ResourceComment extends Comment
{
    /**
     * @var ReflectionMethod
     */
    protected $reflector;

    /**
     * ResourceComment constructor.
     * @param string $resourceClass
     * @throws ReflectionException
     */
    public function __construct(string $resourceClass)
    {
        $this->reflector = new \ReflectionClass($resourceClass);
        $docComment = $this->reflector->getDocComment();
        if ($docComment) {
            $factory = DocBlockFactory::createInstance();
            $this->docblock = $factory->create(
                $docComment,
                (new ContextFactory())->createFromReflector($this->reflector)
            );
        }
    }
}
