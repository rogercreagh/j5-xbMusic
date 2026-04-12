<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/Artist/default.php
 * @version 0.0.61.5 12th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);

$item = $this->item;
$iconarr = array('facebook'=>'<i class="fab fa-facebook"></i>',
    'twitter'=>'<i class="fab fa-twitter"></i>',
    'instagram'=>'<i class="fab fa-instagram"></i>',
    'bandcamp'=>'<i class="fab fa-bandcamp"></i>',
    'spotify'=>'<i class="fab fa-spotify"></i>',
    'youtube'=>'<i class="fab fa-youtube-square"></i>',
    'website'=>'<i class="fas fa-globe"></i>'
);


?>
<div class="xbcomponent xbpvitem">
	<table style="width:100%;">
		<tr>
			<td>
                <h3><?php if ($item->type > 1): ?>
                		<i class='fas fa-users-line' >
                	<?php else : ?>
                		<i class='fas fa-user' ></i> 
                	<?php endif; ?>
                	<?php echo $item->name; ?>
                </h3>
				<?php if ($item->name != $item->sortname) : ?>
			    	<?php echo Text::_('XBMUSIC_SORTNAME').' '.$item->sortname; ?>
			    <?php endif; ?>
			    <?php if ($item->description != '') : 
			         echo Text::_('XB_DESCRIPTION'); ?>
			    	<div class="xbbox" style="width:100%;">
    					<?php echo $item->description; ?>
    				</div>
    			<?php endif; ?>
				<table>
					<?php if ($item->catid > 0) : ?>
  						<tr><td>
  							<?php echo Text::_('XB_CATEGORY'); ?>
    					</td><td>
                    		<span class="xblabel label-cat" >
                    			<?php echo $item->category_title; ?>
                    		</span>	
						</td></tr>
					<?php endif; ?>
					<?php if ($item->tags) : ?>
						<tr><td>
							<?php echo Text::_('XB_TAGS'); ?>
						</td><td>
				    		<?php foreach ($item->tags as $t) : ?>
    							<span class="xblabel label-tag">
    								<?php echo $t->title; ?>
    							</span><span style="width:15px;"></span>
    						<?php endforeach; ?>
						</td></tr>
        			<?php endif; ?>
          		</table>        
        	</td>
		<?php if ($item->imgurl != '') : ?>
			<td style="width:220px;">
				<?php  $src = trim($item->imgurl); ?>    	
    			<?php $tip = '<img src=\''.$src.'\'  />'; ?>
    			<img src="<?php echo $src; ?>" alt="<?php echo $item->name; ?>" 
    				class="hasPopover" title="" 
    				data-bs-content="<?php echo $tip; ?>" 				
    				style="width:200px;margin:0 0 20px 20px"  
        		/>
            </td>      
		<?php endif; ?>
		</tr>      
    </table>
    <?php if ($item->ext_links_cnt > 0) : ?>
          <?php echo Text::_('Internet Links'); ?>
		<div class="xbbox" style="width:100%;">
     		<table>
     			<?php foreach($item->ext_links as $link) : ?>
     				<tr>
                      <td>
     					<?php $key = trim(strtolower($link->link_text));
        			    if (key_exists($key, $iconarr)) : ?>
        			    	<span class="xbr12 xbpl10">
        			    		<a href="<?php echo $link->link_url; ?>" target="_blank" class="xbicomag"><?php echo $iconarr[$key]; ?></a>
        			        </span>
        			    	<?php echo $link->link_text; ?>
        			    <?php else : ?>
    			    		<a href="<?php echo $link->link_url; ?>" target="_blank">
    			        		<?php echo $link->link_text; ?>
    			        	</a>
        			    <?php endif; ?>
                        </td>
                      <td>
        			    <span class="xbnote xbpl20"><?php echo $link->link_desc; ?></span>
                        </td>
     				</tr>
     			<?php endforeach; ?>
     		</table>
     	</div>
    <?php endif; ?>
</div>
