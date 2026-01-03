<?php

declare(strict_types=1);

namespace robotshop\ratings;

use robotshop\ratings\Controller\HealthController;
use robotshop\ratings\Controller\RatingsApiController;
use robotshop\ratings\Service\CatalogueService;
use robotshop\ratings\Service\HealthCheckService;
use robotshop\ratings\Service\RatingsService;
use robotshop\ratings\Database;
use Monolog\Formatter\JsonFormatter;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel implements EventSubscriberInterface
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new MonologBundle(),
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE  => 'corsResponseFilter',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /* -------------------------
       CORS
    --------------------------*/
    public function corsResponseFilter(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $response->headers->add([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => '*',
        ]);
    }

    /* -------------------------
       Global exception logging
    --------------------------*/
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request   = $event->getRequest();
        $logger    = $this->getContainer()->get('logger');

        $logger->error('unhandled application exception', [
            'service'    => 'ratings',
            'error_type' => 'UNHANDLED_EXCEPTION',
            'exception'  => get_class($exception),
            'message'    => $exception->getMessage(),
            'path'       => $request->getPathInfo(),
            'method'     => $request->getMethod(),
        ]);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S0ME_SECRET',
            'router' => [
                'utf8' => true,
            ],
        ]);

        /* -------------------------
           Monolog (JSON to stdout)
        --------------------------*/
        $c->loadFromExtension('monolog', [
            'handlers' => [
                'stdout' => [
                    'type'     => 'stream',
                    'level'    => 'info',
                    'path'     => 'php://stdout',
                    'formatter'=> 'monolog.formatter.json',
                    'channels' => ['!request'],
                ],
            ],
        ]);

        $c->register('monolog.formatter.json', JsonFormatter::class)
            ->setPublic(false);


        /* -------------------------
           App config
        --------------------------*/
        $config = require __DIR__ . '/../config/config.php';

        $c->setParameter('catalogueUrl', $config['catalogue_url']);
        $c->setParameter('pdo_dsn',      $config['database']['dsn']);
        $c->setParameter('pdo_user',     $config['database']['user']);
        $c->setParameter('pdo_password', $config['database']['password']);
        $c->setParameter('jwt.secret',   $config['jwt_secret']);

        /* -------------------------
           Database
        --------------------------*/
        $c->register(Database::class)
            ->addArgument($c->getParameter('pdo_dsn'))
            ->addArgument($c->getParameter('pdo_user'))
            ->addArgument($c->getParameter('pdo_password'))
            ->addMethodCall('setLogger', [new Reference('logger')]);

        $c->register('database.connection', \PDO::class)
            ->setFactory([new Reference(Database::class), 'getConnection']);

        $c->setAlias(\PDO::class, 'database.connection');

        /* -------------------------
           Services
        --------------------------*/
        $c->register(CatalogueService::class)
            ->addArgument($c->getParameter('catalogueUrl'))
            ->addMethodCall('setLogger', [new Reference('logger')]);

        $c->register(HealthCheckService::class)
            ->addArgument(new Reference('database.connection'))
            ->addMethodCall('setLogger', [new Reference('logger')]);

        $c->register(RatingsService::class)
            ->addMethodCall('setLogger', [new Reference('logger')]);

        /* -------------------------
           Controllers
        --------------------------*/
        $c->register(HealthController::class)
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->addTag('controller.service_arguments');

        $c->register(RatingsApiController::class)
            ->addArgument(new Reference(CatalogueService::class))
            ->addArgument(new Reference(RatingsService::class))
            ->addArgument($c->getParameter('jwt.secret'))
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->addTag('controller.service_arguments');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__ . '/Controller/', '/', 'annotation');
    }
}