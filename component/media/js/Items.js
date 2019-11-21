/*
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * JavaScript for backend Items view
 */

if (typeof (akeeba) == "undefined")
{
    var akeeba = {};
}

if (typeof (akeeba.jQuery) == "undefined")
{
    akeeba.jQuery = jQuery.noConflict();
}

var arsItems = {};

arsItems.onTypeChange = function (e) {
    arsItems.showHideRows();
};

arsItems.populateFiles = function (forceSelected) {
    (function ($) {
        var itemID    = Joomla.getOptions("ars.item_id", "");
        var releaseID = $("#release_id").val();
        var selected  = $("#filename").val();
        var token     = Joomla.getOptions("csrf.token", "");

        if (forceSelected)
        {
            selected = forceSelected;
        }

        var ajaxData    = {
            "option":     "com_ars",
            "view":       "Ajax",
            "format":     "raw",
            "task":       "getFiles",
            "item_id":    itemID,
            "release_id": releaseID,
            "selected":   selected
        };
        ajaxData[token] = 1;

        $.get(
            "index.php",
            ajaxData,
            function (data, textStatus) {
                var elFilename = $("#filename");

                elFilename.html(data);
                elFilename.removeAttr("disabled");
                elFilename.change(function (e) {
                    arsItems.onFileChange(e);
                });

                try
                {
                    elFilename.trigger("liszt:updated");
                }
                catch (e)
                {
                }

                arsItems.onFileChange();
            }
        )
    })(akeeba.jQuery);
};

arsItems.onLinkBlur = function (e) {
    var elAlias  = document.getElementById("alias");
    var oldAlias = elAlias.value;

    if (oldAlias === "")
    {
        var newAlias = basename(document.getElementById("url").value);
        var qmPos    = newAlias.indexOf("?");

        if (qmPos >= 0)
        {
            newAlias = newAlias.substr(0, qmPos);
        }

        newAlias = newAlias.replace(" ", "-", "g");
        newAlias = newAlias.replace(".", "-", "g");

        elAlias.value = newAlias;
    }
};

arsItems.onFileChange = function (e) {
    var elAlias  = document.getElementById("alias");
    var oldAlias = elAlias.value;

    if (oldAlias === "")
    {
        var newAlias = basename(document.getElementById("filename").value);

        newAlias = newAlias.replace(" ", "-", "g");
        newAlias = newAlias.replace(".", "-", "g");

        elAlias.value = newAlias;
    }
};

arsItems.showHideRows = function (populateFiles) {
    var elFilename = document.getElementById("filename");
    var elURL      = document.getElementById("url");

    elFilename.parentNode.style.display = 'none';
    elURL.parentNode.style.display = 'none';

    var currentType = document.getElementById("type").value;

    if (currentType === "file")
    {
        elFilename.parentNode.style.display = '';
        elFilename.setAttribute("disabled", "disabled");

        if ((populateFiles === undefined) || populateFiles)
        {
            arsItems.populateFiles();
        }
    }
    else
    {
        elURL.parentNode.style.display = '';
    }
};

akeeba.System.documentReady(function () {
    document.getElementById('url').addEventListener('focus', function(e){
        arsItems.onLinkBlur(e);
    });

    arsItems.showHideRows(false);
    arsItems.populateFiles(Joomla.getOptions("ars.item_filename", ""));
});
