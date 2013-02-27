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
				
				$articleData['modified'] = $article->getLastModifiedDate();
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
	
	public function updateArticles()
	{
		$newsList = $this->feed->getNewsHTML();
		$updatedCount = 0;
		$errored = false;
		
		foreach ($newsList as $article)
		{
			$contentId = $this->getContentId($article);
			
			if ($this->isModified($contentId, $article))
			{
				$result = $this->updateArticle($contentId, $article);
				if ($result !== true)
				{
					JLog::add(sprintf('Error: Failed to update article: %s', $result), JLog::ERROR, 'com_braftonarticles');
					$errored = true;
				}
				else
					$updatedCount++;
			}
		}
		
		if ($updatedCount > 0)
			JLog::add(sprintf('Updated %d article%s.', $updatedCount, ($updatedCount == 1 ? '' : 's')), JLog::DEBUG, 'com_braftonarticles');
	}
	
	private function updateArticle($contentId, $article)
	{
		JLog::add(sprintf('Updating article "%s" (%d).', trim($article->getHeadline()), $article->getId()), JLog::DEBUG, 'com_braftonarticles');
		
		$content = $this->getTable('content');
		$content->load($contentId, true);
		
		/* fields we want to leave alone (joomla 2.5):
			- alias: affects the URL
			- state: respect the user's preferences
			- created: affects sort, preserve history
			- publish_up: preserve history
			- created_by: respect the user's preferences
			- language: asking for trouble
		*/
		$ignore = array('alias', 'state', 'created', 'publish_up', 'created_by', 'language');
		$data = $this->convertToContent($article, $contentId, $ignore);
		
		if (!$content->save($data, '', $ignore))
			return $content->getError();
		
		return true;
	}
	
	protected function convertToContent($article, $contentId = null, $ignore = array())
	{
		$data = array();
		
		$data['id'] = $contentId;
		$data['modified'] = $article->getLastModifiedDate();
		
		if (!in_array('title', $ignore))
			$data['title'] = trim($article->getHeadline());
		
		if (!in_array('alias', $ignore))
			$data['alias'] = preg_replace(array('/[^a-zA-Z0-9]+/', '/^-+/', '/-+$/'), array('-', '', ''), strtolower($article->getHeadline()));
		
		$introText = $article->getExtract();
		$fullText = $article->getText();
		
		$photos = $article->getPhotos();
		
		// photos are optional; having none is a valid state.
		if (!empty($photos))
		{
			$photo = $photos[0];
			$imagesFolder = JPATH_ROOT . '/images';
			$imagesUrl = JURI::base(true) . '/images';
			$filename = preg_replace(array('/[^a-zA-Z0-9]+/', '/^-+/', '/-+$/'), array('-', '', ''), strtolower($article->getHeadline()));
			
			$fullSizeFilename = null;
			$fullSizeSaved = false;
			
			// these are actually init'd as strings, not null.
			if ($photo->getLarge()->getURL() != "NULL")
			{
				$fullSizePhoto = $photo->getLarge();
				$fullSizeFilename = $filename . '.' . pathinfo($fullSizePhoto->getURL(), PATHINFO_EXTENSION);
				$fullSizePath = $imagesFolder . "/$fullSizeFilename";
				
				if ($this->saveImage($fullSizePhoto->getURL(), $fullSizePath))
				{
					$fullSizeSaved = true;
					$fullSizeUrl = $imagesUrl . "/$fullSizeFilename";
					$imageMarkup = sprintf('<div class="figure figure-full-size"><img src="%s" alt="%s" title="%s" class="article-image" /><p class="caption">%s</p></div>', $fullSizeUrl, $photo->getAlt(), $photo->getAlt(), $photo->getAlt());
					$fullText = $imageMarkup . $fullText;
				}
				else
					JLog::add(sprintf('Notice: Failed to save image %s (attached to article %s (%d)).', $fullSizePhoto->getURL(), trim($article->getHeadline()), $article->getId()), JLog::NOTICE, 'com_braftonarticles');
			}
			
			if ($photo->getThumb()->getURL() != "NULL")
			{
				$thumbPhoto = $photo->getThumb();
				$thumbFilename = $filename . '.' . pathinfo($thumbPhoto->getURL(), PATHINFO_EXTENSION);
				$thumbPath = $imagesFolder . "/$thumbFilename";
				
				if ($this->saveImage($thumbPhoto->getURL(), $thumbPath))
				{
					$thumbUrl = $imagesUrl . "/$thumbFilename";
					$imageMarkup = sprintf('<div class="figure figure-thumbnail"><img src="%s" alt="%s" title="%s" class="article-thumbnail" /></div>', $thumbUrl, $photo->getAlt(), $photo->getAlt(), $photo->getAlt());
					$introText = $imageMarkup . $introText;
				}
				else
					JLog::add(sprintf('Notice: Failed to save image %s (attached to article %s (%d)).', $thumbPhoto->getURL(), trim($article->getHeadline()), $article->getId()), JLog::NOTICE, 'com_braftonarticles');
			}
			// fallback to using full size if no thumbnail on feed
			else if ($fullSizeSaved)
			{
				$thumbUrl = $imagesUrl . "/$fullSizeFilename";
				$imageMarkup = sprintf('<div class="figure figure-thumbnail"><img src="%s" alt="%s" title="%s" class="article-image-thumbnail" /></div>', $thumbUrl, $photo->getAlt(), $photo->getAlt(), $photo->getAlt());
				$introText = $imageMarkup . $introText;
			}
		}
		
		if (!in_array('introtext', $ignore))
			$data['introtext'] = $introText;
		
		if (!in_array('fulltext', $ignore))
			$data['fulltext'] = $fullText;
		
		if (!in_array('state', $ignore))
		{
			$this->options->load('published-state');
			$publishedState = $this->options->value;
			
			if ($publishedState == 'Unpublished')
				$data['state'] = 0;
			else
				$data['state'] = 1;
		}
		
		$this->options->load('import-order');
		$importOrder = $this->options->value;
		
		if (!in_array('created', $ignore))
		{
			if ($importOrder == 'Published Date')
				$data['created'] = $article->getPublishDate();
			else if ($importOrder == 'Last Modified Date')
				$data['created'] = $article->getLastModifiedDate();
			else
				$data['created'] = $article->getCreatedDate();
			
			if (!in_array('publish_up', $ignore))
				$data['publish_up'] = $data['created'];
		}
		
		if (!in_array('created_by', $ignore))
		{
			$this->options->load('author');
			$data['created_by'] = $this->options->value;
		}
		
		if (!in_array('catid', $ignore))
		{
			$categories = $article->getCategories();
			$catId = null;
			
			if (empty($categories))
				JLog::add(sprintf('Notice: Article "%s" (%d) has no assigned categories.', trim($article->getHeadline()), $article->getId()), JLog::NOTICE, 'com_braftonarticles');
			else
			{
				$category = $categories[0];
				$catId = $this->getCategoryId($category);
				
				if (!$catId)
					JLog::add(sprintf('Warning: No category match for Brafton id %d (attached to article "%s" (%d)) found in the database.', $category->getId(), trim($article->getHeadline()), $article->getId()), JLog::WARNING, 'com_braftonarticles');
				else
					$data['catid'] = $catId;
			}
		}
		
		if (!in_array('language', $ignore))
			$data['language'] = '*';
		
		return $data;
	}
	
	private function getCategoryId($category)
	{
		$brafCatId = $category->getId();
		
		$db = JFactory::getDbo();
		$q = $db->getQuery(true);
		
		$q->select('cat_id')->from('#__brafton_categories')->where('brafton_cat_id = ' . $q->q($brafCatId));
		$db->setQuery($q);
		
		return $db->loadResult();
	}
	
	private function isModified($contentId, $article)
	{
		if (!$contentId || !$article)
			return false;
		
		return strtotime($article->getLastModifiedDate()) > strtotime($this->getContentLastModifiedDate($contentId));
	}
	
	private function getContentLastModifiedDate($contentId)
	{
		$db = JFactory::getDbo();
		$q = $db->getQuery(true);
		
		$q->select('modified')->from('#__content')->where('id = ' . $q->q($contentId));
		$db->setQuery($q);
		
		return $db->loadResult();
	}
	
	// content id = id in the content db table
	private function getContentId($article)
	{
		$brafArticleId = $article->getId();
		
		$db = JFactory::getDbo();
		$q = $db->getQuery(true);
		
		$q->select('content_id')->from('#__brafton_content')->where('brafton_content_id = ' . $q->q($brafArticleId));
		$db->setQuery($q);
		
		return $db->loadResult();
	}
}
?>
