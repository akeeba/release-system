/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.addEventListener("DOMContentLoaded", function ()
{
    // Attach click event handlers to all A elements with the hasArsItemProxy class
    document.querySelectorAll("a.hasArsItemProxy")
            .forEach(function (item)
            {
                item.addEventListener("click", function (event)
                {
                    // Cancel the default event handler
                    event.preventDefault();

                    var callbackName = Joomla.getOptions("ars.itemsProxyCallback", "");

                    if (callbackName === "")
                    {
                        return;
                    }

                    // Get the row ID and title from the data attributes
                    var elTarget = event.currentTarget;
                    var rowId    = elTarget.dataset.arsrowid ?? 0;
                    var title    = elTarget.dataset.arstitle ?? "ARS Item";

                    // Call the proxy function
                    window.parent[callbackName](rowId, title);

                    return false;
                });
            })
});
