<?php

namespace Smart\Common\Helpers;

/**
 * 一个生成markdown文档内容的工具
 */
class MarkdownCreator
{
    protected $text = '';

    public function title(string $title)
    {
        $this->text .= '#' . $title . "\n\n";

        return $this;
    }

    public function table(array $title, array $items)
    {
        $text = '|' . implode('|', $title) . "|\n";

        $second = array_fill(0, count($title), ':----');

        $text .= '|' . implode('|', $second) . "|\n";

        foreach ($items as $item) {
            $text .= '|' . implode('|', $item) . "|\n";
        }
        $this->text .= $text . "\n";

        return $this;
    }

    public function div(string $title, array $items)
    {
        $text = '**' . $title . "**\n\n";

        foreach ($items as $item) {
            if (count($items) > 1) {
                $text .= ' - ';
            }

            $text .= $item . "\n";
        }

        $this->text .= $text . "\n";

        return $this;
    }

    public function line(string $title, string $small)
    {
        $this->text .= '**' . $title . '** _' . $small . "_\n\n";

        return $this;
    }

    public function p(string $title, string $content)
    {
        $text = '**' . $title . "**\n\n";
        $text .= $content . "\n\n";

        $this->text .= $text;

        return $this;
    }

    public function text(string $content)
    {
        $this->text .= $content . "\n";

        return $this;
    }

    public function render()
    {
        return $this->text;
    }
}
