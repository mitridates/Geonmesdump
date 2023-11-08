<?php
namespace App\Geonamesdump\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class GeonamesdumpExtension extends Extension
{

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Geonames registered?
        if (!isset($container->getParameter('kernel.bundles')['Geonames'])) {
            throw new \Exception(sprintf('%s not registered in "kernel.bundles". Please, download and register this bundle before!', 'Geonames'));
        }
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('sample.yml');
    }
}
