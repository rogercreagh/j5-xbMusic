<?php
/*******
 * @package xbMusic
 * @filesource mod_xbimages/services/provider.php
 * @version 0.0.1.0 12th February 2026
 * @since 12th February 2026
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\Module as ModuleServiceProvider;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory as ModuleDispatcherFactoryServiceProvider;
use Joomla\CMS\Extension\Service\Provider\HelperFactory as HelperFactoryServiceProvider;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {

    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactoryServiceProvider('\\Crosborne\\Module\\Xbimages'));
        $container->registerServiceProvider(new HelperFactoryServiceProvider('\\Crosborne\\Module\\Xbimages\\Site\\Helper'));
        $container->registerServiceProvider(new ModuleServiceProvider());
    }
};