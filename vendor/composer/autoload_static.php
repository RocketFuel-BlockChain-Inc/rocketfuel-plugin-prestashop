<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5ce1a1ddb42d1bad591c3f75f839f747
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Rocketfuel\\Controller\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Rocketfuel\\Controller\\' => 
        array (
            0 => __DIR__ . '/../..' . '/controller',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Rocketfuel' => __DIR__ . '/../..' . '/Rocketfuel.php',
        'RocketfuelValidationModuleFrontController' => __DIR__ . '/../..' . '/controllers/front/validation.php',
        'Rocketfuel\\Controller\\Callback' => __DIR__ . '/../..' . '/controllers/Callback.php',
        'Rocketfuel\\Controller\\RestRouteController' => __DIR__ . '/../..' . '/controllers/RestRouteController.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5ce1a1ddb42d1bad591c3f75f839f747::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5ce1a1ddb42d1bad591c3f75f839f747::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5ce1a1ddb42d1bad591c3f75f839f747::$classMap;

        }, null, ClassLoader::class);
    }
}
