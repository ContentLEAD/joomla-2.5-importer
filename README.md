Brafton Article Importer for Joomla! 2.5
==
The Brafton Article Importer loads custom content from Brafton, ContentLEAD, and Castleford XML feeds.

## Prerequisites ##
1. Joomla 2.5
2. MySQL database
3. PHP 5+ (5.3 recommended)

## Installation ##
1. [Download the Joomla 2.5 Component](https://github.com/ContentLEAD/joomla-2.5-importer/archive/master.zip).
2. Log in to your Joomla administrator section.
3. Under **Extensions**, browse to the **Extension Manager**.
4. Choose to upload a file under **Upload Package File**, then choose the component.
5. Click **Upload & Install**.
6. You will be presented with a message indicating if the installation was successful.

## Configuration ##
These settings are for version 0.8; if you have a lower version please update before referencing this document.

To activate automatic article importing, both the **Brafton Article Importer component** and the **Brafton Cron plugin** must be configured and enabled.

### Component Configuration ###
Within your Joomla administrator section, the component configuration can be found under **Components** > **Brafton Article Importer**.

#### Standard Settings ####

- **API Key**: (*required*) Your unique access key, in the format `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`. This will be provided by your account manager.
- **API Domain**: (*required*) The serving URL of your custom content. This will be provided by your account manager.
- **Post Author**: The Joomla user imported articles will be attributed to.

#### Advanced Settings ####
These settings are optional. Care should be taken when undergoing modifications.

- **Apply Article Updates**: Applies updates to article content when they are available.
- **Article Date**: Date to use as the article's Created Date attribute. This can be one of the following:
    - Created Date: Date the article was started
    - Published Date: Date the article was approved
    - Last Modified Date: Date the article was last edited
- **Published Status**: Initial published status of the imported article. Articles will be immediately posted if this is set to Published.
- **Parent Category**: Parent category for imported categories. Useful for automatically posting articles to a Category Blog.

### Plugin Configuration ###
Within your Joomla administrator section, the list of installed plugins can be found under **Extensions** > **Plugin Manager**. The Brafton Cron Plugin is listed under **System - Brafton Cron Plugin**.

#### Settings ####
- **Set Interval**: Minutes between each check for new articles. *Recommended value*: **180** (three hours). Lower values may cause excess strain on your server.

## Debugging ##
The importer stores a detailed log of its state and actions in a file called **com_braftonarticles.log.php** in your Joomla logs folder. This location is *(Joomla install root)*/logs/ by default.

When contacting support, *please include this file* to facilitate a quick response.