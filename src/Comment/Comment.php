<?php

namespace Smart\Common\Comment;

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use phpDocumentor\Reflection\DocBlock\Tags\Param;

class Comment
{
    /**
     * @var DocBlock
     */
    protected $docblock;

    /**
     * @return string
     */
    public function title()
    {
        if ($this->docblock) {
            return $this->docblock->getSummary();
        }
    }

    /**
     * @return string
     */
    public function desc()
    {
        if (!$this->docblock) {
            return null;
        }

        if ($desc = $this->docblock->getDescription()) {
            return $desc->render();
        }
    }

    /**
     * @return DocBlock\Tag[]
     */
    public function tags()
    {
        if ($this->docblock) {
            return $this->docblock->getTags();
        }
    }

    /**
     * 获取所有的param标签
     * @return Param[]
     */
    public function paramTag()
    {
        if (!$this->docblock) {
            return [];
        }
        if (!$this->docblock->hasTag('param')) {
            return [];
        }

        /** @var Param[] $params */
        $params = $this->docblock->getTagsByName('param');
        if (!$params) {
            return [];
        }

        $tmp = [];
        foreach ($params as $param) {
            $tmp[$param->getVariableName()] = $param;
        }

        return $tmp;
    }

    /**
     * 获取指定的param标签
     * @param $var
     * @return Param|null
     */
    public function paramTagByVar($var)
    {
        if (!($params = $this->paramTag())) {
            return null;
        }

        return isset($params[$var]) ? $params[$var] : null;
    }

    /**
     * @return bool
     */
    public function isDeprecated()
    {
        if (!$this->docblock) {
            return false;
        }

        return $this->docblock->hasTag('deprecated');
    }

    /**
     * 获取deprecated标签
     * @return array|bool
     */
    public function deprecatedTag()
    {
        if (!$this->isDeprecated()) {
            return false;
        }

        $tmp = [];

        /** @var DocBlock\Tags\Deprecated[] $tags */
        $tags = $this->docblock->getTagsByName('deprecated');
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if ($desc = $tag->getDescription()) {
                    $tmp[] = $desc->render();
                }
            }
        }

        return $tmp;
    }

    /**
     * @return array|bool
     */
    public function author()
    {
        if (!$this->docblock) {
            return [];
        }

        $tmp = [];

        /** @var DocBlock\Tags\Author[] $tags */
        $tags = $this->docblock->getTagsByName('author');
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $tmp[] = [
                    'authorName' => $tag->getAuthorName(),
                    'email' => $tag->getEmail(),
                ];
            }
        }

        return $tmp;
    }

    /**
     * 获取date标签值
     * @return string|null
     */
    public function date()
    {
        if (!$this->docblock) {
            return null;
        }

        if (!$this->docblock->hasTag('date')) {
            return null;
        }

        $tags = $this->docblock->getTagsByName('date');

        /** @var Generic $dateTag */
        $dateTag = $tags[0];
        if ($desc = $dateTag->getDescription()) {
            return $desc->render();
        }
    }

    /**
     * 代码字符串格式化
     * @param $data
     * @return string
     */
    protected function print($data)
    {
        $str = print_r($data, true);
        $str = str_replace("\n", '<br/>', $str);
        $str = str_replace(' ', '&nbsp;', $str);

        return '<br><code>' . $str . '</code>';
    }
}
