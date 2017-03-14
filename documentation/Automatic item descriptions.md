Most often than not, when dealing with software distribution, you need to provide the same description and title to similarly named files. For example, if you have an installable package of your software you may want to give short installation instructions and a link to the thorough installation guide in its description. Most likely, it will also have a standard title, e.g. "com\_foobar for Joomla! 1.5". Traditionally, this is dealt with by copying the release items from one release to the next. However, this is the least convenient way to do it, as you have to copy items, edit them one by one and change the file or URL they point to. That takes time. What's better is to have your download system somehow "know" the title and description of each download item. This is even more important with zero-click item publishing like what ARS does with its BleedingEdge-type repositories. So, ARS does exactly that: it knows the title and description of any file, with its Automatic Item Descriptions feature.

Launch this feature by using the Automatic Item Descriptions button in ARS Control Panel. You are presented with a standard Joomla! administration table. Editing an item presents you with an editor form, containing the following fields:

**Category**  
The category where this automatic item description will be applied to

**Package name pattern**  
The file naming pattern of the files which will be handled by this automatic description. For example, if you type in `com_foobar-*.zip` all items whose File or URL field matches this pattern (e.g. com\_foobar-1.0.zip, com\_foobar-extras-1.0.zip) will be assigned the Title and Description provided below if these fields are not provided when you create them.

**Title**  
The title to be applied to download items matching this automatic description's pattern rule.

**Published**  
If you want to temporarily disable an automatic description, set Published to No.

**Description**  
The description to be applied to download items matching this automatic description's pattern rule.

You can use Save & New to mass-create many Automatic Item Description records one after the other.