/*!
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

akeeba.Loader.add(["akeeba.System", "akeeba.fef.tabs"], function ()
{
    akeeba.fef.tabs();

    var release_info = document.querySelectorAll(".release-info-toggler");

    release_info.forEach(function (item)
    {
        item.addEventListener("click", function ()
        {
            var target   = this.getAttribute("data-target");
            var elTarget = document.getElementById(target);

            // If the element is visible, hide it
            if (window.getComputedStyle(elTarget).display === "block")
            {
                elTarget.style.display = "none";
            }
            else
            {
                elTarget.style.display = "";
            }
        })
    });
});