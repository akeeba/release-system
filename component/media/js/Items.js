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
    (function ($) {
        var elAlias  = $("#alias");
        var oldAlias = elAlias.val();

        if (oldAlias === "")
        {
            var newAlias = basename($("#url").val());
            var qmPos    = newAlias.indexOf("?");

            if (qmPos >= 0)
            {
                newAlias = newAlias.substr(0, qmPos);
            }

            newAlias = newAlias.replace(" ", "-", "g");
            newAlias = newAlias.replace(".", "-", "g");

            elAlias.val(newAlias);
        }
    })(akeeba.jQuery);
};

arsItems.onFileChange = function (e) {
    (function ($) {
        var elAlias  = $("#alias");
        var oldAlias = elAlias.val();

        if (oldAlias === "")
        {
            var newAlias = basename($("#filename").val());

            newAlias = newAlias.replace(" ", "-", "g");
            newAlias = newAlias.replace(".", "-", "g");

            elAlias.val(newAlias);
        }

    })(akeeba.jQuery);
};

arsItems.showHideRows = function (populateFiles) {
    (function ($) {
        var elFilename = $("#filename");
        var elURL      = $("#url");

        elFilename.parent().hide();
        elURL.parent().hide();

        var currentType = $("#type").val();

        if (currentType === "file")
        {
            elFilename.parent().show();
            elFilename.attr("disabled", "disabled");

            if ((populateFiles === undefined) || populateFiles)
            {
                arsItems.populateFiles();
            }
        }
        else
        {
            elURL.parent().show();
        }
    })(akeeba.jQuery);
};

(function ($) {
    $(document).ready(function () {
        $("#url").blur(function (e) {
            arsItems.onLinkBlur(e);
        });

        arsItems.showHideRows(false);
        arsItems.populateFiles(Joomla.getOptions("ars.item_filename", ""));
    })
})(akeeba.jQuery);