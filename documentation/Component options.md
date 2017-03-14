# Bleeding Edge options

By default, releases in Bleeding Edge categories automatically generate a CHANGELOG and save it in the release notes based on the changed lines between the CHANGELOG files found in a release which is about to be published and the previously published release in the same Bleeding Edge category. You can customise how that works with parameters found in the component Options page.

**Generate changelog for Bleeding Edge releases**  
When it's turned off the CHANGELOG will not be created and saved in the release notes.

**Colorise Changelog**  
By default ARS adds some class names to each CHANGELOG line to allow color-coding each line based on whether it's an addition, deletion, modification, bug fix and so on. When this option is disabled these classes are not added. This could allow, for example, a content plugin to perform its own custom formatting of the CHANGELOG.

# Front-end display options

If you want to fully customise the display of the component you have to do [template overrides](http://docs.joomla.org/How_to_override_the_output_from_the_Joomla!_core). But for simple stuff, like defining if the MD5 sum should be shown or to define what the users see when they are not allowed to access an item there are much simpler and more user friendly options.

**No Access URL**  
When a user tries to access a category, release or item that he has no access to he normally gets a standard-issue Joomla! 403 Forbidden page. Not really user-friendly. Sometimes it's best to direct them to a page explaining why they have no access and give them instructions to subscribe to your services. That's what this option does. Give the URL where the visitors will be directed when they try to access a category, release or item they have no access to.

**Show file size**  
Should the release display the file size for each item?

**Show MD5**  
Should the release display the MD5 sum for each item?

**Show SHA1**  
Should the release display the SHA1 sum for each item?

**Show environments (compatibility)**  
Should the release display the environment (compatibility) badges for each item?

# Direct Link Options

ARS is excellent for distributing subscription-based software. However this requires the users to log in to your site, download the file to their device / computer and then use it. This is just fine when your users are using a full-blown computer, e.g. a desktop or a laptop. This doesn't really work when they are using a mobile device like a smartphone or a tablet. In those cases it would be much better if you could just give them a "pre-signed" URL which allows them to download the software directly, without having to log in. If you are in the business of distributing Joomla! extensions (like us) this direct link can be used with Joomla!'s "Install from URL" feature to allow your users to easily install the extension on their site.

And that's exactly what the Direct Link is: a link (URL) which can be used with Joomla!'s "Install from URL" feature to install subscription-based extensions. Of course you may find more creative uses for it!

**Show Direct Link**  
When it's enabled a "Direct Link" link will be shown next to the regular download link of each item.

**Direct Link extensions**  
A comma-separated list of file extensions for which we should display Direct Link links. Do not include the dots. Do not leave spaces between the comma and the extension. We recommend using `zip,tar,tar.gz` in here.

**Direct Link description**  
The tooltip for the Direct Link links. It's a good idea to use this to explain to your users what the Direct Link is all about.