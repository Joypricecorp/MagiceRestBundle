<?php

namespace Magice\Bundle\RestBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MagiceRestExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        switch ($config['serializer_naming_strategy']) {
            case 'camel':
                $strategy = 'JMS\Serializer\Naming\CamelCaseNamingStrategy';
                break;

            case 'serialized':
                $strategy = 'JMS\Serializer\Naming\SerializedNameAnnotationStrategy';
                break;

            default:
                $strategy = 'JMS\Serializer\Naming\IdenticalPropertyNamingStrategy';
        }

        $container->setParameter('magice.rest.serializer_naming_strategy', $strategy);
    }
}
