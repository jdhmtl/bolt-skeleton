<?php

use Bolt\Application;
use Bolt\Configuration\Composer as Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

return call_user_func(
    function() {
        $root_dir = dirname(dirname(__DIR__));
        require_once $root_dir . '/vendor/autoload.php';

        $config = new Config($root_dir);

        // If we have a .bolt.yml file, update our config object
        if (file_exists($root_dir . '/.bolt.yml')) {
            $yaml = Yaml::parse(file_get_contents($root_dir . '/.bolt.yml')) ?: [];
        }

        if (isset($yaml['paths'])) {
            foreach ($yaml['paths'] as $key => $path) {
                $config->setPath($key, $path);
            }
        }

        if (isset($yaml['urls'])) {
            foreach ($yaml['urls'] as $key => $url) {
                $config->setUrl($key, $url);
            }
        }

        $config->verify();

        $app = new Application(['resources' => $config]);

        $app->initialize();

        $fs = new Filesystem;

        // Copy base theme
        $source = $app['paths']['apppath'] . '/..' . $app['paths']['theme'];
        $target = $app['paths']['themepath'];
        $fs->mirror($source, $target);

        // Create missing directories and set correct permissions
        $db_path = $app['paths']['databasepath'];

        if (!$fs->exists($db_path)) {
            $fs->mkdir($db_path);
            $fs->chmod($db_path, 0777);
        }

        // Fix permissions on cache directory
        $cache_path = $app['paths']['cachepath'];
        $fs->chmod($cache_path, 0777);

        try {
            $fs->remove($cache_path . '/.version');
            $fs->remove($cache_path . '/development');
        } catch (\Exception $e) {
            /**
             * Initially deleting .version and /development so they will be
             * created and owned by web server. Once that's done they cannot
             * be deleted, but also don't need to be. An exception will be
             * thrown but we don't really need to care.
             */
        }

        return $app;
    }
);
