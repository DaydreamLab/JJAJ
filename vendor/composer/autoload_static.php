<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7865e8a007f3dcce4453aea28754222a
{
    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'DaydreamLab\\JJAJ\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'DaydreamLab\\JJAJ\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7865e8a007f3dcce4453aea28754222a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7865e8a007f3dcce4453aea28754222a::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
