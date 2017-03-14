In order to manage the categories in ARS, just click on the Categories button on the Control Panel page, or the Categories link right below the component's name in any ARS page. A standard Joomla! administrator list page appears. Do note that the search box below the Title field will filter categories based on their title or description.

You can quickly make a copy of a category by selecting its checkbox on the left-hand column and clicking the Copy button on the toolbar. The releases and items belonging to that category will not be copied along. Deleting a category by selecting it and clicking on the Delete button will remove all releases and items associated with that category. Beware! You will not be asked to confirm this choice. Deleting a category is an irreversible change. If you are not sure, unpublish instead of removing the category. You can edit a category either by clicking on its name in the list, or selecting it and clicking on the Edit button. You can add one or more new categories by clicking on the Newbutton.

**Editing or adding a category**

The category editor page consists of the following fields:

**Title**  
This is the title of the category displayed to your site's visitors

**Alias**  
This is the alias (slug) appended to the URL pointing to that category's page. It's best to keep it short and only use lowercase unaccented Latin letters (a-z), numbers (0-9), dashes and underscores. Anything else may behave oddly.

**Directory Type**    
You have two options. In a **Normal** directory you have to manually create releases and items. In a **BleedingEdge** directory all subdirectories created in the selected Files Directory will result into new releases being created and published without your intervention. Similarly, files uploaded in the subdirectories will be automatically turned into items and published.

**Files Directory**  
This is the path to a directory which contains the files to be published in this category's releases. The paths are given relative to your site's root. This field is mandatory. You can not create a category without assigning it a files directory. You can use the same directory in multiple releases. You can use directories in completely different base folders on each release. If a directory doesn't already exists it will not be created, you will see an error message and your category won't be created.

Please note that ARS uses Joomla!'s API to filter directories. This limits you to using directories under your site's root. If you want to protect your files from direct web access, please create a .htaccess file with the following content inside your files directory:

    <IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
    </IfModule>
    <IfModule mod_authz_core.c>
    <RequireAll>
    Require all denied
    </RequireAll>
    </IfModule>

**Published**   
Should it be visible to your users? Set to off to hide the category from view.

**Access**  
The Joomla! access level / view level to apply to the release. You can create custom view levels, effectively choosing which user groups should be granted access.

**Description**  
Use the WYSIWYG editor to type in a description to be displayed for this category. You can use plugins freely.

Please note that if a category is unavailable to a particular user the user will not see the category listed in the front-end and will be unable to access the category, its releases or download its items.

Clicking on the Save & New button will save the category and immediately let you create a new category.