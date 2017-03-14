> **Important**
>
> You can edit items even if the category or release they are in is unpublished, unlike DOCman.

In order to manage the items in ARS, just click on the Items button on the Control Panel page, or the Items link right below the component's name in any ARS page. A standard Joomla! administrator list page appears.

You can quickly make a copy of an item by selecting its checkbox on the left-hand column and clicking the Copy button on the toolbar. The copied item will be unpublished by default and have an ordering of 0. Deleting an item is an irreversible change. If you are not sure, unpublish instead of removing the item. You can edit an item either by clicking on its name (the Title column) in the list, or selecting it and clicking on the Edit button. You can add one or more new items by clicking on the Newbutton. You can edit the category the item is in by clicking on the name of the category in the Category column. You can edit the release the item is in by clicking the name of the release in the Release column.

**Editing or adding a release**

The release editor page consists of the following fields:

**Release**  
Choose the release where the item will be placed in.

> **Tip**
>
> If you had filtered the items list using a specific release, that release will be automatically selected in this field! Filter the list, save some time.

**Title**  
The title of the item, used to display it to your users. If you are using Automatic Descriptions you don't have to fill it in.

**Alias**  
This is the alias (slug) appended to the URL pointing to that category's page. It's best to keep it short and only use lowercase unaccented Latin letters (a-z), numbers (0-9), dashes and underscores. Anything else may behave oddly.

> **Tip**
>
> Leave it blank. As soon as you select a file or type in a link, ARS will automatically fill it in before your eyes. Didn't I already mention that ARS is designed to *save you lots of time*? But please note that ARS will also include the extension of the file (e.g. .zip) in the alias. Unless you've modified your .htaccess to redirect all extensions to Joomla!, this might cause 404 errors. In this case, just edit the automatic alias, removing the dot.

**Type**  
What kind of download is this. File items allow your visitors to download files stored on your server, the most common case of downloads. However, Link items allow you to link to a file or even a web page by its URL. You can use it to let your visitors download files hosted on external sites (e.g. your friend's site, JoomlaCode.org, SourceForge, Amazon S3, DropBox, Windows Live SkyDrive, RapidShare, MegaUpload etc) or redirect visitors to an external page. Using the Link type will not reveal the URL of the external item to your visitors until they proceed with downloading the item. Furthermore, all download links are marked as no-follow and no-index, so that search engines do not reveal the URL to the linked file/page.

**File Name**  
If you chose the File type, this displays a drop down of files found in the category's Files Directory and all of its subdirectories. Yes, that list could be very long, so we are cheating: we only show you the files which have not been already used in other File items on any category. So, instead of having to scroll through a never ending list of files you will only see the files you haven't already used. That's what we call a clever repository! Oh, yes... As soon as you choose a file, the Alias will be filled in if and only if it was empty.

**URL**  
If you chose the Link type, type in the full URL to the file or web page you want to link to. Oh, yes... As soon as you click anywhere outside this field, the Alias will be filled in automatically based on the contents of the URL field if and only if you had an empty alias.

**File size**  
Type in the file size in bytes. Or don't. If you leave it empty, ARS will try to determine this automatically. In the case of Link items, it will try to download the linked file.

**MD5 Signature**  
Type in the MD5 hash of the file. Or don't. If you leave it empty, ARS will try to determine this automatically. In the case of Link items, it will try to download the linked file.

**SHA1 Signature**  
Type in the SHA1 hash of the file. Or don't. If you leave it empty, ARS will try to determine this automatically. In the case of Link items, it will try to download the linked file.

**Hits**  
How many times the item has been downloaded. You can use this field to alter that number. Do note that you do not need to change it on copied items; copies will automatically receive a Hits number of zero.

**Published**  
Should it be visible to your users? Set to off to hide the category from view.

**Access**  
The Joomla! access level / view level to apply to the item. You can create custom view levels, effectively choosing which user groups should be granted access.

**Description**  
Use the WYSIWYG editor to type in a description to be displayed for this item. You can use plugins freely.

Please note that if an item is unavailable to a particular user the user will not see the item listed in the front-end page showing the release and will also be unable to download it.

Clicking on the Save & New button will save the item and immediately let you create a new category.