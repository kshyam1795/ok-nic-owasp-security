<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit33160d352b119168533ac3e12726daa3
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Sam\\OkNicOwaspSecurity\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Sam\\OkNicOwaspSecurity\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit33160d352b119168533ac3e12726daa3::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit33160d352b119168533ac3e12726daa3::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit33160d352b119168533ac3e12726daa3::$classMap;

        }, null, ClassLoader::class);
    }
}
