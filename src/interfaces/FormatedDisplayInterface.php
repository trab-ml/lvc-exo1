<?php

interface FormatedDisplayInterface
{
    /**
     * Format a tuple of values for displaying purposes
     * @param $type
     * @param $val
     * @param $qty
     * @return string
     */
    function format_line($type, $val, $qty): string;

    /**
     * Format a message for displaying purposes
     * @param $msg
     * @return string
     */
    function format_line_msg($msg): string;
}
