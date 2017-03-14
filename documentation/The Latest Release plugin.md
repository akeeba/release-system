Shown in the plugin manager as: Content - Akeeba Release System Latest Releases

Many times you want to include the latest published version number of a specific category in your content articles. Surely, you can just type it out. This works perfectly when you only do that in one or two places, provided that you remember to edit this information every single time you make a new release. However, after a while, it becomes too cumbersome. Especially when this information is present in more than two places and/or you have more than a handful of categories. This is where this plugin comes in to save your day. It allows you to automatically display the version number and URL of the latest release. Moreover, it allows you to link directly to specific items in the latest release of a category.

# Showing the latest release version number

Syntax:

    {arslatest release Category Name}

where Category Name is the name of the Akeeba Release System category (not the alias, the title). This will return the latest release's version number, e.g. 1.0.

# Creating links to the latest release

Syntax:

    {arslatest release_link Category Name}

where Category Name is the name of the Akeeba Release System category (not the alias, the title). This will return the URL to latest release's version number, e.g. 1.0. Please note that this is the URL, not a link! If you want to use it as a link you will need something like this:

    <a href="{arslatest release_link Category Name}">Your link text</a>

You can combine it with the version number syntax for something like this:

    <a href="{arslatest release_link Category Name}">My category,
    version {arslatest release Category Name}</a>

# Creating links to specific items in the latest release

Syntax:

    {arslatest item_link "pattern" Category Name}

where Category Name is the name of the Akeeba Release System category and pattern is a file naming pattern, enclosed in double quotes. For example, in order to create a link to a download item for a file whose names begins with com\_foobar and ends in .zip in the Foobar category you would have to do:

    {arslatest item_link 'com_foobar*.zip' Foobar}

Please note that this is the URL, not a link! If you want to use it as a link you will need something like this:

    <a href="{arslatest item_link 'com_foobar*.zip' Foobar}">Download latest version</a>

or combine it with the version number syntax to have something even more useful:

    <a href="{arslatest item_link 'com_foobar*.zip' Foobar}">Download
    Foobar version {arslatest release Foobar}</a>

# Creating links to specific items by means of the update stream ID

Syntax:

    {arslatest stream_link Stream ID}

where Stream ID is the numeric ID of an update stream, as shown in the leftmost column of the Update Streams page.

Please note that this is the URL, not a link! If you want to use it as a link you will need something like this:

    <a href="{arslatest stream_link 123}">Download latest version</a>

or combine it with the version number syntax to have something even more useful:

    <a href="{arslatest stream_link 123}">Download
    Foobar version {arslatest release Foobar}</a>

# Creating links for the Install from Web feature

> **Important**
>
> The System - Akeeba Release System integration with Install from Web plugin must be published for this feature to work.

This is used to provide integration with the Install from Web feature introduced in Joomla! 3.2. When a user clicks the Install or Buy & Install button in the "Install from Web" page on their site, the Joomla! Extensions Directory page will send your site the JED extension ID and a link to the user's site. Our plugin will generate a link that takes your user back to their site, with the installation URL for the latest version already filled in for them. Please note that you MUST have enabled the system plugin mentioned in the box above, otherwise this feature WILL NOT WORK AT ALL!

Syntax:

    {arslatest installfromweb Stream ID}

where Stream ID is the numeric ID of an update stream, as shown in the leftmost column of the Update Streams page. The Stream ID is not used if a JED extension ID has already been provided, i.e. the user is using the Install from Web feature. If, however, the user has not used the Install from Web feature the Stream ID above generates a download URL identical to the one of the stream\_link syntax above.

Yes, we understand this is confusing. You know what is more confusing? Trying to figure out how the Install from Web feature works based on useless documentation and convoluted code, but we digress.

Please note that this is the URL, not a link! If you want to use it as a link you will need something like this:

    <a href="{arslatest installfromweb 123}">Install or download latest version</a>

or combine it with the version number syntax to have something even more useful:

    <a href="{arslatest installfromweb 123}">Install or download
    Foobar version {arslatest release Foobar}</a>

> **Tip**
>
> You can use this feature in your successful login / subscription message page to let the user install / download your software.