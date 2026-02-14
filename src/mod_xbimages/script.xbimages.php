<?php
/*******
 * @package xbMusic
 * @filesource mod_xbimages/script.xbimages.php
 * @version 0.0.2.0 14th February 2026
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Version;

return new class () implements InstallerScriptInterface {

    protected $minphp = '8.2';
    protected $jminver = '5.0';
    protected $jmaxver = '7.0';
    protected $extension = 'mod_xbimages';
    protected $extname = 'xbImages';
    protected $extslug = 'xbimages';
    protected $ver = 'v1.2.3.4';
    protected $date = '32nd January 2024';
    protected $oldver = 'v1.2.3.4';
    protected $olddate = '32nd January 2024';
    
    
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {        
        if (($type != 'uninstall') && (version_compare(PHP_VERSION, $this->minphp, '<'))) {
            Factory::getApplication()->enqueueMessage(sprintf(Text::_('JLIB_INSTALLER_MINIMUM_PHP'), $this->minphp), 'error');
            return false;
        }

        $jversion = new Version();
        $jverthis = $jversion->getShortVersion();
        if ((version_compare($jverthis, $this->jminver,'lt')) || (version_compare($jverthis, $this->jmaxver, 'ge'))) {
            throw new RuntimeException($this->extname.' requires Joomla version greater than '.$this->jminver. ' and less than '.$this->jmaxver.'. You have '.$jverthis);
            return false;
        }
        // if we are updating then get the old version and date from component xml before it gets overwritten.
        if ($type=='update') {
            $oldmanifest = simplexml_load_file(Path::clean(JPATH_SITE . '/modules/'.$this->extension.'/'.$this->extension.'.xml'));
            $this->oldver = $oldmanifest->version;
            $this->olddate = $oldmanifest->creationDate;
        }
        return true;
    }

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {

        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        $app = Factory::getApplication();
        $manifest = $adapter->getManifest();
        $ext_mess = '<div style="position: relative; margin: 15px 15px 15px -15px; padding: 1rem; border:solid 1px #444; border-radius: 6px;">';
        if ($type == 'update') {
            $ext_mess .= '<p><b>'.$this->extname.'</b> module has been updated from '.$this->oldver.' of '.$this->olddate;
            $ext_mess .= ' to v<b>'.$manifest->version.'</b> dated '.$manifest->creationDate.'</p>';
            $ext_mess .= '<p>Check options for existing instances of xbImages on <a href="index.php?option=com_modules&view=select&client_id=0">Site Modules</a> page.</p>';
        }
        if (($type=='install') || ($type=='discover_install')) {
            $ext_mess .= '<h3>'.$this->extname.' module installed</h3>';
            $ext_mess .= '<p>version '.$manifest->version.' dated '.$manifest->creationDate.'</p>';
            $ext_mess .= '<p>Enable module and set options on <a href="index.php?option=com_modules&view=select&client_id=0">Site Modules</a> page.</p>';
        }
        if (($type=='install') || ($type=='discover_install') || ($type == 'update')) {
            $ext_mess .= '<p>For help and information see <a href="https://crosborne.co.uk/'.$this->extslug.'/doc" target="_blank" style="font-weight:bold; color:black;">www.crosborne.co.uk/'.$this->extslug.'/doc</a> ';
            $ext_mess .= '</div>';
            echo $ext_mess;
        }
        return true;
    }

};