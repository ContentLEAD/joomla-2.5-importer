<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

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
	
	public function updateArticles()
	{
		$newsList = $this->feed->getNewsHTML();
		$updatedCount = 0;
		$errored = false;
		
		foreach ($newsList as $article)
		{
			$contentId = $this->getContentId($article);
			
			if ($this->articleModified($contentId, $article))
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
	
	private function loadArticle($article)
	{
		JLog::add(sprintf('Loading article "%s" (%d).', trim($article->getHeadline()), $article->getId()), JLog::DEBUG, 'com_braftonarticles');
		
		$content = $this->getTable('content');
		$data = $this->convertToContent($article);
		
		if (!$content->save($data))
			return $content->getError();
		
		$brafContent = $this->getTable('braftoncontent');
		$listing = array('content_id' => $content->id, 'brafton_content_id' => $article->getId(), 'id' => null);
		
		if (!$brafContent->save($listing))
			return $brafContent->getError();
		
		return true;
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
			- attribs: respect the user's preferences
			- language: asking for trouble
		*/
		$ignore = array('alias', 'state', 'created', 'publish_up', 'created_by', 'language');
		$data = $this->convertToContent($article, $contentId, $ignore);
		
		if (!$content->save($data))
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
		
		if (!in_array('metakey', $ignore))
			$data['metakey'] = trim($article->getHtmlMetaKeywords());
		
		if (!in_array('metadesc', $ignore))
			$data['metadesc'] = trim($article->getHtmlMetaDescription());
		
		if (!in_array('attribs', $ignore))
			$data['attribs'] = '{"show_title":"","link_titles":"","show_intro":"","show_category":"","link_category":"","show_parent_category":"","link_parent_category":"","show_author":"","link_author":"","show_create_date":"","show_modify_date":"","show_publish_date":"","show_item_navigation":"","show_icons":"","show_print_icon":"","show_email_icon":"","show_vote":"","show_hits":"","show_noauth":"","urls_position":"","alternative_readmore":"","article_layout":"","show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}';
		
		if (!in_array('access', $ignore))
			$data['access'] = '1';
		
		if (!in_array('metadata', $ignore))
			$data['metadata'] = '{"robots":"","author":"","rights":"","xreference":""}';
		
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
	
	public function loadArticles()
	{
		$newsList = $this->feed->getNewsHTML();
		$addedCount = 0;
		$errored = false;
		
		foreach ($newsList as $article)
		{
			$contentId = $this->getContentId($article);
			
			if (!$this->articleExists($article))
			{
				$result = $this->loadArticle($article);
				if ($result !== true)
				{
					JLog::add(sprintf('Error: Failed to update article: %s', $result), JLog::ERROR, 'com_braftonarticles');
					$errored = true;
				}
				else
					$addedCount++;
			}
		}
		
		if ($addedCount > 0)
			JLog::add(sprintf('Loaded %d article%s.', $addedCount, ($addedCount == 1 ? '' : 's')), JLog::DEBUG, 'com_braftonarticles');
	}
	
	private function articleExists($article)
	{
		if (!$article)
			return false;
		
		return !!$this->getContentId($article);
	}
	
	private function articleModified($contentId, $article)
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
