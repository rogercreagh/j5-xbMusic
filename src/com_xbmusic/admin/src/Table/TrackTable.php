<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Table/TrackTable.php
 * @version 0.0.4.0 25th April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;


class TrackTable extends Table implements VersionableTableInterface, TaggableTableInterface {
    
    use TaggableTableTrait;
    
    protected $_supportNullValue = true;
    
    public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_xbmusic.track';
        
        parent::__construct('#__xbmusic_tracks', 'id', $db, $dispatcher);
        
        $this->created = Factory::getDate()->toSql();
        $this->setColumnAlias('published', 'status');
    }
    
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            
            return false;
        }
        
        // Set name
        $this->title = htmlspecialchars_decode($this->title, ENT_QUOTES);
        
        // Set alias
        if (trim($this->alias) == '') {
            $this->alias = $this->title;
        }
        
        $this->alias = ApplicationHelper::stringURLSafe($this->alias, $this->language);
        
        if (trim(str_replace('-', '', $this->alias)) == '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }
        
        // Check for a valid category.
//         if (!$this->catid = (int) $this->catid) {
//             $this->setError(Text::_('JLIB_DATABASE_ERROR_CATEGORY_REQUIRED'));
            
//             return false;
//         }
        
        // Set created date if not set.
        if (!(int) $this->created) {
            $this->created = Factory::getDate()->toSql();
        }
        
        // Set ordering
        if ($this->status < 0) {
            // Set ordering to 0 if state is archived or trashed
            $this->ordering = 0;
        } elseif (empty($this->ordering)) {
            // Set ordering to last if ordering was 0
            $this->ordering = self::getNextOrder($this->_db->quoteName('catid') . ' = ' . ((int) $this->catid) . ' AND ' . $this->_db->quoteName('status') . ' >= 0');
        }
        
        // Set modified to created if not set
        if (!$this->modified) {
            $this->modified = $this->created;
        }
        
        // Set modified_by to created_by if not set
        if (empty($this->modified_by)) {
            $this->modified_by = $this->created_by;
        }
        
        return true;
    }
    
    public function bind($array, $ignore = [])
    {
        if (isset($array['params']) && \is_array($array['params'])) {
            $registry = new Registry($array['params']);
            //check stuff in the params
        }
       
        return parent::bind($array, $ignore);
    }
    
    public function store($updateNulls = true)
    {
        $db = $this->getDbo();
        
        if (empty($this->id)) {

            parent::store($updateNulls);
        } else {
            // Get the old row
            $oldrow = new self($db, $this->getDispatcher());
            
            if (!$oldrow->load($this->id) && $oldrow->getError()) {
                $this->setError($oldrow->getError());
            }
            
            // Verify that the alias is unique
            $table = new self($db, $this->getDispatcher());
            
            if ($table->load(['alias' => $this->alias, 'catid' => $this->catid]) && ($table->id != $this->id || $this->id == 0)) {
                $this->setError(Text::_('Error alias not unique'));
                
                return false;
            }
            
            // Store the new row
            parent::store($updateNulls);
            
            // Need to reorder ?
            if ($oldrow->status >= 0 && ($this->status < 0 || $oldrow->catid != $this->catid)) {
                // Reorder the oldrow
                $this->reorder($this->_db->quoteName('catid') . ' = ' . ((int) $oldrow->catid) . ' AND ' . $this->_db->quoteName('status') . ' >= 0');
            }
        }
        
        return \count($this->getErrors()) == 0;
    }
    
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
    
}
