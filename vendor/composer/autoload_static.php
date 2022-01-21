<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit05fa3e9616e5ae69f2ebe4889170c85c
{
    public static $prefixLengthsPsr4 = array (
        'K' => 
        array (
            'Kevin\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Kevin\\' => 
        array (
            0 => __DIR__ . '/..' . '/getkevin/kevin-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit05fa3e9616e5ae69f2ebe4889170c85c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit05fa3e9616e5ae69f2ebe4889170c85c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit05fa3e9616e5ae69f2ebe4889170c85c::$classMap;

        }, null, ClassLoader::class);
    }
}
