<?php

namespace RESTEssentials\Helper;

class Url {

    public static function removeSufix($string, $delimitator = '.') {
        $return = explode($delimitator, $string);
        return is_array($return) ? $return[0] : $return;
    }

}
