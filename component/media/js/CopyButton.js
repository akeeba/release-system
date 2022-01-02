/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */
"use strict";

window.akeeba                          = window.akeeba || {};
window.akeeba.ReleaseSystem            = window.akeeba.ReleaseSystem || {};
window.akeeba.ReleaseSystem.CopyButton = {
    onClick: function (e)
             {
                 let clickElement = e.currentTarget;
                 let selector     = clickElement.dataset.copyTarget;

                 if (!selector)
                 {
                     return;
                 }

                 let elTarget = document.getElementById(selector);

                 if (!elTarget)
                 {
                     return;
                 }

                 navigator.clipboard.writeText(elTarget.innerText)
                          .then(function ()
                          {
                              Joomla.renderMessages({
                                  notice: [Joomla.Text._("COM_ARS_DLIDLABELS_LBL_COPIED")]
                              });
                          })
                          .catch(function (err)
                          {
                              Joomla.renderMessages({
                                  error: [Joomla.Text._("COM_ARS_DLIDLABELS_LBL_COPY_FAIL") + err]
                              });
                          })
             }
};

window.addEventListener("DOMContentLoaded", function ()
{
    document.querySelectorAll(".ars-copy-button")
            .forEach(function (elButton)
            {
                elButton.addEventListener("click", window.akeeba.ReleaseSystem.CopyButton.onClick);
            })
});