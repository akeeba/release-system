/*!
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

(function ()
{
    var isChromium  = window.chrome;
    var winNav      = window.navigator;
    var vendorName  = winNav.vendor;
    var isOpera     = typeof window.opr !== "undefined";
    var isIEedge    = winNav.userAgent.indexOf("Edge") > -1;
    var isIOSChrome = winNav.userAgent.match("CriOS");
    var isChrome    = isChromium !== null && typeof isChromium !== "undefined" && vendorName === "Google Inc." && isOpera === false && isIEedge === false;

    if (!isChrome && !isIOSChrome)
    {
        return;
    }

    document.getElementById("chromeFalsePositives").style.display = "block";
})(window, document);
