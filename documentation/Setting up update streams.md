# Update streams
ARS comes with integrated update stream provisioning. Update streams are, in short, a machine-readable representation of your repository. They can be used by remote clients to figure out if there is an update available and optionally ask the user to download it and install it. ARS can serve two types of update streams:

**INI format**  
This is a custom format, designed with portability in mind. The update information is rendered as a standard INI format text file. The downside of this format is that only the latest version is displayed in the stream.

**XML format**  
Joomla! comes with an extension updater. In order for it to work, it requires developers to supply update information in a custom XML format. There are actually three type of XML files Joomla! understands. The "extensionset" document serves as the master update stream. It contains links to one or more repositories, one for each extension type Joomla! understands, i.e. components, plugins, modules, libraries, packages, templates and files. The second form of an "extensionset" document is the master index of extensions of a specific type (called "category") known to this server, e.g. a list of components. Each item on that list points to an "updates" XML document. This third and final XML format contains the history of an extension. It tells us the version history, details about the extension, links to an information page and, most importantly, tells us where to download the extension from.

This is an overly complex system and maintaining such files manually can be a drag. ARS handles all three types of documents for you, automatically. In order to add updates support to your Joomla! extension, all you have to do is to add the following lines just before the closing &lt;/install&gt; tag in your extension's manifest XML file:

    <updateservers>
        <server type="extension" priority="1"
            name="Whatever you want to call it"><![CDATA[link_to_your_update_stream_xml]]></server>
    </updateservers>

The name attribute can be anything you want. Usually it should be something like "Name Of Extension Updates", e.g. "Akeeba Backup Core Updates". The link in the CDATA section is the XML link provided by ARS.

> **Important**
>
> In Joomla! 1.7, 2.5, 3.0, 3.1 and 3.2 your URL must end with `.xml` for Joomla! to be able to use it. This means that the long, non-SEF form of the URL, e.g. <http://www.example.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=1> mentioned in the XML link on the Update Streams page will not work. Just append [&dummy=extension.xml](&dummy=extension.xml) to it and it will work, e.g. <http://www.example.com/index.php?option=com_ars&view=update&task=stream&format=xml&id=1&dummy=extension.xml> for the example above. ARS will do that automatically for you when generating the Master Joomla! XML Update stream.

> **Warning**
>
> You MUST NOT leave a space or put a newline between the server open/close tag and its contents. Anything between those tags, including newlines and spaces, are considered part of the update URL and will cause problems with Joomla!'s integrated update system.

> **Note**
>
> When using the master XML stream, not the extension's versions stream, you should use type="collection" instead of type="extension"

> **Tip**
>
> It is not necessary to use CDATA if you escape ampersands (&) to &amp; per the XML standard.

In order to set up update streams, please click on the Update Streams button in ARS Control Panel.

On the very top of the page you will find a link to your master Joomla! update stream (the first type of "extensionset" XML document). You can use that link in your Joomla! extensions' XML files.

The grid below is a standard Joomla! administrator table. Clicking on a stream name allows you to edit it. The most important column is the Links column. It provides direct links to the publicly accessible formats of your update stream.

The INI link will take you to the INI format update stream, which is necessary for Live Update to work. More information on including Live Update in your components can be found in a later section of this documentation.

The XML link will take you to the Joomla! XML "update" document for that stream. This is the XML file you should in extension manifest files, inside the CDATA section mentioned above.

The JED link will take you to the Joomla! Extension Directory (JED) Remote XML document for that stream. You need to enter this URL in your JED listing to have JED automatically update the listing whenever you release a new version. Please note that at the time of this writing JED will only check your Remote XML URL once every 24 hours. This means that your listing will be updated with a delay up to one full day since the release of your extension's new version.

The D/L link is a special link which you can use in the Joomla! Extension Directory or anywhere you want to provide a direct link to the latest version of the item in the update stream. This URL will start the download if the latest version of the item specified in this update stream.

# Editing or adding an update stream

Editing or adding an update stream will present you with an editor page. Please note that update streams look for similarly named files (using a pattern) across all releases inside a specific category. You are called to provide this relationship in this page. The fields you have to fill in are:

**Stream Name**  
A name for the update stream. It can be anything you want and is only used in the Joomla! XML format streams.

> **Warning**
>
> This is what Joomla! will also display in the Extension Name when it lists new available updates.

**Alias**  
Used to construct the URL in the front-end. Keep it short and sweet.

**Extension type**  
This is used by the Joomla! XML format update stream.

**Category**  
The category where we're going to look for updates.

> **Note**
>
> You can have more than one update streams per category. However, a single update stream can only look inside a single category.

**Package naming pattern**  
An update stream looks for files following a specific naming convention. You have to supply a "shell pattern" in here. This is fancy wording for saying that you provide a filename and use a single question mark (?) to match any single character or a single start (\*) to match any number of characters. It's what you already use on your operating system! Only items whose File or URL field matches this pattern will be included in the update stream.

For instance, all Akeeba Backup Core installation packages are named com\_akeeba-VERSIONNUMBER-core.zip, where only VERSIONNUMBER changes, i.e. com\_akeeba-3.1-core.zip, com\_akeeba-3.1.5-core.zip etc. This leads us to a naming pattern of `com_akeeba-*-core.zip` and that's what I would use.

**Element**  
This is required for Joomla! update streams. It should contain the name of your extension, e.g. com\_something, mod\_something, plg\_something etc.

For example, Akeeba Backup Core installs in the com\_akeeba directory. This is the element name: com\_akeeba

**Site area (client\_id)**  
Since Joomla! 1.7.0, all update streams must indicate the site area (frontend or backend) the extension applies to. For component, library and file extensions you must always use "Backend". For plugins, modules and templates select "Frontend" or "Backend" depending on whether your extension applies to the frontend or the backend of your clients' sites respectively.

This is only required for XML update streams. If you are only interested in providing INI update streams, e.g. for desktop software, simply ignore this option.

**Folder (for plugins)**  
For all extension types except plugins, leave this blank. For plugins, this must be set to the plugin type. For example, if you have a system plugin, type in `system` in this box. If you have a content plugin, type in `content` in this box. You get the idea! This is the name of the plugins folder's subdirectory where the plugin is being installed to.

This is only required for XML update streams. If you are only interested in providing INI update streams, e.g. for desktop software, simply ignore this option.

**JED Extension ID**  
This is optional and only needs to be entered for extensions listed in the Joomla! Extensions Directory (JED). This is the numeric ID of your extension as listed in the JED. This is used for the Install from Web feature integration. For more information please take a look at the Content - Akeeba Release System Latest Version plugin which contains the plumbing to actually provide the URLs you need to use for the (quite convoluted) Install from Web feature.

**Published**  
Well, I guess you know what this means! Just note that unpublished streams result in empty pages

# The INI update format

The INI update format was designed with portability in mind. It is a deliberately simple format so that it can be parsed by PHP, as well as a plethora of programming languages such as Ruby, Python, Delphi, Pascal, C/C++, C\#, VB.NET, or even Visual Basic for Applications (if you consider *that* a programming language...). A typical stream looks like this:

    ; Live Update provision file
    software="Foo Bar"
    version="1.2.3"
    link="http://www.example.com/downloads/foobar-1.2.3.zip.html"
    date="2010-10-10"

The first line is a comment and is always there. It allow you to figure out if the rest of the file is a valid INI update stream.

The software key provide the name of the software item.

The version key provides the latest version published.

The link key provides a download link to the item linked to that update stream. Do note that due to Joomla!'s routing the extension of the file might always be .html! **Do not** trust the extension to tell you about the file type. When you initiate the download, ARS will set the correct MIME type in the HTTP headers. You should trust that header instead to figure out the real file type.

Finally, the date key gives you the release date of the latest version in YYYY-MM-DD format.