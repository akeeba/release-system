ARS is a Joomla! component and as such it is limited by the way the front-end SEF (Search Engine Friendly) URL routing works. Due to the ambiguity of the routing in many cases the result you will get might not be the result you would expect but the one dictated by your Joomla! site's menu structure. In here we'll present a fool-proof way to set up your menu items for a software distribution site.

Before we begin, there is a VERY important thing to keep in mind. The menu items described in this section can either be displayed in your main menu, a secondary menu or even be "hidden". "Hidden" menu items are those that belong to a Menu that is never displayed in the front-end, i.e. there is no menu module pointing to it. Counter-intuitively, "hidden" menu items do participate in Joomla!'s SEF URL routing. We strongly recommend putting your ARS menu structure in a "hidden" menu and creating menu items of the "Alias" type in your regular menus. This will prevent routing ambiguity and ensure that you will ALWAYS have perfect results. For this reason the instructions below describe exactly this process.

Start by creating a new Menu from Menus, Manage, Add New Menu. Use the following information:

-   Title: `ARS Hidden`

-   Menu Type: `ars-hidden`

-   Description: `ARS menu structure, do not
                publish`

Now we have to create our base menu item for the repository. Go to Menus, ARS Hidden, Add New Menu Item. The Menu Item Type must be `Entire Repository`. Use any Menu Title and Alias you want. Do remember that the alias will become part of all your download links. For example, if you set Alias to download and your site is http://www.example.com then the URL http://www.example.com/download will lead to your download page (list of all ARS categories).

Once you save that menu item create one menu item for each of your ARS Categories. Each menu item needs the following setup:

-   Menu Type: Akeeba Release System, Category View

-   Parent Item: select the menu Entire Repository menu item you created above

> **Note**
>
> If you have a multilingual site you will need to [repeat
>           that process for every language](mailto:repeat
>           that process for every language) on your site.

You may also create BleedingEdge Releases, Normal Releases or Latest Releases menu items under (inside) the Entire Repository menu. If you really want to create a Release View menu item you should put it under the respective Category View menu item for its category – however we don't recommend creating Release View menu items at all!

Of course, if you have a use for these menu items, you can create menu items for Add-on Download IDs and Latest Releases next to –not under– the Entire Repository menu.

> **Important**
>
> You may have noticed the absence of any reference to JED Remote XML, XML Master Feed, XML Stream Feed and XML Category Feed menu items. **Do not create such menu items at all**! Due to the way Joomla! routing works you may end up with update URLs that have broken download or information URLs.

Now, how about providing menu items that your site's visitors can use? Simple. Go to the Menus menu and find your regular menu. Hover over it and choose Add new menu item. Now create a new menu item with menu item type System Links, Menu Item Alias. In the Menu Item drop-down choose one of the ARS Hidden menu items.

> **Note**
>
> When creating Menu Item Alias items the Alias field is ignored. Only the Menu Title is used to display the menu. The actual URL being used for the menu item is defined by the aliases and menu items structure you have set up in the ARS Hidden menu. That's why we need the hidden menu: it lets us define an unambiguous URL structure while alias menu items lets us link to that unambiguous URL structure!