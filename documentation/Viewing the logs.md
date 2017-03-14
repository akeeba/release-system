ARS keeps detailed logs of all download attempts of all items. In order to view you logs, please click on the Download Logs button on the component's Control Panel page. This takes you to the log viewer page.

The top row of the list contains the filters. Below the Item column you can find three filters:

**Category**  
Select a category from the drop-down box. When you select a category, the release drop-down will also be filtered and show releases belonging to that category.

**Release**  
Select a release from the drop-down box.

**The search box**  
Type in a part of an item's title so as to filter the list by it.

Below the User column you will find a search box. Type in a part of the user's full name, username or email address to filter the list. There are also two search boxes below the Referer and IP Address columns which work in a similar fashion should you need to filter the list by the HTTP referer or IP address respectively. Finally, you have Country and Authorized drop downs.

The columns which appear in the list are:

**Item**  
Displays the item title (top row) and the category and release (bottom row) pertaining to this download record

**User**  
Displays the user full name (top row) and username and email address (bottom row) of the user who tried to download this item. Download attempts by guest (not logged in) users are displayed as a single bullet.

**Accessed**  
The date and time of the download attempt. Do note that this is the server local time, which is not necessarily the same as your timezone.

**Referer**  
The HTTP referer set by the user's download client. It only makes sense if the download was initiated by a browser. This allows you to detect hot-linked files, i.e. people linking directly to your download item from their own sites.

**IP Address**  
The IP address of the user who attempted to download the item.

**Country**  
The country of origin of the particular IP address recorded. IP to country conversion is performed using a local copy of the (free version of) MaxMind's GeoLite2 Country database. This documentation contains instructions on updating it.

**Authorized**  
If the user was allowed to download the item, it is a green check. If the user was denied the download it is marked as a white X inside a red circle. Download are denied if the category, release or item are unpublished, or if the view level settings of a particular category, release or item indicate that a user should be denied access.

You can short by any column by simply clicking on its title.

> **Note**
>
> This product includes GeoLite data created by MaxMind, available from http://www.maxmind.com/.