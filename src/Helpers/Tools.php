<?php

namespace Smart\Common\Helpers;

class Tools
{
    /**
     * 过滤掉rules中的required规则
     *
     * @param array $rules
     * @return array
     */
    public static function removeRequiredForRules(array $rules): array
    {
        foreach ($rules as $attribute => &$rule) {
            if (is_array($rule)) {
                $index = array_search('required', $rule);
                if (false !== $index) {
                    unset($rule[$index]);

                    continue;
                }
                $index = array_search('Required', $rule);
                if (false !== $index) {
                    unset($rule[$index]);

                    continue;
                }
            }

            if (is_string($rule)) {
                $rule = str_ireplace(['|required', 'required'], '', $rule);
                $rule = trim($rule, '|');
            }
        }

        return $rules;
    }
}
