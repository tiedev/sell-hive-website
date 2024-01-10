<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Noodlehaus\Config;
use Propel\Runtime\Propel;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Twig\Loader\FilesystemLoader;

return [

    Config::class => DI\factory(function (ContainerInterface $c) {
        $config = Config::load(['config.dist.yaml', '?config.yaml']);

        $data = $config->all();
        array_walk_recursive($data, function ($value, $key): void {
            if (is_string($value) && $value == 'need2configure') {
                echo "Please check configuration. (need2configure : $key)";
                die();
            }
        });

        return $config;
    }),

    LoggerInterface::class => DI\factory(function (ContainerInterface $c) {
        $config = $c->get(Config::class);

        $msgFormat = $config->get('monolog.format');
        $datetimeFormat = $config->get('monolog.datetime');
        $allowLineBreaks = $config->get('monolog.allowLineBreaks');
        $ignoreEmptyContextAndExtra = $config->get('monolog.ignoreEmptyContextAndExtra');
        $formatter = new LineFormatter($msgFormat, $datetimeFormat, $allowLineBreaks, $ignoreEmptyContextAndExtra);
        $loglevel = Logger::toMonologLevel($config->get('monolog.level'));
        $stream = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $loglevel);
        $stream->setFormatter($formatter);
        $logger = new Logger('sell-hive');
        $logger->pushHandler($stream);

        Propel::getServiceContainer()->setLogger('defaultLogger', $logger);

        return $logger;
    }),

    Twig::class => DI\factory(function (ContainerInterface $c) {
        $config = $c->get(Config::class);

        $loader = new FilesystemLoader('templates');

        return new Twig($loader, [
            'cache' => ($config->get('twig.cache') ? 'twigcache' : false),
            'debug' => ($config->get('twig.debug') ? 'true' : 'false'),
            'auto_reload' => ($config->get('twig.autoReload') ? 'true' : 'false')
        ]);
    })

];