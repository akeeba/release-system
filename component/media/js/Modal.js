/*!
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Object initialisation
if (typeof akeeba == 'undefined')
{
	var akeeba = {};
}

if (typeof akeeba.Modal == 'undefined')
{
	/*!
	 * jsModal - A pure JavaScript modal dialog engine v1.0d
	 * http://jsmodal.com/
	 *
	 * Author: Henry Rune Tang Kai <henry@henrys.se>
	 *
	 * (c) Copyright 2013 Henry Tang Kai.
	 *
	 * License: http://www.opensource.org/licenses/mit-license.php
	 *
	 * Date: 2013-7-11
	 *
	 * Modified by Akeeba Ltd:
	 * - [Oct 2016] Prefix "akeeba-modal-" instead of generic "modal-" to avoid conflicts with 3PD software e.g. Bootstrap.
	 * - [Oct 2016] Remove support for AJAX content. We use our own AJAX handlers which work around 3PD plugins corrupting content.
	 * – [Oct 2016] Added method show() which just calls open() for backwards compatibility reasons.
	 * – [Oct 2016] Added parameter "inherit" where you can give a query selector for an element whose content will be inherited by the modal.
	 * – [Oct 2016] open() returns the Modal object so that we can interact with it through external code.
	 * – [Nov 2016] Added parameter "iframe" where you can give a URL to load inside an IFRAME.
	 */
	akeeba.Modal = (function ()
	{
		"use strict";
		/*global document: false */
		/*global window: false */

		// create object method
		var method           = {},
			settings         = {},

			modalOverlay     = document.createElement('div'),
			modalContainer   = document.createElement('div'),
			modalHeader      = document.createElement('div'),
			modalContent     = document.createElement('div'),
			modalClose       = document.createElement('div'),

			inheritedElement = null,

			centerModal,

			closeModalEvent,

			defaultSettings  = {
				width:         'auto',
				height:        'auto',
				lock:          false,
				hideClose:     false,
				draggable:     false,
				closeAfter:    0,
				openCallback:  false,
				closeCallback: false,
				hideOverlay:   false
			};

		// I seem to be getting confused all the time
		method.show = function (parameters)
		{
			try
			{
				console.log('Using akeeba.Modal.show() is deprecated. Use .open() instead.');
			}
			catch (e)
			{
				// Do nothing if logging fails
			}

			return method.open(parameters);
		};

		// Open the modal
		method.open = function (parameters)
		{
			settings.width         = parameters.width || defaultSettings.width;
			settings.height        = parameters.height || defaultSettings.height;
			settings.lock          = parameters.lock || defaultSettings.lock;
			settings.hideClose     = parameters.hideClose || defaultSettings.hideClose;
			settings.draggable     = parameters.draggable || defaultSettings.draggable;
			settings.closeAfter    = parameters.closeAfter || defaultSettings.closeAfter;
			settings.closeCallback = parameters.closeCallback || defaultSettings.closeCallback;
			settings.openCallback  = parameters.openCallback || defaultSettings.openCallback;
			settings.hideOverlay   = parameters.hideOverlay || defaultSettings.hideOverlay;

			centerModal = function ()
			{
				method.center({});
			};

			modalContent.innerHTML = '';
			var element = null;

			if (parameters.content)
			{
				modalContent.innerHTML = parameters.content;
			}
			else if (parameters.inherit)
			{
				element = parameters.inherit;

				if (typeof parameters.inherit == 'string')
				{
					element = window.document.querySelector(parameters.inherit);
				}

				if ((element != null) && (element.innerHTML))
				{
					inheritedElement = element;

					while (inheritedElement.childNodes.length > 0)
					{
						modalContent.appendChild(inheritedElement.childNodes[0]);
					}
				}
			}
			else if (parameters.iframe)
			{
				element = window.document.createElement('iframe');
				element.setAttribute('src', parameters.iframe);
				element.setAttribute('width', parameters.width);
				element.setAttribute('height', parameters.height);
				element.setAttribute('frameborder', 0);
				element.setAttribute('marginheight', 0);
				element.setAttribute('marginwidth', 0);
				modalContent.appendChild(element);
			}

			modalContainer.style.width  = settings.width;
			modalContainer.style.height = settings.height;

			method.center({});

			if (settings.lock || settings.hideClose)
			{
				modalClose.style.visibility = 'hidden';
			}
			if (!settings.hideOverlay)
			{
				modalOverlay.style.visibility = 'visible';
			}
			modalContainer.style.visibility = 'visible';

			document.onkeypress = function (e)
			{
				if (e.keyCode === 27 && settings.lock !== true)
				{
					method.close();
				}
			};

			modalClose.onclick   = function ()
			{
				if (!settings.hideClose)
				{
					method.close();
				}
				else
				{
					return false;
				}
			};
			modalOverlay.onclick = function ()
			{
				if (!settings.lock)
				{
					method.close();
				}
				else
				{
					return false;
				}
			};

			if (window.addEventListener)
			{
				window.addEventListener('resize', centerModal, false);
			}
			else if (window.attachEvent)
			{
				window.attachEvent('onresize', centerModal);
			}

			if (settings.draggable)
			{
				modalHeader.style.cursor = 'move';
				modalHeader.onmousedown  = function (e)
				{
					method.drag(e);
					return false;
				};
			}
			else
			{
				modalHeader.onmousedown = function ()
				{
					return false;
				};
			}
			if (settings.closeAfter > 0)
			{
				closeModalEvent = window.setTimeout(function ()
				{
					method.close();
				}, settings.closeAfter * 1000);
			}
			if (settings.openCallback)
			{
				settings.openCallback();
			}

			return this;
		};

		// Drag the modal
		method.drag = function (e)
		{
			var xPosition   = (window.event !== undefined) ? window.event.clientX : e.clientX,
				yPosition   = (window.event !== undefined) ? window.event.clientY : e.clientY,
				differenceX = xPosition - modalContainer.offsetLeft,
				differenceY = yPosition - modalContainer.offsetTop;

			document.onmousemove = function (e)
			{
				xPosition = (window.event !== undefined) ? window.event.clientX : e.clientX;
				yPosition = (window.event !== undefined) ? window.event.clientY : e.clientY;

				modalContainer.style.left = ((xPosition - differenceX) > 0) ? (xPosition - differenceX) + 'px' : 0;
				modalContainer.style.top  = ((yPosition - differenceY) > 0) ? (yPosition - differenceY) + 'px' : 0;

				document.onmouseup = function ()
				{
					window.document.onmousemove = null;
				};
			};
		};

		// Close the modal
		method.close = function ()
		{
			if (!empty(inheritedElement))
			{
				while (modalContent.childNodes.length > 0)
				{
					inheritedElement.appendChild(modalContent.childNodes[0]);
				}

				inheritedElement = null;
			}

			modalContent.innerHTML = '';
			modalOverlay.setAttribute('style', '');
			modalOverlay.style.cssText    = '';
			modalOverlay.style.visibility = 'hidden';
			modalContainer.setAttribute('style', '');
			modalContainer.style.cssText    = '';
			modalContainer.style.visibility = 'hidden';
			modalHeader.style.cursor        = 'default';
			modalClose.setAttribute('style', '');
			modalClose.style.cssText = '';

			if (closeModalEvent)
			{
				window.clearTimeout(closeModalEvent);
			}

			if (settings.closeCallback)
			{
				settings.closeCallback();
			}

			if (window.removeEventListener)
			{
				window.removeEventListener('resize', centerModal, false);
			}
			else if (window.detachEvent)
			{
				window.detachEvent('onresize', centerModal);
			}
		};

		// Center the modal in the viewport
		method.center = function (parameters)
		{
			var documentHeight  = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight),

				modalWidth      = Math.max(modalContainer.clientWidth, modalContainer.offsetWidth),
				modalHeight     = Math.max(modalContainer.clientHeight, modalContainer.offsetHeight),

				browserWidth    = 0,
				browserHeight   = 0,

				amountScrolledX = 0,
				amountScrolledY = 0;

			if (typeof (window.innerWidth) === 'number')
			{
				browserWidth  = window.innerWidth;
				browserHeight = window.innerHeight;
			}
			else if (document.documentElement && document.documentElement.clientWidth)
			{
				browserWidth  = document.documentElement.clientWidth;
				browserHeight = document.documentElement.clientHeight;
			}

			if (typeof (window.pageYOffset) === 'number')
			{
				amountScrolledY = window.pageYOffset;
				amountScrolledX = window.pageXOffset;
			}
			else if (document.body && document.body.scrollLeft)
			{
				amountScrolledY = document.body.scrollTop;
				amountScrolledX = document.body.scrollLeft;
			}
			else if (document.documentElement && document.documentElement.scrollLeft)
			{
				amountScrolledY = document.documentElement.scrollTop;
				amountScrolledX = document.documentElement.scrollLeft;
			}

			if (!parameters.horizontalOnly)
			{
				modalContainer.style.top = amountScrolledY + (browserHeight / 2) - (modalHeight / 2) + 'px';
			}

			modalContainer.style.left = amountScrolledX + (browserWidth / 2) - (modalWidth / 2) + 'px';

			modalOverlay.style.height = documentHeight + 'px';
			modalOverlay.style.width  = '100%';
		};

		// Set the id's, append the nested elements, and append the complete modal to the document body
		modalOverlay.setAttribute('id', 'akeeba-modal-overlay');
		modalContainer.setAttribute('id', 'akeeba-modal-container');
		modalHeader.setAttribute('id', 'akeeba-modal-header');
		modalContent.setAttribute('id', 'akeeba-modal-content');
		modalClose.setAttribute('id', 'akeeba-modal-close');
		modalHeader.appendChild(modalClose);
		modalContainer.appendChild(modalHeader);
		modalContainer.appendChild(modalContent);

		modalOverlay.style.visibility   = 'hidden';
		modalContainer.style.visibility = 'hidden';

		if (window.addEventListener)
		{
			window.addEventListener('load', function ()
			{
				document.body.appendChild(modalOverlay);
				document.body.appendChild(modalContainer);
			}, false);
		}
		else if (window.attachEvent)
		{
			window.attachEvent('onload', function ()
			{
				document.body.appendChild(modalOverlay);
				document.body.appendChild(modalContainer);
			});
		}

		return method;
	}());
}
