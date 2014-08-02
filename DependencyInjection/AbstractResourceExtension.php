<?php
namespace Magice\Bundle\RestBundle\DependencyInjection;

use Sylius\Bundle\ResourceBundle\DependencyInjection\Driver\DatabaseDriverFactory;
use Sylius\Component\Resource\Exception\Driver\InvalidDriverException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

abstract class AbstractResourceExtension extends Extension
{
    const CONFIGURE_LOADER     = 1;
    const CONFIGURE_DATABASE   = 2;
    const CONFIGURE_PARAMETERS = 4;
    const CONFIGURE_VALIDATORS = 8;

    protected $applicationName = 'sylius';
    protected $configFileType = 'yml';
    protected $configDirectory = '/../Resources/config';
    protected $configFiles = array(
        'services',
    );

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $this->configure($config, new Configuration(), $container);
    }

    /**
     * @param array                  $config
     * @param ConfigurationInterface $configuration
     * @param ContainerBuilder       $container
     * @param integer                $configure
     * @return array
     */
    public function configure(
        array $config,
        ConfigurationInterface $configuration,
        ContainerBuilder $container,
        $configure = self::CONFIGURE_LOADER
    ) {
        $processor = new Processor();
        $config    = $processor->processConfiguration($configuration, $config);

        $config = $this->process($config, $container);

        $fileLoader = $this->configFileType == 'yml'
            ? '\Symfony\Component\DependencyInjection\Loader\YamlFileLoader'
            : '\Symfony\Component\DependencyInjection\Loader\XmlFileLoader';

        $loader = new $fileLoader($container, new FileLocator($this->getConfigurationDirectory()));

        $this->loadConfigurationFile($this->configFiles, $loader);

        if ($configure & self::CONFIGURE_DATABASE) {
            $this->loadDatabaseDriver($config, $loader, $container);
        }

        $classes = isset($config['classes']) ? $config['classes'] : array();

        if ($configure & self::CONFIGURE_PARAMETERS) {
            $this->mapClassParameters($classes, $container);
        }

        if ($configure & self::CONFIGURE_VALIDATORS) {
            $this->mapValidationGroupParameters($config['validation_groups'], $container);
        }

        if ($container->hasParameter('sylius.config.classes')) {
            $classes = array_merge($classes, $container->getParameter('sylius.config.classes'));
        }

        $container->setParameter('sylius.config.classes', $classes);

        return array($config, $loader);
    }

    /**
     * Remap class parameters.
     * @param array            $classes
     * @param ContainerBuilder $container
     */
    protected function mapClassParameters(array $classes, ContainerBuilder $container)
    {
        foreach ($classes as $model => $serviceClasses) {
            foreach ($serviceClasses as $service => $class) {
                $container->setParameter(
                    sprintf(
                        '%s.%s.%s.class',
                        $this->applicationName,
                        $service === 'form' ? 'form.type' : $service,
                        $model
                    ),
                    $class
                );
            }
        }
    }

    /**
     * Remap validation group parameters.
     * @param array            $validationGroups
     * @param ContainerBuilder $container
     */
    protected function mapValidationGroupParameters(array $validationGroups, ContainerBuilder $container)
    {
        foreach ($validationGroups as $model => $groups) {
            $container->setParameter(sprintf('%s.validation_group.%s', $this->applicationName, $model), $groups);
        }
    }

    /**
     * Load bundle driver.
     * @param array                 $config
     * @param FileLoader            $loader
     * @param null|ContainerBuilder $container
     * @throws InvalidDriverException
     */
    protected function loadDatabaseDriver(array $config, FileLoader $loader, ContainerBuilder $container)
    {
        $bundle    = str_replace(array('Extension', 'DependencyInjection\\'), array('Bundle', ''), get_class($this));
        $driver    = empty($config['driver']) ? 'doctrine/orm' : $config['driver'];
        $class     = new \ReflectionClass($bundle);
        $shortName = $class->getShortName();

        if ($class->hasMethod('getSupportedDrivers') && !in_array($driver, call_user_func(array($bundle, 'getSupportedDrivers')))) {
            throw new InvalidDriverException($driver, basename($bundle));
        }

        $this->loadConfigurationFile(array(sprintf('driver/%s', $driver)), $loader);

        $container->setParameter($this->getAlias() . '.driver', $driver);
        $container->setParameter($this->getAlias() . '.driver.' . $driver, true);

        foreach ($config['classes'] as $model => $classes) {

            // auto config templage namespace
            if (empty($config['templates'][$model])) {
                $config['templates'][$model] = sprintf('%s:%s', $shortName, self::underscoredToUpperCamelcase($model));
            }

            // auto config resource controller
            if (empty($classes['controller'])) {
                $classes['controller'] = 'Magice\\Bundle\\RestBundle\\Controller\\ResourceController';
            }

            if (array_key_exists('model', $classes)) {
                DatabaseDriverFactory::get(
                    $driver,
                    $container,
                    $this->applicationName,
                    $model,
                    $config['templates'][$model]
                )->load($classes);
            }
        }
    }

    /**
     * @param array      $config
     * @param FileLoader $loader
     */
    protected function loadConfigurationFile(array $config, FileLoader $loader)
    {
        foreach ($config as $filename) {
            if (file_exists($file = sprintf('%s/%s.%s', $this->getConfigurationDirectory(), $filename, $this->configFileType))) {
                $loader->load($file);
            }
        }
    }

    /**
     * Get the configuration directory
     * @return string
     * @throws \RuntimeException
     */
    protected function getConfigurationDirectory()
    {
        $reflector = new \ReflectionClass($this);
        $fileName  = $reflector->getFileName();

        if (!is_dir($directory = dirname($fileName) . $this->configDirectory)) {
            throw new \RuntimeException(sprintf('The configuration directory "%s" does not exists.', $directory));
        }

        return $directory;
    }

    /**
     * In case any extra processing is needed.
     * @param array            $config
     * @param ContainerBuilder $container
     * @return array
     */
    protected function process(array $config, ContainerBuilder $container)
    {
        // Override if needed.
        return $config;
    }

    /**
     * http://php.net/manual/en/function.ucwords.php#92092
     * @param $string
     * @return mixed
     */
    public static function  underscoredToUpperCamelcase($string)
    {
        return preg_replace_callback(
            '/(?:^|_)(.?)/',
            function ($str) {
                return strtoupper($str[1]);
            },
            $string
        );
    }
}