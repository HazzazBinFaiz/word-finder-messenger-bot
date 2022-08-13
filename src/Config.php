<?php

namespace App;

class Config
{
    protected static $config = [];

    public static function getAppSecret() : string
    {
        return self::$config['app_secret'];
    }

    public static function getVerifyToken() : string
    {
        return self::$config['verify_token'];
    }

    public static function getAccessToken()
    {
        return self::$config['access_token'];
    }

    public static function set(mixed $config)
    {
        self::$config = $config;
    }
}