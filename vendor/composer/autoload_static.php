<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9a899760f4f059256f9724e1d755b5e7
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'LifterLMS\\CLI\\' => 14,
            'LLMS\\' => 5,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'LifterLMS\\CLI\\' => 
        array (
            0 => __DIR__ . '/../..' . '/libraries/lifterlms-cli/src',
        ),
        'LLMS\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'WP_Async_Request' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-async-request.php',
        'WP_Background_Process' => __DIR__ . '/..' . '/deliciousbrains/wp-background-processing/classes/wp-background-process.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9a899760f4f059256f9724e1d755b5e7::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9a899760f4f059256f9724e1d755b5e7::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9a899760f4f059256f9724e1d755b5e7::$classMap;

        }, null, ClassLoader::class);
    }
}