<?php

use Bolt\Application;
use Bolt\Configuration\Composer as Config;
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

        return $app;
    }
);
