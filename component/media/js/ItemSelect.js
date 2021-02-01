/*!
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Wait until akeeba.System is loaded
akeeba.Loader.add(["akeeba.System"], function ()
{
    // Attach click event handlers to all A elements with the hasArsItemProxy class
    akeeba.System.iterateNodes("a.hasArsItemProxy", function (item)
    {
        akeeba.System.addEventListener(item, "click", function (event)
        {
            // Cancel the default event handler
            event.preventDefault();

            var callbackName = akeeba.System.getOptions("ars.itemsProxyCallback", "");

            if (callbackName === "")
            {
                return;
            }

            // Get the row ID and title from the data attributes
            var elTarget = event.currentTarget;
            var rowId    = akeeba.System.data.get(elTarget, "arsrowid", 0);
            var title    = akeeba.System.data.get(elTarget, "arstitle", "ARS Item");

            // Call the proxy function
            window.parent[callbackName](rowId, title);

            return false;
        });
    });
});