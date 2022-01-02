/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

/**
 * JavaScript for backend Items view
 */

if (typeof (akeeba) == "undefined")
{
    var akeeba = {};
}

var arsItems = {};

arsItems.populateFiles = function (forceSelected) {
    var itemID    = Joomla.getOptions("ars.item_id", "");
    var releaseID = document.getElementById("jform_release_id").value;
    var selected  = document.getElementById("jform_filename").value;
    var token     = Joomla.getOptions("csrf.token", "");

    if (forceSelected)
    {
        selected = forceSelected;
    }

    Joomla.request({
        url:       `index.php?option=com_ars&view=ajax&format=raw&task=getFiles&item_id=${itemID}&release_id=${releaseID}&selected=${selected}&${token}=1`,
        method:    "GET",
        perform:   true,
        onSuccess: data =>
                   {
                       let elFilename = document.getElementById("jform_filename");

                       elFilename.innerHTML = data;
                       elFilename.removeAttribute("disabled");

                       arsItems.onFileChange();
                   }
    });
};

arsItems.onLinkBlur = function (e)
{
    var elAlias  = document.getElementById("jform_alias");
    var oldAlias = elAlias.value;

    if (oldAlias !== "")
    {
        return;
    }

    var newAlias = basename(document.getElementById("jform_url").value);
    var qmPos    = newAlias.indexOf("?");

    if (qmPos >= 0)
    {
        newAlias = newAlias.substr(0, qmPos);
    }

    newAlias = newAlias.replace(" ", "-", "g");

    elAlias.value = newAlias;
};

arsItems.onFileChange = function (e)
{
    var elAlias  = document.getElementById("jform_alias");
    var oldAlias = elAlias.value;

    if (oldAlias !== "")
    {
        return;
    }

    var newAlias = basename(document.getElementById("jform_filename").value);
    newAlias     = newAlias.replace(" ", "-", "g");

    elAlias.value = newAlias;
};

window.addEventListener("DOMContentLoaded", function ()
{
    document.getElementById("jform_url").addEventListener("focus", arsItems.onLinkBlur);
    document.getElementById("jform_filename").addEventListener("change", arsItems.onFileChange);
    document.getElementById("jform_type").addEventListener("change", function (e)
    {
        if (document.getElementById("jform_type").value === "file")
        {
            arsItems.populateFiles();
        }
    });

    arsItems.populateFiles(Joomla.getOptions("ars.item_filename", ""));
});
