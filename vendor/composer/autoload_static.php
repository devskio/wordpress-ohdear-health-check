<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1a9c248631736736e6266a982ba8eb37
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'OhDear\\HealthCheckResults\\' => 26,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'OhDear\\HealthCheckResults\\' => 
        array (
            0 => __DIR__ . '/..' . '/ohdearapp/health-check-results/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1a9c248631736736e6266a982ba8eb37::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1a9c248631736736e6266a982ba8eb37::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit1a9c248631736736e6266a982ba8eb37::$classMap;

        }, null, ClassLoader::class);
    }
}