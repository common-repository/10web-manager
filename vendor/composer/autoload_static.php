<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935
{
    public static $files = array (
        '320cde22f66dd4f5d3fd621d3e88b98f' => __DIR__ . '/..' . '/symfony/polyfill-ctype/bootstrap.php',
        '5255c38a0faeba867671b61dfda6d864' => __DIR__ . '/..' . '/paragonie/random_compat/lib/random.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Polyfill\\Ctype\\' => 23,
            'Symfony\\Component\\Process\\' => 26,
            'Symfony\\Component\\Finder\\' => 25,
            'Symfony\\Component\\Filesystem\\' => 29,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'PhpZip\\' => 7,
        ),
        'A' => 
        array (
            'Araqel\\Archive\\' => 15,
            'Alchemy\\Zippy\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Polyfill\\Ctype\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-ctype',
        ),
        'Symfony\\Component\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/process',
        ),
        'Symfony\\Component\\Finder\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/finder',
        ),
        'Symfony\\Component\\Filesystem\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/filesystem',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'PhpZip\\' => 
        array (
            0 => __DIR__ . '/..' . '/nelexa/zip/src',
        ),
        'Araqel\\Archive\\' => 
        array (
            0 => __DIR__ . '/..' . '/10web/archive/src',
        ),
        'Alchemy\\Zippy\\' => 
        array (
            0 => __DIR__ . '/..' . '/alchemy/zippy/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'K' => 
        array (
            'KubAT\\PhpSimple\\HtmlDomParser' => 
            array (
                0 => __DIR__ . '/..' . '/kub-at/php-simple-html-dom-parser/src',
            ),
        ),
        'D' => 
        array (
            'Doctrine\\Common\\Collections\\' => 
            array (
                0 => __DIR__ . '/..' . '/doctrine/collections/lib',
            ),
        ),
        'C' => 
        array (
            'Console' => 
            array (
                0 => __DIR__ . '/..' . '/pear/console_getopt',
            ),
        ),
        'A' => 
        array (
            'Archive_Tar' => 
            array (
                0 => __DIR__ . '/..' . '/pear/archive_tar',
            ),
        ),
    );

    public static $fallbackDirsPsr0 = array (
        0 => __DIR__ . '/..' . '/pear/pear-core-minimal/src',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PEAR_Exception' => __DIR__ . '/..' . '/pear/pear_exception/PEAR/Exception.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935::$prefixesPsr0;
            $loader->fallbackDirsPsr0 = ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935::$fallbackDirsPsr0;
            $loader->classMap = ComposerStaticInitc85afd90e3f5aad218c48a38da9a4935::$classMap;

        }, null, ClassLoader::class);
    }
}
