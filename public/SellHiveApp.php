<?php

use DI\ContainerBuilder;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Noodlehaus\Config;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Propel\Runtime\Propel;

class SellHiveApp extends \DI\Bridge\Slim\App
{
    protected function configureContainer(ContainerBuilder $builder)
    {
        $definitions = [

            Config::class => function (ContainerInterface $c) {
                $config = Config::load(['config.dist.yaml', '?config.yaml']);

                $data = $config->all();
                array_walk_recursive($data, array($this, 'need2configure'));

                return $config;
            },

            LoggerInterface::class => function (ContainerInterface $c) {
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

                // TODO: this is not the best place but it works for now
                Propel::getServiceContainer()->setLogger('defaultLogger', $logger);

                return $logger;
            },

            Twig::class => function (ContainerInterface $c) {
                $config = $c->get(Config::class);

                $twig = new Twig('templates', [
                    'cache' => (boolval($config->get('twig.cache')) ? 'twigcache' : false),
                    'debug' => (boolval($config->get('twig.debug')) ? 'true' : 'false'),
                    'auto_reload' => (boolval($config->get('twig.autoReload')) ? 'true' : 'false')
                ]);

                $twig->addExtension(new Slim\Views\TwigExtension(
                    $c->get('router'),
                    $c->get('request')->getUri()
                ));

                return $twig;
            },

            'phpErrorHandler' => function (ContainerInterface $c) {
                return function ($request, $response, $exception) use ($c) {
                    $logger = $c->get(LoggerInterface::class);
                    $logger->critical('service failed', ['exception message' => $exception->getMessage(), 'stack trace' => $exception->getTraceAsString()]);
                    return $response->withStatus(500);
                };
            },

            'settings.displayErrorDetails' => true

        ];

        $builder->addDefinitions($definitions);
    }

    private function need2configure($item, $key)
    {
        if (is_string($item) && $item == 'need2configure') {
            echo "Please check configuration. (need2configure : $key)";
            die();
        }
    }
}
