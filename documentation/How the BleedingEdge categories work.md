BleedingEdge (BE) categories are quite different than the Normal-type categories. You don't have to visit your site's back-end to publish and unpublish releases and items. It all happens automatically whenever you upload or delete files on your server. Here is the breakdown of how that works.

As all ARS categories, BleedingEdge categories are linked with a Files Directory. Unlike Normal-type categories, BE categories continuously monitor that directory for changes. Whenever you create a new subdirectory, a new ARS Release is created. The Version of the ARS Release is the directory's name and its Maturity is always set to "Alpha". Here is the coolness factor for software releases: If you upload a plain text file named CHANGELOG (yes, all caps and no extension), ARS will read it, compare it with the previous releases's CHANGELOG, colorize the result and use that as the ARS Release's description. The colorizer follows the Joomla! standard on marking changes in the CHANGELOG:

-   Lines starting with \# (hash) indicate bug fixes

-   Lines starting with + (plus) indicate added features

-   Lines starting with - (dash) indicate removed features

-   Lines starting with ~ (tilde) indicate miscellaneous changes

-   All other lines are comments and/or notes

If you're not interested in colorizing, changelogs and stuff like that, just keep in mind that if you upload a plain text file named CHANGELOG it will be used as your ARS Release's description.

Finally, for each file you upload an item be created and published. If you have set up Automatic Item Descriptions for the category they will, of course, be used for naming the file. ARS Releases and ARS Items created this way will inherit the access (view) level and AMBRA Group settings of the BE category.

Let's sum it up, OK?

-   Creating directories creates releases

-   A text file named CHANGELOG will be used to produce your release's description

-   Uploading files creates items

-   Access (view) levels and AMBRA Group settings are inherited from the category to the release, to the items

-   Deleting a directory unpublishes the release

-   Deleting a file unpublishes the item

-   You don't need to login to your back-end to manage the releases and items, but if you want to, you can.