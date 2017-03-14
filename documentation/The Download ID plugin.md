Shown in the plugin manager as: Content - Download ID

> **Note**
>
> This plugin only appeals to developers. If you are not a developer you don't need and you are not supposed to understand this part of the documentation. If you are not a developer, just disable this plugin.

If you want to provide updates to registered users / subscribers you will find out that the download URL in the XML and INI update streams results in a 403 Forbidden error. This is normal. These downloads are only available to registered users, but accessing the URL directly, from Joomla!'s perspective, is a guest (non logged in) visitor trying to access the URL. This of course results in a 403 Forbidden error and begs the question: how do you authenticate users?

The answer to that question is the Download ID. The Download ID is a unique, random hash which uniquely identifies the user to ARS. Appending the download URL with the dlid=download\_id query string parameter will allow ARS to authenticate the user and, if they have access to the download, proceed with the download.

You need to be able to communicate this Download ID to your users. This is where this plugin comes in use. Just enter

    {dlid}

in an article, Custom HTML module or anywhere else where content plugins are supported. It will be replaced with the download ID of the current user, e.g. abcdef01234567890abcdef01234567890, or a blank string if the user is a guest.

> **Important**
>
> Just because a Download ID is displayed for a user it doesn't mean that the user has access to any downloads. Think of the Download ID as a combined, abbreviated form of a username and a password.

> **Warning**
>
> Since ARS 3 a Download ID can only change when the user clicks on the Reset button in the Download ID page of ARS. On older versions of ARS the Download ID would change whenever your users changed or resets their password, *even if the new password is the same as the old one*.