<?php
/**
 * @version 1.9.6
 * @package JEM
 * @copyright (C) 2013-2014 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;
?>

<form action="index.php?option=com_jem&amp;view=eventelement&amp;tmpl=component" method="post" name="adminForm" id="adminForm">

<table class="adminform">
	<tr>
		<td width="100%">
			<?php echo JText::_('COM_JEM_SEARCH').' '.$this->lists['filter']; ?>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->lists['search']; ?>" class="text_area" onChange="document.adminForm.submit();" />
			<button class="buttonfilter" type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button class="buttonfilter" type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</td>
		<td nowrap="nowrap">
			<select name="filter_state" class="inputbox" onchange="this.form.submit()">
			<option value=""><?php echo JText::_('JOPTION_SELECT_PUBLISHED');?></option>
			<?php echo JHtml::_('select.options', JHtml::_('jgrid.publishedOptions',array('all' => 0, 'unpublished' => 0,'archived' => 0, 'trash' => 0)), 'value', 'text', $this->filter_state, true);?>
			</select>
		</td>
	</tr>
</table>

<table class="table table-striped" id="articleList">
	<thead>
		<tr>
			<th class="center" width="5"><?php echo JText::_('COM_JEM_NUM'); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'COM_JEM_EVENT_TITLE', 'a.title', $this->lists['order_Dir'], $this->lists['order'], 'eventelement' ); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'COM_JEM_DATE', 'a.dates', $this->lists['order_Dir'], $this->lists['order'], 'eventelement' ); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'COM_JEM_START', 'a.times', $this->lists['order_Dir'], $this->lists['order'], 'eventelement' ); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'COM_JEM_VENUE', 'loc.venue', $this->lists['order_Dir'], $this->lists['order'], 'eventelement' ); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'COM_JEM_CITY', 'loc.city', $this->lists['order_Dir'], $this->lists['order'], 'eventelement' ); ?></th>
			<th class="title"><?php echo JText::_('COM_JEM_CATEGORY'); ?></th>
			<th class="center" width="1%" nowrap="nowrap"><?php echo JText::_('COM_JEM_PUBLISHED'); ?></th>
		</tr>
	</thead>

	<tfoot>
		<tr>
			<td colspan="8">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>

	<tbody>
			<?php foreach ($this->rows as $i => $row) : ?>
		<tr class="row<?php echo $i % 2; ?>">
			<td class="center"><?php echo $this->pagination->getRowOffset( $i ); ?></td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo JText::_('COM_JEM_SELECT');?>::<?php echo $row->title; ?>">
				<a style="cursor:pointer" onclick="window.parent.elSelectEvent('<?php echo $row->id; ?>', '<?php echo str_replace( array("'", "\""), array("\\'", ""), $row->title ); ?>');">
					<?php echo $this->escape($row->title); ?>
				</a></span>
			</td>
			<td>
				<?php
					//Format date
					echo JemOutput::formatLongDateTime($row->dates, null, $row->enddates, null);
				?>
			</td>
			<td>
				<?php
					//Prepare time
					if (!$row->times) {
						$displaytime = '-';
					} else {
						$time = strftime( $this->jemsettings->formattime, strtotime( $row->times ));
						$displaytime = $time.' '.$this->jemsettings->timename;
					}
					echo $displaytime;
				?>
			</td>
			<td><?php echo $row->venue ? $this->escape($row->venue) : '-'; ?></td>
			<td><?php echo $row->city ? $this->escape($row->city) : '-'; ?></td>
			<td>
			<?php 
			# we're referring to the helper due to the multi-cat feature
			echo implode(", ",JemOutput::getCategoryList($row->categories, false)); 
			?>
			</td>
			<td class="center">
				<?php echo JHtml::_('jgrid.published', $row->published, $i,'',false); ?>
			</td>
		</tr>
			<?php endforeach; ?>
	</tbody>

</table>

<p class="copyright">
	<?php echo JemAdmin::footer( ); ?>
</p>

<input type="hidden" name="task" value="" />
<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
</form>