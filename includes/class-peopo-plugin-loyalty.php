<?php
namespace MihoMemberShip;

if (!defined("ABSPATH")) {
    exit();
}

final class Plugin
{
    private static $instance;

    public static function instance()
    {
        return self::$instance ?: (self::$instance = new self());
    }

    private function __construct()
    {
        // Chỗ này sau “expand”: enqueue assets, shortcode, admin page, Woo hooks...
    }
}
