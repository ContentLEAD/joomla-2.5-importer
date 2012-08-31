<?php 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
JHTML::_('behavior.tooltip');
?>
<form action="index.php?option=com_braftonarticles&controller=braftonarticles&task=setOptions" method="post" name="adminForm">
<table>
	<tr>
	<td>Hover over a title for futher information on that field.</td>
	</tr>
	<tr>
		<td><?php echo JHTML::tooltip('This is the key provided by Brafton/ContentLEAD which is used to import articles', 'API Key', '', '<h2 class=admin-header>API Key</h2>'); ?>
			http://api.brafton.com/<input type="text" name="braftonxml_API_input" size=47 value=""/> <br />
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
</table>
</form>