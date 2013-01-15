<?php 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
JHTML::_('behavior.tooltip');
?>
<form action="index.php?option=com_braftonarticles" method="post" name="adminForm">
<table align="left">
	<tr>
	<td>Hover over a title for futher information on that field.</td>
	</tr>
	<tr>
		<td><?php echo JHTML::tooltip('This is the url/api key provided by Brafton/ContentLEAD which is used to import articles.  It should look something like<br/><b>http://api.brafton.com/eee83d24-906b-4736-91d9-1031621b85eb/</b>', 'API URL', '', '<h2 class=admin-header>API URL</h2>'); ?>	
			Please enter the full URL for your content below<br/>
			<input type="text" name="api-key" size="75" value="<?php echo $this->base_url . $this->api_key; ?>"/> <br />
		</td>
  	</tr>
	<tr>
		<td>
		<?php echo JHTML::tooltip('Changing the author after some articles have been uploaded 
		will ONLY change the author for recently uploaded articles, not the older ones.  This is (currently) working as intended.', 'Consider the Following', '', '<h2 class=admin-header>Post Author</h2>'); ?>
		Sets the post author for all imported entires<br/><br/>
			<select name="author">
				<?php foreach($this->authorList as $author): ?>
				<option 
					<?php if(($this->author) == $author->id): ?>
						 selected="selected"
					<?php endif; ?>
						value="<?php echo $author->id; ?>"><?php echo $author->name; ?>
				</option>
				<?php endforeach; ?>
			</select>
		</td>
		<input type="hidden" name="task" value="options.apply" />
	</tr>
	
	<tr>
		<td>
			<fieldset>
				<legend>Advanced Options (<a href="#" onclick="$$('div#brafton-advanced-opts').toggle();">Show/Hide</a>)</legend>
				<div id="brafton-advanced-opts" style="display: none;">
					<?php echo JHTML::tooltip('Sets the article\'s created date based on the following', 'Import Order', '', '<h2 class=admin-header>Import Order</h2>'); ?>
					<select name="import-order">
						<?php
							$opts = array('Created Date', 'Last Modified Date');
							foreach ($opts as $o) : ?>
							<option 
								<?php if ($this->importOrder == $o) : ?>
									selected="selected"
								<?php endif; ?>
							value="<?php echo $o; ?>"><?php echo $o; ?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</fieldset>
		</td>
	</tr>
</table>

</form>