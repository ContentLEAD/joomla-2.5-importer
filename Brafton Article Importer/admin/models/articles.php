<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
$params = JComponentHelper::getParams('com_media');
$path = "file_path";
define('COM_MEDIA_BASE', JPath::clean(JPATH_ROOT.DS.$params->get($path, 'images'.DS.'stories')));
require_once (JPATH_ADMINISTRATOR.DS.'components'.DS.'com_braftonarticles'.DS.'models'.DS.'parent.php');

class BraftonArticlesModelArticles extends BraftonArticlesModelParent
{
	protected function saveImage($source, $dest)
	{
		if ($this->loadingMechanism == "cURL")
		{
			$ch = curl_init($source);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			$image = curl_exec($ch);
			curl_close($ch);
			
			$result = file_put_contents($dest, $image);
			return $result !== false;
		}
		else if ($this->loadingMechanism == "allow_url_fopen")
		{
			return @copy($source, $dest);
		}
		
		return false;
	}
	
	public function getArticles()
	{
		$newsList = $this->feed->getNewsHTML(); //return an array of your latest news items with HTML encoding text. Note this is still raw data.
		
		foreach ($newsList as $article)
		{
			$contentRow = $this->getTable('content');
			$brContentRow = $this->getTable('braftoncontent');
			$brCategoryRow = $this->getTable('braftoncategories');
			if(!$this->article_exists($article, $brContentRow)) {
				$articleData['id'] = null;	// primary key, must be null to auto increment
				$articleData['title'] = $article->getHeadline();	// Title of the article
				// replace non-letter, non-number characters with dashes & collapse
				// this is preferred over trim() because it will properly capture at boundaries and inside words
				$articleData['alias'] = preg_replace(array('/[^a-zA-Z0-9]+/', '/^-+/', '/-+$/'), array('-', '', ''), strtolower($article->getHeadline()));
				
				/* Picture Importing time */
				$imgTmb = '';	// init as empty strings
				$imgFull = '';
				$photos = $article->getPhotos(); // PhotoInstance object
				if(!empty($photos)) {		// make sure there's photos present
				
					$thumbnail = $photos[0]->getThumb()->getURL();	// get the URL of the thumbnail
					$fullPic = $photos[0]->getLarge()->getURL();	// get the URL of the full pic
					
					$fullPic_base = basename($fullPic);		// grab just the end
					$thumbnail_base = basename($thumbnail);
					
					// Strip random numbers off of pictures
					// $firstPlace = strpos($pic_base, "_", 0);
					// $lastPlace = strrpos($pic_base, ".");
					// $pic_base = substr_replace($pic_base, '', $firstPlace - 1, $lastPlace - $firstPlace + 1);
					// put them in appropriate folders (COM_MEDIA_BASE is defined up top)
					$fullPic_folder = COM_MEDIA_BASE . DS . $fullPic_base;
					$thumbnail_folder = COM_MEDIA_BASE . DS . 'brafton_thumbs' . DS . $thumbnail_base;
					
					// Copy them over!  It'll be slow using both the thumbnail and full pic, but I'd rather not hardcode the CSS
					// just in case someone might want to change it.  I'll make an option for this later.
					if(($fullPic_base != null || $thumbnail_base != null) && !$this->saveImage($fullPic, $fullPic_folder) && !$this->saveImage($thumbnail, $thumbnail_folder))
						JLog::add('Warning: Failed to save any images.', JLog::WARNING, 'com_braftonarticles');
					else
					{
						// yeah, the images path is hardcoded.
						// deal with it.
						if ($thumbnail_base != null && strtolower($thumbnail_base) != 'null')
							$imgTmb = '<img src="' . JURI::base(true) . "/images/$thumbnail_base" . '" class="article-thumbnail" />';
						else
							$imgTmb = '<img src="' . JURI::base(true) . "/images/$fullPic_base" . '" class="article-image-thumbnail" />';
						$imgFull = '<img src="' . JURI::base(true) . "/images/$fullPic_base" . '" class="article-image" />';
					}
				}
				
				$articleData['introtext'] = $imgTmb . $article->getExtract();	// excerpt of the article
				$articleData['fulltext'] = $imgFull . $article->getText();	// content of the article
				/* End photo fun */
				
				$optsTable = $this->getTable('braftonoptions');
				$optsTable->load('published-state');
				$publishedState = $optsTable->value;
				
				if ($publishedState == 'Unpublished')
					$articleData['state'] = 0;
				else
					$articleData['state'] = 1;
				
				$optsTable = $this->getTable('braftonoptions');
				$optsTable->load('import-order');
				$importOrder = $optsTable->value;
				
				if ($importOrder == 'Published Date')
					$articleData['created'] = $article->getPublishDate();
				else if ($importOrder == 'Last Modified Date')
					$articleData['created'] = $article->getLastModifiedDate();
				else
					// fall back to created date - this handles invalid db values as well
					$articleData['created'] = $article->getCreatedDate();
				
				$articleData['publish_up'] = $articleData['created'];	// Same as created date, quicker to reference the variable
				
				// Grab the author from the options table
				$this->options->load('author');
				$articleData['created_by'] = $this->options->value;
				
				// Logic for setting the category
				/*****************************************************************************************************/
				$categories = $article->getCategories();	// Get the list of categories from the XML
				$category = $categories[0];	// since Joomla can only hold one category, just grab the first one
				$keys['brafton_cat_id'] = $category->getId();	// Set the keys to load the appropriate row
				$brCategoryRow->load($keys);	// Load up the row
				$articleData['catid'] = $brCategoryRow->cat_id;		// Set the category id according to the row it found
				/*****************************************************************************************************/
				
				$articleData['language'] = '*';
				
				// save it!
				$contentRow->save($articleData);
				
				// Then associate the brafton categories with the ones inserted
				// Using the JTable class, cat_id can't be the primary key.  When trying to save(), it detects it as the primary
				// and it assumes you're updating the row with that key, instead of adding a row.  A blank primary key is needed to insert.
				$brContentData['id'] = null;
				// Since $categoryRow now contains the data from the last insert, we can use this id to our advantage
				$brContentData['content_id'] = $contentRow->id;
				$brContentData['brafton_content_id'] = (int) $article->getId();
				$brContentRow->save($brContentData);
			} // end if article exists
		}
	} // end getArticles
	
	private function article_exists($article, $brContentRow) {
		
		$brContentID = $article->getId();
		$keys['brafton_content_id'] = $brContentID;
		$brContentRow->load($keys);
		// If the row returns a key and not null, we know it exists.  Otherwise, it doesn't so it's safe to add
		if(!empty($brContentRow->brafton_content_id))
			return true;
		else
			return false;
	}
}
?>
