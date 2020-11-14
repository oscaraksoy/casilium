<?php

declare(strict_types=1);

namespace App;

use ContainerInteropDoctrine\EntityManagerFactory;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * The configuration provider for the App module
 *
 * @see https://docs.laminas.dev/laminas-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'view_helpers' => $this->getViewHelperConfig(),
        ];
    }

    /**
     * Returns the container dependencies
     */
    public function getDependencies(): array
    {
        return [
            'invokables' => [
                Command\TestCommand::class,
            ],
            'factories'  => [
                EntityManager::class => EntityManagerFactory::class,

                Handler\HomePageHandler::class => Handler\Factory\HomePageHandlerFactory::class,
                Handler\AdminPageHandler::class => Handler\Factory\AdminPageHandlerFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                'app'    => [__DIR__ . '/../templates/app'],
                'error'  => [__DIR__ . '/../templates/error'],
                'layout' => [__DIR__ . '/../templates/layout'],
            ],
        ];
    }

    /**
     * Returns the view helper configuration
     *
     * @return array
     */
    public function getViewHelperConfig(): array
    {
        return [
            'factories' => [
                View\Helper\Breadcrumbs::class => InvokableFactory::class,
            ],
            'aliases' => [
                'pageBreadcrumbs' => View\Helper\Breadcrumbs::class,
            ],
            'invokables' => [
                'flash' => View\Helper\Flash::class,
            ],
        ];
    }
}
