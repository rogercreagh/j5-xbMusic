<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Table/PlaylisttrackTable.php
 * @version 0.0.30.0 5th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\OutputFilter;
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


class PlaylisttrackTable extends Table implements VersionableTableInterface, TaggableTableInterface {
    
    use TaggableTableTrait;
    
    protected $_supportNullValue = true;
    
    public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
    {
        $this->typeAlias = 'com_xbmusic.playlisttrack';
        
        parent::__construct('#__xbmusic_trackplaylist', 'id', $db, $dispatcher);
        
        $this->setColumnAlias('ordering', 'listorder');
    }
    
    public function check()
    {
        try {
            parent::check();
        } catch (\Exception $e) {
            $this->setError($e->getMessage());
            
            return false;
        }
        
        // Set ordering
        if (empty($this->ordering)) {
            // Set ordering to last if ordering was 0
            $this->ordering = self::getNextOrder(); //$this->_db->quoteName('catid') . ' = ' . ((int) $this->catid) . ' AND ' . $this->_db->quoteName('status') . ' >= 0');
        }
                
        return true;
    }
    
    public function bind($array, $ignore = [])
    {
       
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
//             $table = new self($db, $this->getDispatcher());
            
//             if ($table->load(['alias' => $this->alias, 'catid' => $this->catid]) && ($table->id != $this->id || $this->id == 0)) {
//                 $this->setError(Text::_('Error alias not unique'));
                
//                 return false;
//             }
            
            // Store the new row
            parent::store($updateNulls);
            
            // Need to reorder ?
//             if ($oldrow->status >= 0 && ($this->status < 0 || $oldrow->catid != $this->catid)) {
//                 // Reorder the oldrow
//                 $this->reorder('');
//             }
        }
        
        return \count($this->getErrors()) == 0;
    }
    
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }
    
}
