<?php

declare(strict_types=1);

namespace robotshop\ratings;

use robotshop\ratings\Controller\HealthController;
use robotshop\ratings\Controller\RatingsApiController;
use robotshop\ratings\Service\CatalogueService;
use robotshop\ratings\Service\HealthCheckService;
use robotshop\ratings\Service\RatingsService;
use robotshop\ratings\Database;
use Monolog\Formatter\LineFormatter;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
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
            KernelEvents::RESPONSE => 'corsResponseFilter',
        ];
    }

    public function corsResponseFilter(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
        ]);
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S0ME_SECRET',
        ]);

        $c->loadFromExtension('monolog', [
            'handlers' => [
                'stdout' => [
                    'type' => 'stream',
                    'level' => 'info',
                    'path' => 'php://stdout',
                    'channels' => ['!request'],
                ],
            ],
        ]);

        // App parameters
        $config = require __DIR__ . '/config/config.php';

        $c->setParameter('catalogueUrl',  $config['catalogue_url']);
        $c->setParameter('pdo_dsn',       $config['database']['dsn']);
        $c->setParameter('pdo_user',      $config['database']['user']);
        $c->setParameter('pdo_password',  $config['database']['password']);
        $c->setParameter('logger.name',   $config['logger']['name']);

        // ⭐ NEW: JWT secret parameter
        $c->setParameter('jwt.secret',    $config['jwt_secret']);

        // Database
        $c->register(Database::class)
            ->addArgument($c->getParameter('pdo_dsn'))
            ->addArgument($c->getParameter('pdo_user'))
            ->addArgument($c->getParameter('pdo_password'))
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->setAutowired(true);

        $c->register('database.connection', \PDO::class)
            ->setFactory([new Reference(Database::class), 'getConnection']);

        $c->setAlias(\PDO::class, 'database.connection');

        // Services
        $c->register(CatalogueService::class)
            ->addArgument($c->getParameter('catalogueUrl'))
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->setAutowired(true);

        $c->register(HealthCheckService::class)
            ->addArgument(new Reference('database.connection'))
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->setAutowired(true);

        $c->register(RatingsService::class)
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->setAutowired(true);

        // Controllers
        $c->register(HealthController::class)
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->addTag('controller.service_arguments')
            ->setAutowired(true);

        // ⭐ UPDATED RatingsApiController registration WITH JWT secret
        $c->register(RatingsApiController::class)
            ->addArgument(new Reference(CatalogueService::class))
            ->addArgument(new Reference(RatingsService::class))
            ->addArgument($c->getParameter('jwt.secret'))       // pass JWT secret
            ->addMethodCall('setLogger', [new Reference('logger')])
            ->addTag('controller.service_arguments');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->import(__DIR__.'/Controller/', '/', 'annotation');
    }
}