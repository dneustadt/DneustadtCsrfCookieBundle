<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DneustadtCsrfCookieExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('dneustadt_csrf_cookie.enable', $config['enable']);
        $container->setParameter('dneustadt_csrf_cookie.id', $config['id']);
        $container->setParameter('dneustadt_csrf_cookie.name', $config['name']);
        $container->setParameter('dneustadt_csrf_cookie.expire', $config['expire']);
        $container->setParameter('dneustadt_csrf_cookie.path', $config['path']);
        $container->setParameter('dneustadt_csrf_cookie.domain', $config['domain']);
        $container->setParameter('dneustadt_csrf_cookie.httpOnly', $config['httpOnly']);
        $container->setParameter('dneustadt_csrf_cookie.secure', $config['secure']);
        $container->setParameter('dneustadt_csrf_cookie.header', $config['header']);
        $container->setParameter('dneustadt_csrf_cookie.sameSite', $config['sameSite']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
