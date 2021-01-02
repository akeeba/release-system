/*!
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

if (typeof akeeba == 'undefined')
{
	var akeeba = {};
}

if (typeof akeeba.System == 'undefined')
{
	akeeba.System = {};

	akeeba.System.documentReady = function (callback, context)
	{
	};

	akeeba.System.notification = {
		hasDesktopNotification: false,
		iconURL:                ''
	};
	akeeba.System.params       = {
		AjaxURL:               '',
		useIFrame:             false,
		errorCallback:         akeeba.System.modalErrorHandler,
		iFrame:                null,
		iFrameCallbackError:   null,
		iFrameCallbackSuccess: null,
		password:              '',
		errorDialogId:         'errorDialog',
		errorDialogMessageId:  'errorDialogPre'
	};
	akeeba.System.modalDialog  = null;

}

/**
 * An extremely simple error handler, dumping error messages to screen
 *
 * @param  error  The error message string
 */
akeeba.System.defaultErrorHandler = function (error)
{
	alert("An error has occurred\n" + error);
};

/**
 * An error handler based on Bootstrap modal. It requires you to set up a modal dialog div with id errorDialog
 *
 * @param  error  The error message string
 */
akeeba.System.modalErrorHandler = function (error)
{
	var dialog_element =
			document.getElementById(akeeba.System.params.errorDialogId);

	var errorContent = 'error';

	if (dialog_element != null)
	{
		document.getElementById(akeeba.System.params.errorDialogMessageId).innerHTML = error;
		errorContent                                                                 = dialog_element.innerHTML;
	}

	akeeba.Modal.open({
		content: errorContent,
		width:   '80%'
	});
};

akeeba.System.params.errorCallback = akeeba.System.modalErrorHandler;

/**
 * Poor man's AJAX, using IFRAME elements
 *
 * @param  data             An object with the query data, e.g. a serialized form
 * @param  successCallback  A function accepting a single object parameter, called on success
 * @param  errorCallback    Error handler
 */
akeeba.System.doIframeCall = function (data, successCallback, errorCallback)
{
	akeeba.System.params.iFrameCallbackSuccess = successCallback;
	akeeba.System.params.iFrameCallbackError   = errorCallback;
	akeeba.System.params.iFrame                = document.createElement('iframe');

	var responseTimer                            = document.getElementById('response-timer');
	akeeba.System.params.iFrame.style.display    = 'none';
	akeeba.System.params.iFrame.style.visibility = 'hidden';
	akeeba.System.params.iFrame.style.height     = '1px';
	akeeba.System.params.iFrame.setAttribute('onload', akeeba.System.iframeCallback);

	var url = akeeba.System.params.AjaxURL + '&' + akeeba.Ajax.interpolateParameters(data);

	akeeba.System.params.iFrame.setAttribute('src', url);
};

/**
 * Poor man's AJAX, using IFRAME elements: the callback function
 */
akeeba.System.iframeCallback = function ()
{
	// Get the contents of the iFrame
	var iframeDoc = null;

	if (akeeba.System.params.iFrame.contentDocument)
	{
		iframeDoc = akeeba.System.params.iFrame.contentDocument; // The rest of the world
	}
	else
	{
		iframeDoc = akeeba.System.params.iFrame.contentWindow.document; // IE on Windows
	}

	var msg = iframeDoc.body.innerHTML;

	// Dispose of the iframe
	akeeba.System.params.iFrame.parentNode.removeChild(akeeba.System.params.iFrame);
	akeeba.System.params.iFrame = null;

	// Start processing the message
	var junk    = null;
	var message = "";

	// Get rid of junk before the data
	var valid_pos = msg.indexOf('###');

	if (valid_pos == -1)
	{
		// Valid data not found in the response
		msg = 'Invalid AJAX data: ' + msg;

		if (akeeba.System.params.iFrameCallbackError == null)
		{
			if (akeeba.System.params.errorCallback != null)
			{
				akeeba.System.params.errorCallback(msg);
			}
		}
		else
		{
			akeeba.System.params.iFrameCallbackError(msg);
		}

		return;
	}
	else if (valid_pos != 0)
	{
		// Data is prefixed with junk
		junk    = msg.substr(0, valid_pos);
		message = msg.substr(valid_pos);
	}
	else
	{
		message = msg;
	}

	message = message.substr(3); // Remove triple hash in the beginning

	// Get of rid of junk after the data
	valid_pos = message.lastIndexOf('###');
	message   = message.substr(0, valid_pos); // Remove triple hash in the end

	try
	{
		var data = JSON.parse(message);
	}
	catch (err)
	{
		msg = err.message + "\n<br/>\n<pre>\n" + message + "\n</pre>";

		if (akeeba.System.params.iFrameCallbackError == null)
		{
			if (akeeba.System.params.errorCallback != null)
			{
				akeeba.System.params.errorCallback(msg);
			}
		}
		else
		{
			akeeba.System.params.iFrameCallbackError(msg);
		}

		return;
	}

	// Call the callback function
	akeeba.System.params.iFrameCallbackSuccess(data);
};

/**
 * Performs an AJAX request and returns the parsed JSON output.
 * akeeba.System.params.AjaxURL is used as the AJAX proxy URL.
 * If there is no errorCallback, the global akeeba.System.params.errorCallback is used.
 *
 * @param  data             An object with the query data, e.g. a serialized form
 * @param  successCallback  A function accepting a single object parameter, called on success
 * @param  errorCallback    A function accepting a single string parameter, called on failure
 * @param  useCaching       Should we use the cache?
 * @param  timeout          Timeout before cancelling the request (default 60s)
 */
akeeba.System.doAjax = function (data, successCallback, errorCallback, useCaching, timeout)
{
	if (akeeba.System.params.useIFrame)
	{
		akeeba.System.doIframeCall(data, successCallback, errorCallback);

		return;
	}

	if (useCaching == null)
	{
		useCaching = true;
	}

	// We always want to burst the cache
	var now                = new Date().getTime() / 1000;
	var s                  = parseInt(now, 10);
	var microtime          = Math.round((now - s) * 1000) / 1000;
	data._cacheBustingJunk = microtime;

	if (timeout == null)
	{
		timeout = 600000;
	}

	var structure =
		{
			type:    "POST",
			url:     akeeba.System.params.AjaxURL,
			cache:   false,
			data:    data,
			timeout: timeout,
			success: function (msg)
					 {
						 // Initialize
						 var junk    = null;
						 var message = "";

						 // Get rid of junk before the data
						 var valid_pos = msg.indexOf('###');

						 if (valid_pos == -1)
						 {
							 // Valid data not found in the response
							 msg = akeeba.System.sanitizeErrorMessage(msg);
							 msg = 'Invalid AJAX data: ' + msg;

							 if (errorCallback == null)
							 {
								 if (akeeba.System.params.errorCallback != null)
								 {
									 akeeba.System.params.errorCallback(msg);
								 }
							 }
							 else
							 {
								 errorCallback(msg);
							 }

							 return;
						 }
						 else if (valid_pos != 0)
						 {
							 // Data is prefixed with junk
							 junk    = msg.substr(0, valid_pos);
							 message = msg.substr(valid_pos);
						 }
						 else
						 {
							 message = msg;
						 }

						 message = message.substr(3); // Remove triple hash in the beginning

						 // Get of rid of junk after the data
						 valid_pos = message.lastIndexOf('###');
						 message   = message.substr(0, valid_pos); // Remove triple hash in the end

						 try
						 {
							 var data = JSON.parse(message);
						 }
						 catch (err)
						 {
							 message = akeeba.System.sanitizeErrorMessage(message);
							 msg     = err.message + "\n<br/>\n<pre>\n" + message + "\n</pre>";

							 if (errorCallback == null)
							 {
								 if (akeeba.System.params.errorCallback != null)
								 {
									 akeeba.System.params.errorCallback(msg);
								 }
							 }
							 else
							 {
								 errorCallback(msg);
							 }

							 return;
						 }

						 // Call the callback function
						 successCallback(data);
					 },
			error:   function (Request, textStatus, errorThrown)
					 {
						 var text    = Request.responseText ? Request.responseText : '';
						 var message = '<strong>AJAX Loading Error</strong><br/>HTTP Status: ' + Request.status +
							 ' (' + Request.statusText + ')<br/>';

						 message = message + 'Internal status: ' + textStatus + '<br/>';
						 message = message + 'XHR ReadyState: ' + Request.readyState + '<br/>';
						 message = message + 'Raw server response:<br/>' + akeeba.System.sanitizeErrorMessage(text);

						 if (errorCallback == null)
						 {
							 if (akeeba.System.params.errorCallback != null)
							 {
								 akeeba.System.params.errorCallback(message);
							 }
						 }
						 else
						 {
							 errorCallback(message);
						 }
					 }
		};

	if (useCaching)
	{
		akeeba.Ajax.enqueue(structure);
	}
	else
	{
		akeeba.Ajax.ajax(structure);
	}
};

/**
 * Sanitize a message before displaying it in an error dialog. Some servers return an HTML page with DOM modifying
 * JavaScript when they block the backup script for any reason (usually with a 5xx HTTP error code). Displaying the
 * raw response in the error dialog has the side-effect of killing our backup resumption JavaScript or even completely
 * destroy the page, making backup restart impossible.
 *
 * @param {string} msg The message to sanitize
 *
 * @returns {string}
 */
akeeba.System.sanitizeErrorMessage = function (msg)
{
	if (msg.indexOf("<script") > -1)
	{
		msg = "(HTML containing script tags)";
	}

	return msg;
};

/**
 * Requests permission for displaying desktop notifications
 */
akeeba.System.notification.askPermission = function ()
{
	if (!akeeba.System.notification.hasDesktopNotification)
	{
		return;
	}

	if (window.Notification == undefined)
	{
		return;
	}

	if (window.Notification.permission == 'default')
	{
		window.Notification.requestPermission();
	}
};

/**
 * Displays a desktop notification with the given title and body content. Chrome and Firefox will display our custom
 * icon in the notification. Safari will not display our custom icon but will place the notification in the iOS /
 * Mac OS X notification centre. Firefox displays the icon to the right of the notification and its own icon on the
 * left hand side. It also plays a sound when the notification is displayed. Chrome plays no sound and displays only
 * our icon on the left hand side.
 *
 * The notifications have a default timeout of 5 seconds. Clicking on them, or waiting for 5 seconds, will dismiss
 * them. You can change the timeout using the timeout parameter. Set to 0 for a permanent notification.
 *
 * @param  title        string  The title of the notification
 * @param  bodyContent  string  The body of the notification (optional)
 */
akeeba.System.notification.notify = function (title, bodyContent, timeout)
{
	if (window.Notification == undefined)
	{
		return;
	}

	if (window.Notification.permission != 'granted')
	{
		return;
	}

	if (timeout == undefined)
	{
		timeout = 5000;
	}

	if (bodyContent == undefined)
	{
		body = '';
	}

	var n = new window.Notification(title, {
		'body': bodyContent,
		'icon': akeeba.System.notification.iconURL
	});

	if (timeout > 0)
	{
		setTimeout(function (notification)
		{
			return function ()
			{
				notification.close();
			}
		}(n), timeout);
	}
};

/**
 * Get and set data to elements. Use:
 * akeeba.System.data.set(element, property, value)
 * akeeba.System.data.get(element, property, defaultValue)
 *
 * On modern browsers (minimum IE 11, Chrome 8, FF 6, Opera 11, Safari 6) this will use the data-* attributes of the
 * elements where possible. On old browsers it will use an internal cache and manually apply data-* attributes.
 */
akeeba.System.data = (function ()
{
	var lastId = 0,
		store  = {};

	return {
		set: function (element, property, value)
			 {
				 // IE 11, modern browsers
				 if (element.dataset)
				 {
					 element.dataset[property] = value;

					 if (value == null)
					 {
						 delete element.dataset[property];
					 }

					 return;
				 }

				 // IE 8 to 10, old browsers
				 var id;

				 if (element.myCustomDataTag === undefined)
				 {
					 id                      = lastId++;
					 element.myCustomDataTag = id;
				 }

				 if (typeof(store[id]) == 'undefined')
				 {
					 store[id] = {};
				 }

				 // Store the value in the internal cache...
				 store[id][property] = value;

				 // ...and the DOM

				 // Convert the property to dash-format
				 var dataAttributeName = 'data-' + property.split(/(?=[A-Z])/).join('-').toLowerCase();

				 if (element.setAttribute)
				 {
					 element.setAttribute(dataAttributeName, value);
				 }

				 if (value == null)
				 {
					 // IE 8 throws an exception on "delete"
					 try
					 {
						 delete store[id][property];
						 element.removeAttribute(dataAttributeName);
					 }
					 catch (e)
					 {
						 store[id][property] = null;
					 }
				 }
			 },

		get: function (element, property, defaultValue)
			 {
				 // IE 11, modern browsers
				 if (element.dataset)
				 {
					 if (typeof(element.dataset[property]) == 'undefined')
					 {
						 element.dataset[property] = defaultValue;
					 }

					 return element.dataset[property];
				 }
				 // IE 8 to 10, old browsers

				 if (typeof(defaultValue) == 'undefined')
				 {
					 defaultValue = null;
				 }

				 // Make sure we have an internal storage
				 if (typeof(store[element.myCustomDataTag]) == 'undefined')
				 {
					 store[element.myCustomDataTag] = {};
				 }

				 // Convert the property to dash-format
				 var dataAttributeName = 'data-' + property.split(/(?=[A-Z])/).join('-').toLowerCase();

				 // data-* attributes have precedence
				 if (typeof(element[dataAttributeName]) !== 'undefined')
				 {
					 store[element.myCustomDataTag][property] = element[dataAttributeName];
				 }

				 // No data-* attribute and no stored value? Use the default.
				 if (typeof(store[element.myCustomDataTag][property]) == 'undefined')
				 {
					 this.set(element, property, defaultValue);
				 }

				 // Return the value of the data
				 return store[element.myCustomDataTag][property];
			 }
	};
}());

/**
 * Adds an event listener to an element
 *
 * @param element
 * @param eventName
 * @param listener
 */
akeeba.System.addEventListener = function (element, eventName, listener)
{
	// Allow the passing of an element ID string instead of the DOM elem
	if (typeof element === "string")
	{
		element = document.getElementById(element);
	}

	if (element == null)
	{
		return;
	}

	if (typeof element != 'object')
	{
		return;
	}

	if (!(element instanceof Element))
	{
		return;
	}

	// Handles the listener in a way that returning boolean false will cancel the event propagation
	function listenHandler(e)
	{
		var ret = listener.apply(this, arguments);

		if (ret === false)
		{
			if (e.stopPropagation())
			{
				e.stopPropagation();
			}

			if (e.preventDefault)
			{
				e.preventDefault();
			}
			else
			{
				e.returnValue = false;
			}
		}

		return (ret);
	}

	// Equivalent of listenHandler for IE8
	function attachHandler()
	{
		// Normalize the target of the event –– PhpStorm detects this as an error
		// window.event.target = window.event.srcElement;

		var ret = listener.call(element, window.event);

		if (ret === false)
		{
			window.event.returnValue  = false;
			window.event.cancelBubble = true;
		}

		return (ret);
	}

	if (element.addEventListener)
	{
		element.addEventListener(eventName, listenHandler, false);

		return;
	}

	element.attachEvent("on" + eventName, attachHandler);
};

/**
 * Remove an event listener from an element
 *
 * @param element
 * @param eventName
 * @param listener
 */
akeeba.System.removeEventListener = function (element, eventName, listener)
{
	// Allow the passing of an element ID string instead of the DOM elem
	if (typeof element === "string")
	{
		element = document.getElementById(element);
	}

	if (element == null)
	{
		return;
	}

	if (typeof element != 'object')
	{
		return;
	}

	if (!(element instanceof Element))
	{
		return;
	}

	if (element.removeEventListener)
	{
		element.removeEventListener(eventName, listener);

		return;
	}

	element.detachEvent("on" + eventName, listener);
};

akeeba.System.triggerEvent = function (element, eventName)
{
	if (typeof element == 'undefined')
	{
		return;
	}

	if (element === null)
	{
		return;
	}

	// Allow the passing of an element ID string instead of the DOM elem
	if (typeof element === "string")
	{
		element = document.getElementById(element);
	}

	if (typeof element != 'object')
	{
		return;
	}

	if (!(element instanceof Element))
	{
		return;
	}

    // Use jQuery and be done with it!
    if (typeof window.jQuery === 'function')
    {
        window.jQuery(element).trigger(eventName);

        return;
    }

    // Internet Explorer way
	if (document.fireEvent && (typeof window.Event == 'undefined'))
	{
		element.fireEvent('on' + eventName);

		return;
	}

    // This works on Chrome and Edge but not on Firefox. Ugh.
    var event = null;

    event = document.createEvent("Event");
    event.initEvent(eventName, true, true);
    element.dispatchEvent(event);
};

// document.ready equivalent from https://github.com/jfriend00/docReady/blob/master/docready.js
(function (funcName, baseObj)
{
	funcName = funcName || "documentReady";
	baseObj  = baseObj || akeeba.System;

	var readyList                   = [];
	var readyFired                  = false;
	var readyEventHandlersInstalled = false;

	// Call this when the document is ready. This function protects itself against being called more than once.
	function ready()
	{
		if (!readyFired)
		{
			// This must be set to true before we start calling callbacks
			readyFired = true;

			for (var i = 0; i < readyList.length; i++)
			{
				/**
				 * If a callback here happens to add new ready handlers, this function will see that it already
				 * fired and will schedule the callback to run right after this event loop finishes so all handlers
				 * will still execute in order and no new ones will be added to the readyList while we are
				 * processing the list.
				 */
				readyList[i].fn.call(window, readyList[i].ctx);
			}

			// Allow any closures held by these functions to free
			readyList = [];
		}
	}

	/**
	 * Solely for the benefit of Internet Explorer
	 */
	function readyStateChange()
	{
		if (document.readyState === "complete")
		{
			ready();
		}
	}

	/**
	 * This is the one public interface:
	 *
	 * akeeba.System.documentReady(fn, context);
	 *
	 * @param   callback   The callback function to execute when the document is ready.
	 * @param   context    Optional. If present, it will be passed as an argument to the callback.
	 */
	//
	//
	//
	baseObj[funcName] = function (callback, context)
	{
		// If ready() has already fired, then just schedule the callback to fire asynchronously
		if (readyFired)
		{
			setTimeout(function ()
			{
				callback(context);
			}, 1);

			return;
		}

		// Add the function and context to the queue
		readyList.push({fn: callback, ctx: context});

		/**
		 * If the document is already ready, schedule the ready() function to run immediately.
		 *
		 * Note: IE is only safe when the readyState is "complete", other browsers are safe when the readyState is
		 * "interactive"
		 */
		if (document.readyState === "complete" || (!document.attachEvent && document.readyState === "interactive"))
		{
			setTimeout(ready, 1);

			return;
		}

		// If the handlers are already installed just quit
		if (readyEventHandlersInstalled)
		{
			return;
		}

		// We don't have event handlers installed, install them
		readyEventHandlersInstalled = true;

		// -- We have an addEventListener method in the document, this is a modern browser.

		if (document.addEventListener)
		{
			// Prefer using the DOMContentLoaded event
			document.addEventListener("DOMContentLoaded", ready, false);

			// Our backup is the window's "load" event
			window.addEventListener("load", ready, false);

			return;
		}

		// -- Most likely we're stuck with an ancient version of IE

		// Our primary method of activation is the onreadystatechange event
		document.attachEvent("onreadystatechange", readyStateChange);

		// Our backup is the windows's "load" event
		window.attachEvent("onload", ready);
	}
})("documentReady", akeeba.System);

akeeba.System.addClass = function (element, newClasses)
{
	if (!element || !element.className)
	{
		return;
	}

	var currentClasses = element.className.split(' ');

	if ((typeof newClasses) == 'string')
	{
		newClasses = newClasses.split(' ');
	}

	currentClasses = array_merge(currentClasses, newClasses);

	element.className = '';

	for (property in currentClasses)
	{
		if (currentClasses.hasOwnProperty(property))
		{
			element.className += currentClasses[property] + ' ';
		}
	}

	if (element.className.trim)
	{
		element.className = element.className.trim();
	}
};

akeeba.System.removeClass = function (element, oldClasses)
{
	if (!element || !element.className)
	{
		return;
	}

	var currentClasses = element.className.split(' ');

	if ((typeof oldClasses) == 'string')
	{
		oldClasses = oldClasses.split(' ');
	}

	currentClasses = array_diff(currentClasses, oldClasses);

	element.className = '';

	for (property in currentClasses)
	{
		if (currentClasses.hasOwnProperty(property))
		{
			element.className += currentClasses[property] + ' ';
		}
	}

	if (element.className.trim)
	{
		element.className = element.className.trim();
	}
};

akeeba.System.hasClass = function (element, aClass)
{
	if (!element || !element.className)
	{
		return;
	}

	var currentClasses = element.className.split(' ');

	for (i = 0; i < currentClasses.length; i++)
	{
		if (currentClasses[i] == aClass)
		{
			return true;
		}
	}

	return false;
};

akeeba.System.toggleClass = function(element, aClass)
{
	if (akeeba.System.hasClass(element, aClass))
	{
		akeeba.System.removeClass(element, aClass);

		return;
	}

	akeeba.System.addClass(element, aClass);
};
