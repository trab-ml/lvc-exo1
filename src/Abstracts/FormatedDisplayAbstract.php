<?php
namespace App\Abstracts;

abstract class FormatedDisplayAbstract
{
    /**
     * Format a tuple of values for displaying purposes
     * @param $type
     * @param $val
     * @param $qty
     * @return string
     */
    public function format_line_msg($msg): string {
        return "<section class='col-red xxl'>{$msg}</section>";
    }

    /**
     * Format a message for displaying purposes
     * @param $msg
     * @return string
     */
    public function format_line($type, $val, $qty): string {
        return "<p class='col-red'>{$type}(s) de {$val} <span class='xxl col-red'>X {$qty}</span>.</p>";
    }
}
