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
			</select>
		</td>
		<input type="hidden" name="task" value="options.apply" />
</table>

</form>