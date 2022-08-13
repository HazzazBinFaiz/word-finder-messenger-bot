<?php

if (!function_exists('http_response_code'))
{
    function http_response_code($responseCode = NULL)
    {
        static $code = 200;
        if($responseCode !== NULL)
        {
            header('X-PHP-Response-Code: '.$responseCode, true, $responseCode);
            if(!headers_sent()) {
                $code = $responseCode;
            }
        }
        return $code;
    }
}