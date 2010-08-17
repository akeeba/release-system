// =============================================================================
(function(jQuery){ // <<< DO NOT REMOVE - USED FOR JQUERY COMPATIBILITY <<<
// =============================================================================

/*
 * jQuery Tooltip plugin 1.3
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-tooltip/
 * http://docs.jquery.com/Plugins/Tooltip
 *
 * Copyright (c) 2006 - 2008 Jörn Zaefferer
 *
 * $Id: gui-helpers.js 183 2010-07-13 22:28:10Z nikosdion $
 * 
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
(function($){var helper={},current,title,tID,IE=$.browser.msie&&/MSIE\s(5\.5|6\.)/.test(navigator.userAgent),track=false;$.tooltip={blocked:false,defaults:{delay:200,fade:false,showURL:true,extraClass:"",top:15,left:15,id:"tooltip"},block:function(){$.tooltip.blocked=!$.tooltip.blocked;}};$.fn.extend({tooltip:function(settings){settings=$.extend({},$.tooltip.defaults,settings);createHelper(settings);return this.each(function(){$.data(this,"tooltip",settings);this.tOpacity=helper.parent.css("opacity");this.tooltipText=this.title;$(this).removeAttr("title");this.alt="";}).mouseover(save).mouseout(hide).click(hide);},fixPNG:IE?function(){return this.each(function(){var image=$(this).css('backgroundImage');if(image.match(/^url\(["']?(.*\.png)["']?\)$/i)){image=RegExp.$1;$(this).css({'backgroundImage':'none','filter':"progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true, sizingMethod=crop, src='"+image+"')"}).each(function(){var position=$(this).css('position');if(position!='absolute'&&position!='relative')$(this).css('position','relative');});}});}:function(){return this;},unfixPNG:IE?function(){return this.each(function(){$(this).css({'filter':'',backgroundImage:''});});}:function(){return this;},hideWhenEmpty:function(){return this.each(function(){$(this)[$(this).html()?"show":"hide"]();});},url:function(){return this.attr('href')||this.attr('src');}});function createHelper(settings){if(helper.parent)return;helper.parent=$('<div id="'+settings.id+'"><h3></h3><div class="body"></div><div class="url"></div></div>').appendTo(document.body).hide();if($.fn.bgiframe)helper.parent.bgiframe();helper.title=$('h3',helper.parent);helper.body=$('div.body',helper.parent);helper.url=$('div.url',helper.parent);}function settings(element){return $.data(element,"tooltip");}function handle(event){if(settings(this).delay)tID=setTimeout(show,settings(this).delay);else
show();track=!!settings(this).track;$(document.body).bind('mousemove',update);update(event);}function save(){if($.tooltip.blocked||this==current||(!this.tooltipText&&!settings(this).bodyHandler))return;current=this;title=this.tooltipText;if(settings(this).bodyHandler){helper.title.hide();var bodyContent=settings(this).bodyHandler.call(this);if(bodyContent.nodeType||bodyContent.jquery){helper.body.empty().append(bodyContent)}else{helper.body.html(bodyContent);}helper.body.show();}else if(settings(this).showBody){var parts=title.split(settings(this).showBody);helper.title.html(parts.shift()).show();helper.body.empty();for(var i=0,part;(part=parts[i]);i++){if(i>0)helper.body.append("<br/>");helper.body.append(part);}helper.body.hideWhenEmpty();}else{helper.title.html(title).show();helper.body.hide();}if(settings(this).showURL&&$(this).url())helper.url.html($(this).url().replace('http://','')).show();else
helper.url.hide();helper.parent.addClass(settings(this).extraClass);if(settings(this).fixPNG)helper.parent.fixPNG();handle.apply(this,arguments);}function show(){tID=null;if((!IE||!$.fn.bgiframe)&&settings(current).fade){if(helper.parent.is(":animated"))helper.parent.stop().show().fadeTo(settings(current).fade,current.tOpacity);else
helper.parent.is(':visible')?helper.parent.fadeTo(settings(current).fade,current.tOpacity):helper.parent.fadeIn(settings(current).fade);}else{helper.parent.show();}update();}function update(event){if($.tooltip.blocked)return;if(event&&event.target.tagName=="OPTION"){return;}if(!track&&helper.parent.is(":visible")){$(document.body).unbind('mousemove',update)}if(current==null){$(document.body).unbind('mousemove',update);return;}helper.parent.removeClass("viewport-right").removeClass("viewport-bottom");var left=helper.parent[0].offsetLeft;var top=helper.parent[0].offsetTop;if(event){left=event.pageX+settings(current).left;top=event.pageY+settings(current).top;var right='auto';if(settings(current).positionLeft){right=$(window).width()-left;left='auto';}helper.parent.css({left:left,right:right,top:top});}var v=viewport(),h=helper.parent[0];if(v.x+v.cx<h.offsetLeft+h.offsetWidth){left-=h.offsetWidth+20+settings(current).left;helper.parent.css({left:left+'px'}).addClass("viewport-right");}if(v.y+v.cy<h.offsetTop+h.offsetHeight){top-=h.offsetHeight+20+settings(current).top;helper.parent.css({top:top+'px'}).addClass("viewport-bottom");}}function viewport(){return{x:$(window).scrollLeft(),y:$(window).scrollTop(),cx:$(window).width(),cy:$(window).height()};}function hide(event){if($.tooltip.blocked)return;if(tID)clearTimeout(tID);current=null;var tsettings=settings(this);function complete(){helper.parent.removeClass(tsettings.extraClass).hide().css("opacity","");}if((!IE||!$.fn.bgiframe)&&tsettings.fade){if(helper.parent.is(':animated'))helper.parent.stop().fadeTo(tsettings.fade,0,complete);else
helper.parent.stop().fadeOut(tsettings.fade,complete);}else
complete();if(settings(this).fixPNG)helper.parent.unfixPNG();}})(jQuery);

 /*
  * jQuery custom checkboxes
  * 
  * Copyright (c) 2008 Khavilo Dmitry (http://widowmaker.kiev.ua/checkbox/)
  * Licensed under the MIT License:
  * http://www.opensource.org/licenses/mit-license.php
  *
  * @version 1.3.0 Beta 1
  * @author Khavilo Dmitry
  * @mailto wm.morgun@gmail.com
 **/
(function($){var i=function(e){if(!e)var e=window.event;e.cancelBubble=true;if(e.stopPropagation)e.stopPropagation()};$.fn.checkbox=function(f){try{document.execCommand('BackgroundImageCache',false,true)}catch(e){}var g={cls:'jquery-checkbox',empty:'empty.png'};g=$.extend(g,f||{});var h=function(a){var b=a.checked;var c=a.disabled;var d=$(a);if(a.stateInterval)clearInterval(a.stateInterval);a.stateInterval=setInterval(function(){if(a.disabled!=c)d.trigger((c=!!a.disabled)?'disable':'enable');if(a.checked!=b)d.trigger((b=!!a.checked)?'check':'uncheck')},10);return d};return this.each(function(){var a=this;var b=h(a);if(a.wrapper)a.wrapper.remove();a.wrapper=$('<span class="'+g.cls+'"><span class="mark"><img src="'+g.empty+'" /></span></span>');a.wrapperInner=a.wrapper.children('span:eq(0)');a.wrapper.hover(function(e){a.wrapperInner.addClass(g.cls+'-hover');i(e)},function(e){a.wrapperInner.removeClass(g.cls+'-hover');i(e)});b.css({position:'absolute',zIndex:-1,visibility:'hidden'}).after(a.wrapper);var c=false;if(b.attr('id')){c=$('label[for='+b.attr('id')+']');if(!c.length)c=false}if(!c){c=b.closest?b.closest('label'):b.parents('label:eq(0)');if(!c.length)c=false}if(c){c.hover(function(e){a.wrapper.trigger('mouseover',[e])},function(e){a.wrapper.trigger('mouseout',[e])});c.click(function(e){b.trigger('click',[e]);i(e);return false})}a.wrapper.click(function(e){b.trigger('click',[e]);i(e);return false});b.click(function(e){i(e)});b.bind('disable',function(){a.wrapperInner.addClass(g.cls+'-disabled')}).bind('enable',function(){a.wrapperInner.removeClass(g.cls+'-disabled')});b.bind('check',function(){a.wrapper.addClass(g.cls+'-checked')}).bind('uncheck',function(){a.wrapper.removeClass(g.cls+'-checked')});$('img',a.wrapper).bind('dragstart',function(){return false}).bind('mousedown',function(){return false});if(window.getSelection)a.wrapper.css('MozUserSelect','none');if(a.checked)a.wrapper.addClass(g.cls+'-checked');if(a.disabled)a.wrapperInner.addClass(g.cls+'-disabled')})}})(jQuery);

/*
 * jQuery Color Animations
 * Copyright 2007 John Resig
 * Released under the MIT and GPL licenses.
 */
(function(jQuery){jQuery.each(['backgroundColor','borderBottomColor','borderLeftColor','borderRightColor','borderTopColor','color','outlineColor'],function(i,attr){jQuery.fx.step[attr]=function(fx){if(fx.state==0){fx.start=getColor(fx.elem,attr);fx.end=getRGB(fx.end);}
fx.elem.style[attr]="rgb("+[Math.max(Math.min(parseInt((fx.pos*(fx.end[0]-fx.start[0]))+fx.start[0]),255),0),Math.max(Math.min(parseInt((fx.pos*(fx.end[1]-fx.start[1]))+fx.start[1]),255),0),Math.max(Math.min(parseInt((fx.pos*(fx.end[2]-fx.start[2]))+fx.start[2]),255),0)].join(",")+")";}});function getRGB(color){var result;if(color&&color.constructor==Array&&color.length==3)
return color;if(result=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))
return[parseInt(result[1]),parseInt(result[2]),parseInt(result[3])];if(result=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))
return[parseFloat(result[1])*2.55,parseFloat(result[2])*2.55,parseFloat(result[3])*2.55];if(result=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))
return[parseInt(result[1],16),parseInt(result[2],16),parseInt(result[3],16)];if(result=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))
return[parseInt(result[1]+result[1],16),parseInt(result[2]+result[2],16),parseInt(result[3]+result[3],16)];return colors[jQuery.trim(color).toLowerCase()];}
function getColor(elem,attr){var color;do{color=jQuery.curCSS(elem,attr);if(color!=''&&color!='transparent'||jQuery.nodeName(elem,"body"))
break;attr="backgroundColor";}while(elem=elem.parentNode);return getRGB(color);};var colors={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0]};})(jQuery);

/*
 * jQuery.timers - Timer abstractions for jQuery
 * Written by Blair Mitchelmore (blair DOT mitchelmore AT gmail DOT com)
 * Licensed under the WTFPL (http://sam.zoy.org/wtfpl/).
 * Date: 2009/10/16
 *
 * @author Blair Mitchelmore
 * @version 1.2
 *
 **/
jQuery.fn.extend({everyTime:function(interval,label,fn,times){return this.each(function(){jQuery.timer.add(this,interval,label,fn,times);});},oneTime:function(interval,label,fn){return this.each(function(){jQuery.timer.add(this,interval,label,fn,1);});},stopTime:function(label,fn){return this.each(function(){jQuery.timer.remove(this,label,fn);});}});jQuery.extend({timer:{global:[],guid:1,dataKey:"jQuery.timer",regex:/^([0-9]+(?:\.[0-9]*)?)\s*(.*s)?$/,powers:{'ms':1,'cs':10,'ds':100,'s':1000,'das':10000,'hs':100000,'ks':1000000},timeParse:function(value){if(value==undefined||value==null)
	return null;var result=this.regex.exec(jQuery.trim(value.toString()));if(result[2]){var num=parseFloat(result[1]);var mult=this.powers[result[2]]||1;return num*mult;}else{return value;}},add:function(element,interval,label,fn,times){var counter=0;if(jQuery.isFunction(label)){if(!times)
	times=fn;fn=label;label=interval;}
	interval=jQuery.timer.timeParse(interval);if(typeof interval!='number'||isNaN(interval)||interval<0)
	return;if(typeof times!='number'||isNaN(times)||times<0)
	times=0;times=times||0;var timers=jQuery.data(element,this.dataKey)||jQuery.data(element,this.dataKey,{});if(!timers[label])
	timers[label]={};fn.timerID=fn.timerID||this.guid++;var handler=function(){if((++counter>times&&times!==0)||fn.call(element,counter)===false)
	jQuery.timer.remove(element,label,fn);};handler.timerID=fn.timerID;if(!timers[label][fn.timerID])
	timers[label][fn.timerID]=window.setInterval(handler,interval);this.global.push(element);},remove:function(element,label,fn){var timers=jQuery.data(element,this.dataKey),ret;if(timers){if(!label){for(label in timers)
	this.remove(element,label,fn);}else if(timers[label]){if(fn){if(fn.timerID){window.clearInterval(timers[label][fn.timerID]);delete timers[label][fn.timerID];}}else{for(var fn in timers[label]){window.clearInterval(timers[label][fn]);delete timers[label][fn];}}
	for(ret in timers[label])break;if(!ret){ret=null;delete timers[label];}}
	for(ret in timers)break;if(!ret)
	jQuery.removeData(element,this.dataKey);}}}});jQuery(window).bind("unload",function(){jQuery.each(jQuery.timer.global,function(index,item){jQuery.timer.remove(item);});});

/*
 *	http://www.JSON.org/json2.js
 *  2009-09-29
 *  Public Domain.
 *  NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.
 *  See http://www.JSON.org/js.html 
 */
if(!this.JSON){this.JSON={};}
(function(){function f(n){return n<10?'0'+n:n;}
if(typeof Date.prototype.toJSON!=='function'){Date.prototype.toJSON=function(key){return isFinite(this.valueOf())?this.getUTCFullYear()+'-'+
f(this.getUTCMonth()+1)+'-'+
f(this.getUTCDate())+'T'+
f(this.getUTCHours())+':'+
f(this.getUTCMinutes())+':'+
f(this.getUTCSeconds())+'Z':null;};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf();};}
var cx=/[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,escapable=/[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,gap,indent,meta={'\b':'\\b','\t':'\\t','\n':'\\n','\f':'\\f','\r':'\\r','"':'\\"','\\':'\\\\'},rep;function quote(string){escapable.lastIndex=0;return escapable.test(string)?'"'+string.replace(escapable,function(a){var c=meta[a];return typeof c==='string'?c:'\\u'+('0000'+a.charCodeAt(0).toString(16)).slice(-4);})+'"':'"'+string+'"';}
function str(key,holder){var i,k,v,length,mind=gap,partial,value=holder[key];if(value&&typeof value==='object'&&typeof value.toJSON==='function'){value=value.toJSON(key);}
if(typeof rep==='function'){value=rep.call(holder,key,value);}
switch(typeof value){case'string':return quote(value);case'number':return isFinite(value)?String(value):'null';case'boolean':case'null':return String(value);case'object':if(!value){return'null';}
gap+=indent;partial=[];if(Object.prototype.toString.apply(value)==='[object Array]'){length=value.length;for(i=0;i<length;i+=1){partial[i]=str(i,value)||'null';}
v=partial.length===0?'[]':gap?'[\n'+gap+
partial.join(',\n'+gap)+'\n'+
mind+']':'['+partial.join(',')+']';gap=mind;return v;}
if(rep&&typeof rep==='object'){length=rep.length;for(i=0;i<length;i+=1){k=rep[i];if(typeof k==='string'){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}else{for(k in value){if(Object.hasOwnProperty.call(value,k)){v=str(k,value);if(v){partial.push(quote(k)+(gap?': ':':')+v);}}}}
v=partial.length===0?'{}':gap?'{\n'+gap+partial.join(',\n'+gap)+'\n'+
mind+'}':'{'+partial.join(',')+'}';gap=mind;return v;}}
if(typeof JSON.stringify!=='function'){JSON.stringify=function(value,replacer,space){var i;gap='';indent='';if(typeof space==='number'){for(i=0;i<space;i+=1){indent+=' ';}}else if(typeof space==='string'){indent=space;}
rep=replacer;if(replacer&&typeof replacer!=='function'&&(typeof replacer!=='object'||typeof replacer.length!=='number')){throw new Error('JSON.stringify');}
return str('',{'':value});};}
if(typeof JSON.parse!=='function'){JSON.parse=function(text,reviver){var j;function walk(holder,key){var k,v,value=holder[key];if(value&&typeof value==='object'){for(k in value){if(Object.hasOwnProperty.call(value,k)){v=walk(value,k);if(v!==undefined){value[k]=v;}else{delete value[k];}}}}
return reviver.call(holder,key,value);}
cx.lastIndex=0;if(cx.test(text)){text=text.replace(cx,function(a){return'\\u'+
('0000'+a.charCodeAt(0).toString(16)).slice(-4);});}
if(/^[\],:{}\s]*$/.test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,'@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,']').replace(/(?:^|:|,)(?:\s*\[)+/g,''))){j=eval('('+text+')');return typeof reviver==='function'?walk({'':j},''):j;}
throw new SyntaxError('JSON.parse');};}}());

/**
 * @author alexander.farkas
 * @version 2.5.4
 * project site: http://plugins.jquery.com/project/AjaxManager
 */
(function($){$.support.ajax=!!(window.XMLHttpRequest);if(window.ActiveXObject){try{new ActiveXObject("Microsoft.XMLHTTP");$.support.ajax=true;}catch(e){if(window.XMLHttpRequest){$.ajaxSetup({xhr:function(){return new XMLHttpRequest();}});}}}
$.manageAjax=(function(){var cache={},queues={},presets={},activeRequest={},allRequests={},triggerEndCache={},defaults={queue:true,maxRequests:1,abortOld:false,preventDoubbleRequests:true,cacheResponse:false,complete:function(){},error:function(ahr,status){var opts=this;if(status&&status.indexOf('error')!=-1){setTimeout(function(){var errStr=status+': ';if(ahr.status){errStr+='status: '+ahr.status+' | ';}
errStr+='URL: '+opts.url;throw new Error(errStr);},1);}},success:function(){},abort:function(){}};function create(name,settings){var publicMethods={};presets[name]=presets[name]||{};$.extend(true,presets[name],$.ajaxSettings,defaults,settings);if(!allRequests[name]){allRequests[name]={};activeRequest[name]={};activeRequest[name].queue=[];queues[name]=[];triggerEndCache[name]=[];}
$.each($.manageAjax,function(fnName,fn){if($.isFunction(fn)&&fnName.indexOf('_')!==0){publicMethods[fnName]=function(param,param2){if(param2&&typeof param==='string'){param=param2;}
fn(name,param);};}});return publicMethods;}
function complete(opts,args){if(args[1]=='success'||args[1]=='notmodified'){opts.success.apply(opts,[args[0].successData,args[1]]);if(opts.global){$.event.trigger("ajaxSuccess",args);}}
if(args[1]==='abort'){opts.abort.apply(opts,args);if(opts.global){$.active--;$.event.trigger("ajaxAbort",args);}}
opts.complete.apply(opts,args);if(opts.global){$.event.trigger("ajaxComplete",args);}
if(opts.global&&!$.active){$.event.trigger("ajaxStop");}}
function proxy(oldFn,fn){return function(xhr,s,e){fn.call(this,xhr,s,e);oldFn.call(this,xhr,s,e);xhr=null;e=null;};}
function callQueueFn(name){var q=queues[name];if(q&&q.length){var fn=q.shift();if(fn){fn();}}}
function add(name,opts){if(!presets[name]){create(name,opts);}
opts=$.extend({},presets[name],opts);var allR=allRequests[name],activeR=activeRequest[name],queue=queues[name];var id=opts.type+'_'+opts.url.replace(/\./g,'_'),triggerStart=true,oldComplete=opts.complete,ajaxFn=function(){activeR.queue.push(id);activeR[id]={xhr:false,ajaxManagerOpts:opts};activeR[id].xhr=$.ajax(opts);return id;};if(opts.data){id+=(typeof opts.data=='string')?opts.data:$.param(opts.data);}
if(opts.preventDoubbleRequests&&allRequests[name][id]){return false;}
allR[id]=true;opts.complete=function(xhr,s,e){var triggerEnd=true;if(opts.abortOld){$.each(activeR.queue,function(i,activeID){if(activeID==id){return false;}
abort(name,activeID);return activeID;});}
oldComplete.call(this,xhr,s,e);if(activeRequest[name][id]){if(activeRequest[name][id]&&activeRequest[name][id].xhr){activeRequest[name][id].xhr=null;}
activeRequest[name][id]=null;}
triggerEndCache[name].push({xhr:xhr,status:s});xhr=null;activeRequest[name].queue=$.grep(activeRequest[name].queue,function(qid){return(qid!==id);});allR[id]=false;e=null;delete activeRequest[name][id];$.each(activeR,function(id,queueRunning){if(id!=='queue'||queueRunning.length){triggerEnd=false;return false;}});if(triggerEnd){$.event.trigger(name+'End',[triggerEndCache[name]]);$.each(triggerEndCache[name],function(i,cached){cached.xhr=null;});triggerEndCache[name]=[];}};if(cache[id]){ajaxFn=function(){activeR.queue.push(id);complete(opts,cache[id]);return id;};}else if(opts.cacheResponse){opts.complete=proxy(opts.complete,function(xhr,s){if(s!=="success"&&s!=="notmodified"){return false;}
cache[id][0].responseXML=xhr.responseXML;cache[id][0].responseText=xhr.responseText;cache[id][1]=s;xhr=null;return id;});opts.success=proxy(opts.success,function(data,s){cache[id]=[{successData:data,ajaxManagerOpts:opts},s];data=null;});}
ajaxFn.ajaxID=id;$.each(activeR,function(id,queueRunning){if(id!=='queue'||queueRunning.length){triggerStart=false;return false;}});if(triggerStart){$.event.trigger(name+'Start');}
if(opts.queue){opts.complete=proxy(opts.complete,function(){callQueueFn(name);});if(opts.queue==='clear'){queue=clear(name);}
queue.push(ajaxFn);if(activeR.queue.length<opts.maxRequests){callQueueFn(name);}
return id;}
return ajaxFn();}
function clear(name,shouldAbort){$.each(queues[name],function(i,fn){allRequests[name][fn.ajaxID]=false;});queues[name]=[];if(shouldAbort){abort(name);}
return queues[name];}
function getXHR(name,id){var ar=activeRequest[name];if(!ar||!allRequests[name][id]){return false;}
if(ar[id]){return ar[id].xhr;}
var queue=queues[name],xhrFn;$.each(queue,function(i,fn){if(fn.ajaxID==id){xhrFn=[fn,i];return false;}
return xhrFn;});return xhrFn;}
function abort(name,id){var ar=activeRequest[name];if(!ar){return false;}
function abortID(qid){if(qid!=='queue'&&ar[qid]&&ar[qid].xhr){try{ar[qid].xhr.abort();}catch(e){}
complete(ar[qid].ajaxManagerOpts,[ar[qid].xhr,'abort']);}
return null;}
if(id){return abortID(id);}
return $.each(ar,abortID);}
function unload(){$.each(presets,function(name){clear(name,true);});cache={};}
return{defaults:defaults,add:add,create:create,cache:cache,abort:abort,clear:clear,getXHR:getXHR,_activeRequest:activeRequest,_complete:complete,_allRequests:allRequests,_unload:unload};})();$(window).unload($.manageAjax._unload);})(jQuery);

/**
 * Pulse plugin for jQuery
 * ---
 * @author James Padolsey (http://james.padolsey.com)
 * @version 0.1
 * @updated 16-DEC-09
 * ---
 * Note: In order to animate color properties, you need
 * the color plugin from here: http://plugins.jquery.com/project/color
 * ---
 * @info http://james.padolsey.com/javascript/simple-pulse-plugin-for-jquery/
 */
jQuery.fn.pulse=function(prop,speed,times,easing,callback){if(isNaN(times)){callback=easing;easing=times;times=1}var optall=jQuery.speed(speed,easing,callback),queue=optall.queue!==false,largest=0;for(var p in prop){largest=Math.max(prop[p].length,largest)}optall.times=optall.times||times;return this[queue?'queue':'each'](function(){var counts={},opt=jQuery.extend({},optall),self=jQuery(this);pulse();function pulse(){var propsSingle={},doAnimate=false;for(var p in prop){counts[p]=counts[p]||{runs:0,cur:-1};if(counts[p].cur<prop[p].length-1){++counts[p].cur}else{counts[p].cur=0;++counts[p].runs}if(prop[p].length===largest){doAnimate=opt.times>counts[p].runs}propsSingle[p]=prop[p][counts[p].cur]}opt.complete=pulse;opt.queue=false;if(doAnimate){self.animate(propsSingle,opt)}else{optall.complete.call(self[0])}}})};

/*
 * jQuery Color Animations
 * Copyright 2007 John Resig
 * Released under the MIT and GPL licenses.
 */
(function(jQuery){jQuery.each(['backgroundColor','borderBottomColor','borderLeftColor','borderRightColor','borderTopColor','color','outlineColor'],function(i,attr){jQuery.fx.step[attr]=function(fx){if(fx.state==0){fx.start=getColor(fx.elem,attr);fx.end=getRGB(fx.end)}fx.elem.style[attr]="rgb("+[Math.max(Math.min(parseInt((fx.pos*(fx.end[0]-fx.start[0]))+fx.start[0]),255),0),Math.max(Math.min(parseInt((fx.pos*(fx.end[1]-fx.start[1]))+fx.start[1]),255),0),Math.max(Math.min(parseInt((fx.pos*(fx.end[2]-fx.start[2]))+fx.start[2]),255),0)].join(",")+")"}});function getRGB(color){var result;if(color&&color.constructor==Array&&color.length==3)return color;if(result=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(color))return[parseInt(result[1]),parseInt(result[2]),parseInt(result[3])];if(result=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(color))return[parseFloat(result[1])*2.55,parseFloat(result[2])*2.55,parseFloat(result[3])*2.55];if(result=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(color))return[parseInt(result[1],16),parseInt(result[2],16),parseInt(result[3],16)];if(result=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(color))return[parseInt(result[1]+result[1],16),parseInt(result[2]+result[2],16),parseInt(result[3]+result[3],16)];return colors[jQuery.trim(color).toLowerCase()]}function getColor(elem,attr){var color;do{color=jQuery.curCSS(elem,attr);if(color!=''&&color!='transparent'||jQuery.nodeName(elem,"body"))break;attr="backgroundColor"}while(elem=elem.parentNode);return getRGB(color)};var colors={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0]}})(akeeba.jQuery);

/*
 * jQuery file picker for plupload
 */
(function(c){var d={};function a(e){return plupload.translate(e)||e}function b(f,e){e.contents().each(function(g,h){h=c(h);if(!h.is(".plupload")){h.remove()}});e.prepend('<div class="plupload_wrapper plupload_scroll"><div id="'+f+'_container" class="plupload_container"><div class="plupload"><div class="plupload_header"><div class="plupload_header_content"><div class="plupload_header_title">'+a("Select files")+'</div><div class="plupload_header_text">'+a("Add files to the upload queue and click the start button.")+'</div></div></div><div class="plupload_content"><div class="plupload_filelist_header"><div class="plupload_file_name">'+a("Filename")+'</div><div class="plupload_file_action">&nbsp;</div><div class="plupload_file_status"><span>'+a("Status")+'</span></div><div class="plupload_file_size">'+a("Size")+'</div><div class="plupload_clearer">&nbsp;</div></div><ul id="'+f+'_filelist" class="plupload_filelist"></ul><div class="plupload_filelist_footer"><div class="plupload_file_name"><div class="plupload_buttons"><a href="#" class="plupload_button plupload_add">'+a("Add files")+'</a><a href="#" class="plupload_button plupload_start">'+a("Start upload")+'</a></div><span class="plupload_upload_status"></span></div><div class="plupload_file_action"></div><div class="plupload_file_status"><span class="plupload_total_status">0%</span></div><div class="plupload_file_size"><span class="plupload_total_file_size">0 b</span></div><div class="plupload_progress"><div class="plupload_progress_container"><div class="plupload_progress_bar"></div></div></div><div class="plupload_clearer">&nbsp;</div></div></div></div></div><input type="hidden" id="'+f+'_count" name="'+f+'_count" value="0" /></div>')}c.fn.pluploadQueue=function(e){if(e){this.each(function(){var j,i,k;i=c(this);k=i.attr("id");if(!k){k=plupload.guid();i.attr("id",k)}j=new plupload.Uploader(c.extend({dragdrop:true,container:k},e));if(e.preinit){e.preinit(j)}d[k]=j;function h(l){var m;if(l.status==plupload.DONE){m="plupload_done"}if(l.status==plupload.FAILED){m="plupload_failed"}if(l.status==plupload.QUEUED){m="plupload_delete"}if(l.status==plupload.UPLOADING){m="plupload_uploading"}c("#"+l.id).attr("class",m).find("a").css("display","block")}function f(){c("span.plupload_total_status",i).html(j.total.percent+"%");c("div.plupload_progress_bar",i).css("width",j.total.percent+"%");c("span.plupload_upload_status",i).text("Uploaded "+j.total.uploaded+"/"+j.files.length+" files");if(j.total.uploaded==j.files.length){j.stop()}}function g(){var m=c("ul.plupload_filelist",i).html(""),n=0,l;c.each(j.files,function(p,o){l="";if(o.status==plupload.DONE){if(o.target_name){l+='<input type="hidden" name="'+k+"_"+n+'_tmpname" value="'+plupload.xmlEncode(o.target_name)+'" />'}l+='<input type="hidden" name="'+k+"_"+n+'_name" value="'+plupload.xmlEncode(o.name)+'" />';l+='<input type="hidden" name="'+k+"_"+n+'_status" value="'+(o.status==plupload.DONE?"done":"failed")+'" />';n++;c("#"+k+"_count").val(n)}m.append('<li id="'+o.id+'"><div class="plupload_file_name"><span>'+o.name+'</span></div><div class="plupload_file_action"><a href="#"></a></div><div class="plupload_file_status">'+o.percent+'%</div><div class="plupload_file_size">'+plupload.formatSize(o.size)+'</div><div class="plupload_clearer">&nbsp;</div>'+l+"</li>");h(o);c("#"+o.id+".plupload_delete a").click(function(q){c("#"+o.id).remove();j.removeFile(o);q.preventDefault()})});c("span.plupload_total_file_size",i).html(plupload.formatSize(j.total.size));if(j.total.queued===0){c("span.plupload_add_text",i).text(a("Add files."))}else{c("span.plupload_add_text",i).text(j.total.queued+" files queued.")}c("a.plupload_start",i).toggleClass("plupload_disabled",j.files.length===0);m[0].scrollTop=m[0].scrollHeight;f();if(!j.files.length&&j.features.dragdrop&&j.settings.dragdrop){c("#"+k+"_filelist").append('<li class="plupload_droptext">'+a("Drag files here.")+"</li>")}}j.bind("UploadFile",function(l,m){c("#"+m.id).addClass("plupload_current_file")});j.bind("Init",function(l,m){b(k,i);if(!e.unique_names&&e.rename){c("#"+k+"_filelist div.plupload_file_name span",i).live("click",function(s){var q=c(s.target),o,r,n,p="";o=l.getFile(q.parents("li")[0].id);n=o.name;r=/^(.+)(\.[^.]+)$/.exec(n);if(r){n=r[1];p=r[2]}q.hide().after('<input type="text" />');q.next().val(n).focus().blur(function(){q.show().next().remove()}).keydown(function(u){var t=c(this);if(u.keyCode==13){u.preventDefault();o.name=t.val()+p;q.text(o.name);t.blur()}})})}c("a.plupload_add",i).attr("id",k+"_browse");l.settings.browse_button=k+"_browse";if(l.features.dragdrop&&l.settings.dragdrop){l.settings.drop_element=k+"_filelist";c("#"+k+"_filelist").append('<li class="plupload_droptext">'+a("Drag files here.")+"</li>")}c("#"+k+"_container").attr("title","Using runtime: "+m.runtime);c("a.plupload_start",i).click(function(n){if(!c(this).hasClass("plupload_disabled")){j.start()}n.preventDefault()});c("a.plupload_stop",i).click(function(n){j.stop();n.preventDefault()});c("a.plupload_start",i).addClass("plupload_disabled")});j.init();if(e.setup){e.setup(j)}j.bind("Error",function(l,o){var m=o.file,n;if(m){n=o.message;if(o.details){n+=" ("+o.details+")"}c("#"+m.id).attr("class","plupload_failed").find("a").css("display","block").attr("title",n)}});j.bind("StateChanged",function(){if(j.state===plupload.STARTED){c("li.plupload_delete a,div.plupload_buttons",i).hide();c("span.plupload_upload_status,div.plupload_progress,a.plupload_stop",i).css("display","block");c("span.plupload_upload_status",i).text("Uploaded 0/"+j.files.length+" files")}else{c("a.plupload_stop,div.plupload_progress",i).hide();c("a.plupload_delete",i).css("display","block")}});j.bind("QueueChanged",g);j.bind("StateChanged",function(l){if(l.state==plupload.STOPPED){g()}});j.bind("FileUploaded",function(l,m){h(m)});j.bind("UploadProgress",function(l,m){c("#"+m.id+" div.plupload_file_status",i).html(m.percent+"%");h(m);f()})});return this}else{return d[c(this[0]).attr("id")]}}})(jQuery);

/*!
 * jQuery blockUI plugin
 * Version 2.33 (29-MAR-2010)
 * @requires jQuery v1.2.3 or later
 *
 * Examples at: http://malsup.com/jquery/block/
 * Copyright (c) 2007-2008 M. Alsup
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Thanks to Amir-Hossein Sobhi for some excellent contributions!
 */
(function($){
	if (/1\.(0|1|2)\.(0|1|2)/.test($.fn.jquery) || /^1.1/.test($.fn.jquery)) {
		alert('blockUI requires jQuery v1.2.3 or later!  You are using v' + $.fn.jquery);
		return;
	}

	$.fn._fadeIn = $.fn.fadeIn;

	var noOp = function() {};

	// this bit is to ensure we don't call setExpression when we shouldn't (with extra muscle to handle
	// retarded userAgent strings on Vista)
	var mode = document.documentMode || 0;
	var setExpr = $.browser.msie && (($.browser.version < 8 && !mode) || mode < 8);
	var ie6 = $.browser.msie && /MSIE 6.0/.test(navigator.userAgent) && !mode;

	// global $ methods for blocking/unblocking the entire page
	$.blockUI   = function(opts) { install(window, opts); };
	$.unblockUI = function(opts) { remove(window, opts); };

	// convenience method for quick growl-like notifications  (http://www.google.com/search?q=growl)
	$.growlUI = function(title, message, timeout, onClose) {
		var $m = $('<div class="growlUI"></div>');
		if (title) $m.append('<h1>'+title+'</h1>');
		if (message) $m.append('<h2>'+message+'</h2>');
		if (timeout == undefined) timeout = 3000;
		$.blockUI({
			message: $m, fadeIn: 700, fadeOut: 1000, centerY: false,
			timeout: timeout, showOverlay: false,
			onUnblock: onClose,
			css: $.blockUI.defaults.growlCSS
		});
	};

	// plugin method for blocking element content
	$.fn.block = function(opts) {
		return this.unblock({ fadeOut: 0 }).each(function() {
			if ($.css(this,'position') == 'static')
				this.style.position = 'relative';
			if ($.browser.msie)
				this.style.zoom = 1; // force 'hasLayout'
			install(this, opts);
		});
	};

	// plugin method for unblocking element content
	$.fn.unblock = function(opts) {
		return this.each(function() {
			remove(this, opts);
		});
	};

	$.blockUI.version = 2.33; // 2nd generation blocking at no extra cost!

	// override these in your code to change the default behavior and style
	$.blockUI.defaults = {
		// message displayed when blocking (use null for no message)
		message:  '<h1>Please wait...</h1>',

		title: null,	  // title string; only used when theme == true
		draggable: true,  // only used when theme == true (requires jquery-ui.js to be loaded)

		theme: false, // set to true to use with jQuery UI themes

		// styles for the message when blocking; if you wish to disable
		// these and use an external stylesheet then do this in your code:
		// $.blockUI.defaults.css = {};
		css: {
			padding:	0,
			margin:		0,
			width:		'30%',
			top:		'40%',
			left:		'35%',
			textAlign:	'center',
			color:		'#000',
			border:		'3px solid #aaa',
			backgroundColor:'#fff',
			cursor:		'wait'
		},

		// minimal style set used when themes are used
		themedCSS: {
			width:	'30%',
			top:	'40%',
			left:	'35%'
		},

		// styles for the overlay
		overlayCSS:  {
			backgroundColor: '#000',
			opacity:	  	 0.6,
			cursor:		  	 'wait'
		},

		// styles applied when using $.growlUI
		growlCSS: {
			width:  	'350px',
			top:		'10px',
			left:   	'',
			right:  	'10px',
			border: 	'none',
			padding:	'5px',
			opacity:	0.6,
			cursor: 	'default',
			color:		'#fff',
			backgroundColor: '#000',
			'-webkit-border-radius': '10px',
			'-moz-border-radius':	 '10px',
			'border-radius': 		 '10px'
		},

		// IE issues: 'about:blank' fails on HTTPS and javascript:false is s-l-o-w
		// (hat tip to Jorge H. N. de Vasconcelos)
		iframeSrc: /^https/i.test(window.location.href || '') ? 'javascript:false' : 'about:blank',

		// force usage of iframe in non-IE browsers (handy for blocking applets)
		forceIframe: false,

		// z-index for the blocking overlay
		baseZ: 1000,

		// set these to true to have the message automatically centered
		centerX: true, // <-- only effects element blocking (page block controlled via css above)
		centerY: true,

		// allow body element to be stetched in ie6; this makes blocking look better
		// on "short" pages.  disable if you wish to prevent changes to the body height
		allowBodyStretch: true,

		// enable if you want key and mouse events to be disabled for content that is blocked
		bindEvents: true,

		// be default blockUI will supress tab navigation from leaving blocking content
		// (if bindEvents is true)
		constrainTabKey: true,

		// fadeIn time in millis; set to 0 to disable fadeIn on block
		fadeIn:  200,

		// fadeOut time in millis; set to 0 to disable fadeOut on unblock
		fadeOut:  400,

		// time in millis to wait before auto-unblocking; set to 0 to disable auto-unblock
		timeout: 0,

		// disable if you don't want to show the overlay
		showOverlay: true,

		// if true, focus will be placed in the first available input field when
		// page blocking
		focusInput: true,

		// suppresses the use of overlay styles on FF/Linux (due to performance issues with opacity)
		applyPlatformOpacityRules: true,

		// callback method invoked when fadeIn has completed and blocking message is visible
		onBlock: null,

		// callback method invoked when unblocking has completed; the callback is
		// passed the element that has been unblocked (which is the window object for page
		// blocks) and the options that were passed to the unblock call:
		//	 onUnblock(element, options)
		onUnblock: null,

		// don't ask; if you really must know: http://groups.google.com/group/jquery-en/browse_thread/thread/36640a8730503595/2f6a79a77a78e493#2f6a79a77a78e493
		quirksmodeOffsetHack: 4
	};

	// private data and functions follow...

	var pageBlock = null;
	var pageBlockEls = [];

	function install(el, opts) {
		var full = (el == window);
		var msg = opts && opts.message !== undefined ? opts.message : undefined;
		opts = $.extend({}, $.blockUI.defaults, opts || {});
		opts.overlayCSS = $.extend({}, $.blockUI.defaults.overlayCSS, opts.overlayCSS || {});
		var css = $.extend({}, $.blockUI.defaults.css, opts.css || {});
		var themedCSS = $.extend({}, $.blockUI.defaults.themedCSS, opts.themedCSS || {});
		msg = msg === undefined ? opts.message : msg;

		// remove the current block (if there is one)
		if (full && pageBlock)
			remove(window, {fadeOut:0});

		// if an existing element is being used as the blocking content then we capture
		// its current place in the DOM (and current display style) so we can restore
		// it when we unblock
		if (msg && typeof msg != 'string' && (msg.parentNode || msg.jquery)) {
			var node = msg.jquery ? msg[0] : msg;
			var data = {};
			$(el).data('blockUI.history', data);
			data.el = node;
			data.parent = node.parentNode;
			data.display = node.style.display;
			data.position = node.style.position;
			if (data.parent)
				data.parent.removeChild(node);
		}

		var z = opts.baseZ;

		// blockUI uses 3 layers for blocking, for simplicity they are all used on every platform;
		// layer1 is the iframe layer which is used to supress bleed through of underlying content
		// layer2 is the overlay layer which has opacity and a wait cursor (by default)
		// layer3 is the message content that is displayed while blocking

		var lyr1 = ($.browser.msie || opts.forceIframe)
			? $('<iframe class="blockUI" style="z-index:'+ (z++) +';display:none;border:none;margin:0;padding:0;position:absolute;width:100%;height:100%;top:0;left:0" src="'+opts.iframeSrc+'"></iframe>')
			: $('<div class="blockUI" style="display:none"></div>');
		var lyr2 = $('<div class="blockUI blockOverlay" style="z-index:'+ (z++) +';display:none;border:none;margin:0;padding:0;width:100%;height:100%;top:0;left:0"></div>');

		var lyr3, s;
		if (opts.theme && full) {
			s = '<div class="blockUI blockMsg blockPage ui-dialog ui-widget ui-corner-all" style="z-index:'+z+';display:none;position:fixed">' +
					'<div class="ui-widget-header ui-dialog-titlebar blockTitle">'+(opts.title || '&nbsp;')+'</div>' +
					'<div class="ui-widget-content ui-dialog-content"></div>' +
				'</div>';
		}
		else if (opts.theme) {
			s = '<div class="blockUI blockMsg blockElement ui-dialog ui-widget ui-corner-all" style="z-index:'+z+';display:none;position:absolute">' +
					'<div class="ui-widget-header ui-dialog-titlebar blockTitle">'+(opts.title || '&nbsp;')+'</div>' +
					'<div class="ui-widget-content ui-dialog-content"></div>' +
				'</div>';
		}
		else if (full) {
			s = '<div class="blockUI blockMsg blockPage" style="z-index:'+z+';display:none;position:fixed"></div>';
		}
		else {
			s = '<div class="blockUI blockMsg blockElement" style="z-index:'+z+';display:none;position:absolute"></div>';
		}
		lyr3 = $(s);

		// if we have a message, style it
		if (msg) {
			if (opts.theme) {
				lyr3.css(themedCSS);
				lyr3.addClass('ui-widget-content');
			}
			else
				lyr3.css(css);
		}

		// style the overlay
		if (!opts.applyPlatformOpacityRules || !($.browser.mozilla && /Linux/.test(navigator.platform)))
			lyr2.css(opts.overlayCSS);
		lyr2.css('position', full ? 'fixed' : 'absolute');

		// make iframe layer transparent in IE
		if ($.browser.msie || opts.forceIframe)
			lyr1.css('opacity',0.0);

		//$([lyr1[0],lyr2[0],lyr3[0]]).appendTo(full ? 'body' : el);
		var layers = [lyr1,lyr2,lyr3], $par = full ? $('body') : $(el);
		$.each(layers, function() {
			this.appendTo($par);
		});

		if (opts.theme && opts.draggable && $.fn.draggable) {
			lyr3.draggable({
				handle: '.ui-dialog-titlebar',
				cancel: 'li'
			});
		}

		// ie7 must use absolute positioning in quirks mode and to account for activex issues (when scrolling)
		var expr = setExpr && (!$.boxModel || $('object,embed', full ? null : el).length > 0);
		if (ie6 || expr) {
			// give body 100% height
			if (full && opts.allowBodyStretch && $.boxModel)
				$('html,body').css('height','100%');

			// fix ie6 issue when blocked element has a border width
			if ((ie6 || !$.boxModel) && !full) {
				var t = sz(el,'borderTopWidth'), l = sz(el,'borderLeftWidth');
				var fixT = t ? '(0 - '+t+')' : 0;
				var fixL = l ? '(0 - '+l+')' : 0;
			}

			// simulate fixed position
			$.each([lyr1,lyr2,lyr3], function(i,o) {
				var s = o[0].style;
				s.position = 'absolute';
				if (i < 2) {
					full ? s.setExpression('height','Math.max(document.body.scrollHeight, document.body.offsetHeight) - (jQuery.boxModel?0:'+opts.quirksmodeOffsetHack+') + "px"')
						 : s.setExpression('height','this.parentNode.offsetHeight + "px"');
					full ? s.setExpression('width','jQuery.boxModel && document.documentElement.clientWidth || document.body.clientWidth + "px"')
						 : s.setExpression('width','this.parentNode.offsetWidth + "px"');
					if (fixL) s.setExpression('left', fixL);
					if (fixT) s.setExpression('top', fixT);
				}
				else if (opts.centerY) {
					if (full) s.setExpression('top','(document.documentElement.clientHeight || document.body.clientHeight) / 2 - (this.offsetHeight / 2) + (blah = document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + "px"');
					s.marginTop = 0;
				}
				else if (!opts.centerY && full) {
					var top = (opts.css && opts.css.top) ? parseInt(opts.css.top) : 0;
					var expression = '((document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop) + '+top+') + "px"';
					s.setExpression('top',expression);
				}
			});
		}

		// show the message
		if (msg) {
			if (opts.theme)
				lyr3.find('.ui-widget-content').append(msg);
			else
				lyr3.append(msg);
			if (msg.jquery || msg.nodeType)
				$(msg).show();
		}

		if (($.browser.msie || opts.forceIframe) && opts.showOverlay)
			lyr1.show(); // opacity is zero
		if (opts.fadeIn) {
			var cb = opts.onBlock ? opts.onBlock : noOp;
			var cb1 = (opts.showOverlay && !msg) ? cb : noOp;
			var cb2 = msg ? cb : noOp;
			if (opts.showOverlay)
				lyr2._fadeIn(opts.fadeIn, cb1);
			if (msg)
				lyr3._fadeIn(opts.fadeIn, cb2);
		}
		else {
			if (opts.showOverlay)
				lyr2.show();
			if (msg)
				lyr3.show();
			if (opts.onBlock)
				opts.onBlock();
		}

		// bind key and mouse events
		bind(1, el, opts);

		if (full) {
			pageBlock = lyr3[0];
			pageBlockEls = $(':input:enabled:visible',pageBlock);
			if (opts.focusInput)
				setTimeout(focus, 20);
		}
		else
			center(lyr3[0], opts.centerX, opts.centerY);

		if (opts.timeout) {
			// auto-unblock
			var to = setTimeout(function() {
				full ? $.unblockUI(opts) : $(el).unblock(opts);
			}, opts.timeout);
			$(el).data('blockUI.timeout', to);
		}
	};

	// remove the block
	function remove(el, opts) {
		var full = (el == window);
		var $el = $(el);
		var data = $el.data('blockUI.history');
		var to = $el.data('blockUI.timeout');
		if (to) {
			clearTimeout(to);
			$el.removeData('blockUI.timeout');
		}
		opts = $.extend({}, $.blockUI.defaults, opts || {});
		bind(0, el, opts); // unbind events

		var els;
		if (full) // crazy selector to handle odd field errors in ie6/7
			els = $('body').children().filter('.blockUI').add('body > .blockUI');
		else
			els = $('.blockUI', el);

		if (full)
			pageBlock = pageBlockEls = null;

		if (opts.fadeOut) {
			els.fadeOut(opts.fadeOut);
			setTimeout(function() { reset(els,data,opts,el); }, opts.fadeOut);
		}
		else
			reset(els, data, opts, el);
	};

	// move blocking element back into the DOM where it started
	function reset(els,data,opts,el) {
		els.each(function(i,o) {
			// remove via DOM calls so we don't lose event handlers
			if (this.parentNode)
				this.parentNode.removeChild(this);
		});

		if (data && data.el) {
			data.el.style.display = data.display;
			data.el.style.position = data.position;
			if (data.parent)
				data.parent.appendChild(data.el);
			$(el).removeData('blockUI.history');
		}

		if (typeof opts.onUnblock == 'function')
			opts.onUnblock(el,opts);
	};

	// bind/unbind the handler
	function bind(b, el, opts) {
		var full = el == window, $el = $(el);

		// don't bother unbinding if there is nothing to unbind
		if (!b && (full && !pageBlock || !full && !$el.data('blockUI.isBlocked')))
			return;
		if (!full)
			$el.data('blockUI.isBlocked', b);

		// don't bind events when overlay is not in use or if bindEvents is false
		if (!opts.bindEvents || (b && !opts.showOverlay))
			return;

		// bind anchors and inputs for mouse and key events
		var events = 'mousedown mouseup keydown keypress';
		b ? $(document).bind(events, opts, handler) : $(document).unbind(events, handler);

	// former impl...
	//	   var $e = $('a,:input');
	//	   b ? $e.bind(events, opts, handler) : $e.unbind(events, handler);
	};

	// event handler to suppress keyboard/mouse events when blocking
	function handler(e) {
		// allow tab navigation (conditionally)
		if (e.keyCode && e.keyCode == 9) {
			if (pageBlock && e.data.constrainTabKey) {
				var els = pageBlockEls;
				var fwd = !e.shiftKey && e.target == els[els.length-1];
				var back = e.shiftKey && e.target == els[0];
				if (fwd || back) {
					setTimeout(function(){focus(back)},10);
					return false;
				}
			}
		}
		// allow events within the message content
		if ($(e.target).parents('div.blockMsg').length > 0)
			return true;

		// allow events for content that is not being blocked
		return $(e.target).parents().children().filter('div.blockUI').length == 0;
	};

	function focus(back) {
		if (!pageBlockEls)
			return;
		var e = pageBlockEls[back===true ? pageBlockEls.length-1 : 0];
		if (e)
			e.focus();
	};

	function center(el, x, y) {
		var p = el.parentNode, s = el.style;
		var l = ((p.offsetWidth - el.offsetWidth)/2) - sz(p,'borderLeftWidth');
		var t = ((p.offsetHeight - el.offsetHeight)/2) - sz(p,'borderTopWidth');
		if (x) s.left = l > 0 ? (l+'px') : '0';
		if (y) s.top  = t > 0 ? (t+'px') : '0';
	};

	function sz(el, p) {
		return parseInt($.css(el,p))||0;
	};
})(jQuery);
//=============================================================================
})(akeeba.jQuery); // <<< DO NOT REMOVE - USED FOR JQUERY COMPATIBILITY <<<
//=============================================================================

/*
 * plupload 1.2.3 full
 */
(function(){var c=0,h=[],j={},f={},a={"<":"lt",">":"gt","&":"amp",'"':"quot","'":"#39"},i=/[<>&\"\']/g,b;function e(){this.returnValue=false}function g(){this.cancelBubble=true}(function(k){var l=k.split(/,/),m,o,n;for(m=0;m<l.length;m+=2){n=l[m+1].split(/ /);for(o=0;o<n.length;o++){f[n[o]]=l[m]}}})("application/msword,doc dot,application/pdf,pdf,application/pgp-signature,pgp,application/postscript,ps ai eps,application/rtf,rtf,application/vnd.ms-excel,xls xlb,application/vnd.ms-powerpoint,ppt pps pot,application/zip,zip,application/x-shockwave-flash,swf swfl,application/vnd.openxmlformats,docx pptx xlsx,audio/mpeg,mpga mpega mp2 mp3,audio/x-wav,wav,image/bmp,bmp,image/gif,gif,image/jpeg,jpeg jpg jpe,image/png,png,image/svg+xml,svg svgz,image/tiff,tiff tif,text/html,htm html xhtml,text/rtf,rtf,video/mpeg,mpeg mpg mpe,video/quicktime,qt mov,video/x-flv,flv,video/vnd.rn-realvideo,rv,text/plain,asc txt text diff log,application/octet-stream,exe");var d={STOPPED:1,STARTED:2,QUEUED:1,UPLOADING:2,FAILED:4,DONE:5,GENERIC_ERROR:-100,HTTP_ERROR:-200,IO_ERROR:-300,SECURITY_ERROR:-400,INIT_ERROR:-500,FILE_SIZE_ERROR:-600,FILE_EXTENSION_ERROR:-700,mimeTypes:f,extend:function(k){d.each(arguments,function(l,m){if(m>0){d.each(l,function(o,n){k[n]=o})}});return k},cleanName:function(k){var l,m;m=[/[\300-\306]/g,"A",/[\340-\346]/g,"a",/\307/g,"C",/\347/g,"c",/[\310-\313]/g,"E",/[\350-\353]/g,"e",/[\314-\317]/g,"I",/[\354-\357]/g,"i",/\321/g,"N",/\361/g,"n",/[\322-\330]/g,"O",/[\362-\370]/g,"o",/[\331-\334]/g,"U",/[\371-\374]/g,"u"];for(l=0;l<m.length;l+=2){k=k.replace(m[l],m[l+1])}k=k.replace(/\s+/g,"_");k=k.replace(/[^a-z0-9_\-\.]+/gi,"");return k},addRuntime:function(k,l){l.name=k;h[k]=l;h.push(l);return l},guid:function(){var k=new Date().getTime().toString(32),l;for(l=0;l<5;l++){k+=Math.floor(Math.random()*65535).toString(32)}return(d.guidPrefix||"p")+k+(c++).toString(32)},buildUrl:function(l,k){var m="";d.each(k,function(o,n){m+=(m?"&":"")+encodeURIComponent(n)+"="+encodeURIComponent(o)});if(m){l+=(l.indexOf("?")>0?"&":"?")+m}return l},each:function(n,o){var m,l,k;if(n){m=n.length;if(m===b){for(l in n){if(n.hasOwnProperty(l)){if(o(n[l],l)===false){return}}}}else{for(k=0;k<m;k++){if(o(n[k],k)===false){return}}}}},formatSize:function(k){if(k===b){return d.translate("N/A")}if(k>1048576){return Math.round(k/1048576,1)+" MB"}if(k>1024){return Math.round(k/1024,1)+" KB"}return k+" b"},getPos:function(l,p){var q=0,o=0,s,r=document,m,n;l=l;p=p||r.body;function k(w){var u,v,t=0,z=0;if(w){v=w.getBoundingClientRect();u=r.compatMode==="CSS1Compat"?r.documentElement:r.body;t=v.left+u.scrollLeft;z=v.top+u.scrollTop}return{x:t,y:z}}if(l.getBoundingClientRect&&(navigator.userAgent.indexOf("MSIE")>0&&r.documentMode!==8)){m=k(l);n=k(p);return{x:m.x-n.x,y:m.y-n.y}}s=l;while(s&&s!=p&&s.nodeType){q+=s.offsetLeft||0;o+=s.offsetTop||0;s=s.offsetParent}s=l.parentNode;while(s&&s!=p&&s.nodeType){q-=s.scrollLeft||0;o-=s.scrollTop||0;s=s.parentNode}return{x:q,y:o}},getSize:function(k){return{w:k.clientWidth||k.offsetWidth,h:k.clientHeight||k.offsetHeight}},parseSize:function(k){var l;if(typeof(k)=="string"){k=/^([0-9]+)([mgk]+)$/.exec(k.toLowerCase().replace(/[^0-9mkg]/g,""));l=k[2];k=+k[1];if(l=="g"){k*=1073741824}if(l=="m"){k*=1048576}if(l=="k"){k*=1024}}return k},xmlEncode:function(k){return k?(""+k).replace(i,function(l){return a[l]?"&"+a[l]+";":l}):k},toArray:function(m){var l,k=[];for(l=0;l<m.length;l++){k[l]=m[l]}return k},addI18n:function(k){return d.extend(j,k)},translate:function(k){return j[k]||k},addEvent:function(l,k,m){if(l.attachEvent){l.attachEvent("on"+k,function(){var n=window.event;if(!n.target){n.target=n.srcElement}n.preventDefault=e;n.stopPropagation=g;m(n)})}else{if(l.addEventListener){l.addEventListener(k,m,false)}}}};d.Uploader=function(n){var l={},q,p=[],r,m;q=new d.QueueProgress();n=d.extend({chunk_size:0,max_file_size:"1gb",multi_selection:true,file_data_name:"file",filters:[]},n);function o(){var s;if(this.state==d.STARTED&&r<p.length){s=p[r++];if(s.status==d.QUEUED){this.trigger("UploadFile",s)}else{o.call(this)}}else{this.stop()}}function k(){var t,s;q.reset();for(t=0;t<p.length;t++){s=p[t];if(s.size!==b){q.size+=s.size;q.loaded+=s.loaded}else{q.size=b}if(s.status==d.DONE){q.uploaded++}else{if(s.status==d.FAILED){q.failed++}else{q.queued++}}}if(q.size===b){q.percent=p.length>0?Math.ceil(q.uploaded/p.length*100):0}else{q.bytesPerSec=Math.ceil(q.loaded/((+new Date()-m||1)/1000));q.percent=q.size>0?Math.ceil(q.loaded/q.size*100):0}}d.extend(this,{state:d.STOPPED,features:{},files:p,settings:n,total:q,id:d.guid(),init:function(){var x=this,y,u,t,w=0,v;n.page_url=n.page_url||document.location.pathname.replace(/\/[^\/]+$/g,"/");if(!/^(\w+:\/\/|\/)/.test(n.url)){n.url=n.page_url+n.url}n.chunk_size=d.parseSize(n.chunk_size);n.max_file_size=d.parseSize(n.max_file_size);x.bind("FilesAdded",function(z,C){var B,A,F=0,E,D=n.filters;if(D&&D.length){E={};d.each(D,function(G){d.each(G.extensions.split(/,/),function(H){E[H.toLowerCase()]=true})})}for(B=0;B<C.length;B++){A=C[B];A.loaded=0;A.percent=0;A.status=d.QUEUED;if(E&&!E[A.name.toLowerCase().split(".").slice(-1)]){z.trigger("Error",{code:d.FILE_EXTENSION_ERROR,message:"File extension error.",file:A});continue}if(A.size!==b&&A.size>n.max_file_size){z.trigger("Error",{code:d.FILE_SIZE_ERROR,message:"File size error.",file:A});continue}p.push(A);F++}if(F){x.trigger("QueueChanged");x.refresh()}});if(n.unique_names){x.bind("UploadFile",function(z,A){A.target_name=A.id+".tmp"})}x.bind("UploadProgress",function(z,A){if(A.status==d.QUEUED){A.status=d.UPLOADING}A.percent=A.size>0?Math.ceil(A.loaded/A.size*100):100;k()});x.bind("StateChanged",function(z){if(z.state==d.STARTED){m=(+new Date())}});x.bind("QueueChanged",k);x.bind("Error",function(z,A){if(A.file){A.file.status=d.FAILED;k();window.setTimeout(function(){o.call(x)})}});x.bind("FileUploaded",function(z,A){A.status=d.DONE;z.trigger("UploadProgress",A);o.call(x)});if(n.runtimes){u=[];v=n.runtimes.split(/\s?,\s?/);for(y=0;y<v.length;y++){if(h[v[y]]){u.push(h[v[y]])}}}else{u=h}function s(){var C=u[w++],B,z,A;if(C){B=C.getFeatures();z=x.settings.required_features;if(z){z=z.split(",");for(A=0;A<z.length;A++){if(!B[z[A]]){s();return}}}C.init(x,function(D){if(D&&D.success){x.features=B;x.trigger("Init",{runtime:C.name});x.trigger("PostInit");x.refresh()}else{s()}})}else{x.trigger("Error",{code:d.INIT_ERROR,message:"Init error."})}}s()},refresh:function(){this.trigger("Refresh")},start:function(){if(this.state!=d.STARTED){r=0;this.state=d.STARTED;this.trigger("StateChanged");o.call(this)}},stop:function(){if(this.state!=d.STOPPED){this.state=d.STOPPED;this.trigger("StateChanged")}},getFile:function(t){var s;for(s=p.length-1;s>=0;s--){if(p[s].id===t){return p[s]}}},removeFile:function(t){var s;for(s=p.length-1;s>=0;s--){if(p[s].id===t.id){return this.splice(s,1)[0]}}},splice:function(u,s){var t;t=p.splice(u,s);this.trigger("FilesRemoved",t);this.trigger("QueueChanged");return t},trigger:function(t){var v=l[t.toLowerCase()],u,s;if(v){s=Array.prototype.slice.call(arguments);s[0]=this;for(u=0;u<v.length;u++){if(v[u].func.apply(v[u].scope,s)===false){return false}}}return true},bind:function(s,u,t){var v;s=s.toLowerCase();v=l[s]||[];v.push({func:u,scope:t||this});l[s]=v},unbind:function(s,u){var v=l[s.toLowerCase()],t;if(v){for(t=v.length-1;t>=0;t--){if(v[t].func===u){v.splice(t,1)}}}}})};d.File=function(n,l,m){var k=this;k.id=n;k.name=l;k.size=m;k.loaded=0;k.percent=0;k.status=0};d.Runtime=function(){this.getFeatures=function(){};this.init=function(k,l){}};d.QueueProgress=function(){var k=this;k.size=0;k.loaded=0;k.uploaded=0;k.failed=0;k.queued=0;k.percent=0;k.bytesPerSec=0;k.reset=function(){k.size=k.loaded=k.uploaded=k.failed=k.queued=k.percent=k.bytesPerSec=0}};d.runtimes={};window.plupload=d})();(function(b){var c={};function a(i,e,k,j,d){var l,g,f,h;g=google.gears.factory.create("beta.canvas");g.decode(i);h=Math.min(e/g.width,k/g.height);if(h<1){e=Math.round(g.width*h);k=Math.round(g.height*h)}else{e=g.width;k=g.height}g.resize(e,k);return g.encode(d,{quality:j/100})}b.runtimes.Gears=b.addRuntime("gears",{getFeatures:function(){return{dragdrop:true,jpgresize:true,pngresize:true,chunks:true,progress:true,multipart:true}},init:function(g,i){var h;if(!window.google||!google.gears){return i({success:false})}try{h=google.gears.factory.create("beta.desktop")}catch(f){return i({success:false})}function d(k){var j,e,l=[],m;for(e=0;e<k.length;e++){j=k[e];m=b.guid();c[m]=j.blob;l.push(new b.File(m,j.name,j.blob.length))}g.trigger("FilesAdded",l)}g.bind("PostInit",function(){var j=g.settings,e=document.getElementById(j.drop_element);if(e){b.addEvent(e,"dragover",function(k){h.setDropEffect(k,"copy");k.preventDefault()});b.addEvent(e,"drop",function(l){var k=h.getDragData(l,"application/x-gears-files");if(k){d(k.files)}l.preventDefault()});e=0}b.addEvent(document.getElementById(j.browse_button),"click",function(o){var n=[],l,k,m;o.preventDefault();for(l=0;l<j.filters.length;l++){m=j.filters[l].extensions.split(",");for(k=0;k<m.length;k++){n.push("."+m[k])}}h.openFiles(d,{singleFile:!j.multi_selection,filter:n})})});g.bind("UploadFile",function(o,l){var q=0,p,m,n=0,k=o.settings.resize,e;m=o.settings.chunk_size;e=m>0;p=Math.ceil(l.size/m);if(!e){m=l.size;p=1}if(k&&/\.(png|jpg|jpeg)$/i.test(l.name)){c[l.id]=a(c[l.id],k.width,k.height,k.quality||90,/\.png$/i.test(l.name)?"image/png":"image/jpeg")}l.size=c[l.id].length;function j(){var u,w,s=o.settings.multipart,r=0,v={name:l.target_name||l.name};function t(y){var x,C="----pluploadboundary"+b.guid(),A="--",B="\r\n",z;if(s){u.setRequestHeader("Content-Type","multipart/form-data; boundary="+C);x=google.gears.factory.create("beta.blobbuilder");b.each(o.settings.multipart_params,function(E,D){x.append(A+C+B+'Content-Disposition: form-data; name="'+D+'"'+B+B);x.append(E+B)});x.append(A+C+B+'Content-Disposition: form-data; name="'+o.settings.file_data_name+'"; filename="'+l.name+'"'+B+"Content-Type: application/octet-stream"+B+B);x.append(y);x.append(B+A+C+A+B);z=x.getAsBlob();r=z.length-y.length;y=z}u.send(y)}if(l.status==b.DONE||l.status==b.FAILED||o.state==b.STOPPED){return}if(e){v.chunk=q;v.chunks=p}w=Math.min(m,l.size-(q*m));u=google.gears.factory.create("beta.httprequest");u.open("POST",b.buildUrl(o.settings.url,v));if(!s){u.setRequestHeader("Content-Disposition",'attachment; filename="'+l.name+'"');u.setRequestHeader("Content-Type","application/octet-stream")}b.each(o.settings.headers,function(y,x){u.setRequestHeader(x,y)});u.upload.onprogress=function(x){l.loaded=n+x.loaded-r;o.trigger("UploadProgress",l)};u.onreadystatechange=function(){var x;if(u.readyState==4){if(u.status==200){x={chunk:q,chunks:p,response:u.responseText,status:u.status};o.trigger("ChunkUploaded",l,x);if(x.cancelled){l.status=b.FAILED;return}n+=w;if(++q>=p){l.status=b.DONE;o.trigger("FileUploaded",l,{response:u.responseText,status:u.status})}else{j()}}else{o.trigger("Error",{code:b.HTTP_ERROR,message:"HTTP Error.",file:l,chunk:q,chunks:p,status:u.status})}}};if(q<p){t(c[l.id].slice(q*m,w))}}j()});i({success:true})}})})(plupload);(function(c){var a={};function b(l){var k,j=typeof l,h,e,g,f;if(j==="string"){k="\bb\tt\nn\ff\rr\"\"''\\\\";return'"'+l.replace(/([\u0080-\uFFFF\x00-\x1f\"])/g,function(n,m){var i=k.indexOf(m);if(i+1){return"\\"+k.charAt(i+1)}n=m.charCodeAt().toString(16);return"\\u"+"0000".substring(n.length)+n})+'"'}if(j=="object"){e=l.length!==h;k="";if(e){for(g=0;g<l.length;g++){if(k){k+=","}k+=b(l[g])}k="["+k+"]"}else{for(f in l){if(l.hasOwnProperty(f)){if(k){k+=","}k+=b(f)+":"+b(l[f])}}k="{"+k+"}"}return k}if(l===h){return"null"}return""+l}function d(o){var r=false,f=null,k=null,g,h,i,q,j,m=0;try{try{k=new ActiveXObject("AgControl.AgControl");if(k.IsVersionSupported(o)){r=true}k=null}catch(n){var l=navigator.plugins["Silverlight Plug-In"];if(l){g=l.description;if(g==="1.0.30226.2"){g="2.0.30226.2"}h=g.split(".");while(h.length>3){h.pop()}while(h.length<4){h.push(0)}i=o.split(".");while(i.length>4){i.pop()}do{q=parseInt(i[m],10);j=parseInt(h[m],10);m++}while(m<i.length&&q===j);if(q<=j&&!isNaN(q)){r=true}}}}catch(p){r=false}return r}c.silverlight={trigger:function(j,f){var h=a[j],g,e;if(h){e=c.toArray(arguments).slice(1);e[0]="Silverlight:"+f;setTimeout(function(){h.trigger.apply(h,e)},0)}}};c.runtimes.Silverlight=c.addRuntime("silverlight",{getFeatures:function(){return{jpgresize:true,pngresize:true,chunks:true,progress:true,multipart:true}},init:function(l,m){var k,h="",j=l.settings.filters,g,f=document.body;if(!d("2.0.31005.0")||(window.opera&&window.opera.buildNumber)){m({success:false});return}a[l.id]=l;k=document.createElement("div");k.id=l.id+"_silverlight_container";c.extend(k.style,{position:"absolute",top:"0px",background:l.settings.shim_bgcolor||"transparent",zIndex:99999,width:"100px",height:"100px",overflow:"hidden",opacity:l.settings.shim_bgcolor?"":0.01});k.className="plupload silverlight";if(l.settings.container){f=document.getElementById(l.settings.container);f.style.position="relative"}f.appendChild(k);for(g=0;g<j.length;g++){h+=(h!=""?"|":"")+j[g].title+" | *."+j[g].extensions.replace(/,/g,";*.")}k.innerHTML='<object id="'+l.id+'_silverlight" data="data:application/x-silverlight," type="application/x-silverlight-2" style="outline:none;" width="1024" height="1024"><param name="source" value="'+l.settings.silverlight_xap_url+'"/><param name="background" value="Transparent"/><param name="windowless" value="true"/><param name="initParams" value="id='+l.id+",filter="+h+'"/></object>';function e(){return document.getElementById(l.id+"_silverlight").content.Upload}l.bind("Silverlight:Init",function(){var i,n={};l.bind("Silverlight:StartSelectFiles",function(o){i=[]});l.bind("Silverlight:SelectFile",function(o,r,p,q){var s;s=c.guid();n[s]=r;n[r]=s;i.push(new c.File(s,p,q))});l.bind("Silverlight:SelectSuccessful",function(){if(i.length){l.trigger("FilesAdded",i)}});l.bind("Silverlight:UploadChunkError",function(o,r,p,s,q){l.trigger("Error",{code:c.IO_ERROR,message:"IO Error.",details:q,file:o.getFile(n[r])})});l.bind("Silverlight:UploadFileProgress",function(o,s,p,r){var q=o.getFile(n[s]);if(q.status!=c.FAILED){q.size=r;q.loaded=p;o.trigger("UploadProgress",q)}});l.bind("Refresh",function(o){var p,q,r;p=document.getElementById(o.settings.browse_button);q=c.getPos(p,document.getElementById(o.settings.container));r=c.getSize(p);c.extend(document.getElementById(o.id+"_silverlight_container").style,{top:q.y+"px",left:q.x+"px",width:r.w+"px",height:r.h+"px"})});l.bind("Silverlight:UploadChunkSuccessful",function(o,r,p,u,t){var s,q=o.getFile(n[r]);s={chunk:p,chunks:u,response:t};o.trigger("ChunkUploaded",q,s);if(q.status!=c.FAILED){e().UploadNextChunk()}if(p==u-1){q.status=c.DONE;o.trigger("FileUploaded",q,{response:t})}});l.bind("Silverlight:UploadSuccessful",function(o,r,p){var q=o.getFile(n[r]);q.status=c.DONE;o.trigger("FileUploaded",q,{response:p})});l.bind("FilesRemoved",function(o,q){var p;for(p=0;p<q.length;p++){e().RemoveFile(n[q[p].id])}});l.bind("UploadFile",function(o,q){var r=o.settings,p=r.resize||{};e().UploadFile(n[q.id],c.buildUrl(o.settings.url,{name:q.target_name||q.name}),b({chunk_size:r.chunk_size,image_width:p.width,image_height:p.height,image_quality:p.quality||90,multipart:!!r.multipart,multipart_params:r.multipart_params||{},headers:r.headers}))});m({success:true})})}})})(plupload);(function(c){var a={};function b(){var d;try{d=navigator.plugins["Shockwave Flash"];d=d.description}catch(f){try{d=new ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable("$version")}catch(e){d="0.0"}}d=d.match(/\d+/g);return parseFloat(d[0]+"."+d[1])}c.flash={trigger:function(f,d,e){setTimeout(function(){var j=a[f],h,g;if(j){j.trigger("Flash:"+d,e)}},0)}};c.runtimes.Flash=c.addRuntime("flash",{getFeatures:function(){return{jpgresize:true,pngresize:true,chunks:true,progress:true,multipart:true}},init:function(g,l){var k,f,h,e,m=0,d=document.body;if(b()<10){l({success:false});return}a[g.id]=g;k=document.getElementById(g.settings.browse_button);f=document.createElement("div");f.id=g.id+"_flash_container";c.extend(f.style,{position:"absolute",top:"0px",background:g.settings.shim_bgcolor||"transparent",zIndex:99999,width:"100%",height:"100%"});f.className="plupload flash";if(g.settings.container){d=document.getElementById(g.settings.container);d.style.position="relative"}d.appendChild(f);h="id="+escape(g.id);f.innerHTML='<object id="'+g.id+'_flash" width="100%" height="100%" style="outline:0" type="application/x-shockwave-flash" data="'+g.settings.flash_swf_url+'"><param name="movie" value="'+g.settings.flash_swf_url+'" /><param name="flashvars" value="'+h+'" /><param name="wmode" value="transparent" /><param name="allowscriptaccess" value="always" /></object>';function j(){return document.getElementById(g.id+"_flash")}function i(){if(m++>5000){l({success:false});return}if(!e){setTimeout(i,1)}}i();k=f=null;g.bind("Flash:Init",function(){var p={},o,n=g.settings.resize||{};e=true;j().setFileFilters(g.settings.filters,g.settings.multi_selection);g.bind("UploadFile",function(q,r){var s=q.settings;j().uploadFile(p[r.id],c.buildUrl(s.url,{name:r.target_name||r.name}),{chunk_size:s.chunk_size,width:n.width,height:n.height,quality:n.quality||90,multipart:s.multipart,multipart_params:s.multipart_params,file_data_name:s.file_data_name,format:/\.(jpg|jpeg)$/i.test(r.name)?"jpg":"png",headers:s.headers})});g.bind("Flash:UploadProcess",function(r,q){var s=r.getFile(p[q.id]);if(s.status!=c.FAILED){s.loaded=q.loaded;s.size=q.size;r.trigger("UploadProgress",s)}});g.bind("Flash:UploadChunkComplete",function(q,s){var t,r=q.getFile(p[s.id]);t={chunk:s.chunk,chunks:s.chunks,response:s.text};q.trigger("ChunkUploaded",r,t);if(r.status!=c.FAILED){j().uploadNextChunk()}if(s.chunk==s.chunks-1){r.status=c.DONE;q.trigger("FileUploaded",r,{response:s.text})}});g.bind("Flash:SelectFiles",function(q,t){var s,r,u=[],v;for(r=0;r<t.length;r++){s=t[r];v=c.guid();p[v]=s.id;p[s.id]=v;u.push(new c.File(v,s.name,s.size))}if(u.length){g.trigger("FilesAdded",u)}});g.bind("Flash:SecurityError",function(q,r){g.trigger("Error",{code:c.SECURITY_ERROR,message:"Security error.",details:r.message,file:g.getFile(p[r.id])})});g.bind("Flash:GenericError",function(q,r){g.trigger("Error",{code:c.GENERIC_ERROR,message:"Generic error.",details:r.message,file:g.getFile(p[r.id])})});g.bind("Flash:IOError",function(q,r){g.trigger("Error",{code:c.IO_ERROR,message:"IO error.",details:r.message,file:g.getFile(p[r.id])})});g.bind("QueueChanged",function(q){g.refresh()});g.bind("FilesRemoved",function(q,s){var r;for(r=0;r<s.length;r++){j().removeFile(p[s[r].id])}});g.bind("StateChanged",function(q){g.refresh()});g.bind("Refresh",function(q){var r,s,t;j().setFileFilters(g.settings.filters,g.settings.multi_selection);r=document.getElementById(q.settings.browse_button);s=c.getPos(r,document.getElementById(q.settings.container));t=c.getSize(r);c.extend(document.getElementById(q.id+"_flash_container").style,{top:s.y+"px",left:s.x+"px",width:t.w+"px",height:t.h+"px"})});l({success:true})})}})})(plupload);(function(a){a.runtimes.BrowserPlus=a.addRuntime("browserplus",{getFeatures:function(){return{dragdrop:true,jpgresize:true,pngresize:true,chunks:true,progress:true,multipart:true}},init:function(g,i){var e=window.BrowserPlus,h={},d=g.settings,c=d.resize;function f(n){var m,l,j=[],k,o;for(l=0;l<n.length;l++){k=n[l];o=a.guid();h[o]=k;j.push(new a.File(o,k.name,k.size))}if(l){g.trigger("FilesAdded",j)}}function b(){g.bind("PostInit",function(){var m,k=d.drop_element,o=g.id+"_droptarget",j=document.getElementById(k),l;function p(r,q){e.DragAndDrop.AddDropTarget({id:r},function(s){e.DragAndDrop.AttachCallbacks({id:r,hover:function(t){if(!t&&q){q()}},drop:function(t){if(q){q()}f(t)}},function(){})})}function n(){document.getElementById(o).style.top="-1000px"}if(j){if(document.attachEvent&&(/MSIE/gi).test(navigator.userAgent)){m=document.createElement("div");m.setAttribute("id",o);a.extend(m.style,{position:"absolute",top:"-1000px",background:"red",filter:"alpha(opacity=0)",opacity:0});document.body.appendChild(m);a.addEvent(j,"dragenter",function(r){var q,s;q=document.getElementById(k);s=a.getPos(q);a.extend(document.getElementById(o).style,{top:s.y+"px",left:s.x+"px",width:q.offsetWidth+"px",height:q.offsetHeight+"px"})});p(o,n)}else{p(k)}}a.addEvent(document.getElementById(d.browse_button),"click",function(v){var t=[],r,q,u=d.filters,s;v.preventDefault();for(r=0;r<u.length;r++){s=u[r].extensions.split(",");for(q=0;q<s.length;q++){t.push(a.mimeTypes[s[q]])}}e.FileBrowse.OpenBrowseDialog({mimeTypes:t},function(w){if(w.success){f(w.value)}})});j=m=null});g.bind("UploadFile",function(n,k){var m=h[k.id],j={},l=n.settings.chunk_size,o,p=[];function r(s,u){var t;if(k.status==a.FAILED){return}j.name=k.target_name||k.name;if(l){j.chunk=s;j.chunks=u}t=p.shift();e.Uploader.upload({url:a.buildUrl(n.settings.url,j),files:{file:t},cookies:document.cookies,postvars:n.settings.multipart_params,progressCallback:function(x){var w,v=0;o[s]=parseInt(x.filePercent*t.size/100,10);for(w=0;w<o.length;w++){v+=o[w]}k.loaded=v;n.trigger("UploadProgress",k)}},function(w){var v,x;if(w.success){v=w.value.statusCode;if(l){n.trigger("ChunkUploaded",k,{chunk:s,chunks:u,response:w.value.body,status:v})}if(p.length>0){r(++s,u)}else{k.status=a.DONE;n.trigger("FileUploaded",k,{response:w.value.body,status:v});if(v>=400){n.trigger("Error",{code:a.HTTP_ERROR,message:"HTTP Error.",file:k,status:v})}}}else{n.trigger("Error",{code:a.GENERIC_ERROR,message:"Generic Error.",file:k,details:w.error})}})}function q(s){k.size=s.size;if(l){e.FileAccess.chunk({file:s,chunkSize:l},function(v){if(v.success){var w=v.value,t=w.length;o=Array(t);for(var u=0;u<t;u++){o[u]=0;p.push(w[u])}r(0,t)}})}else{o=Array(1);p.push(s);r(0,1)}}if(c&&/\.(png|jpg|jpeg)$/i.test(k.name)){BrowserPlus.ImageAlter.transform({file:m,quality:c.quality||90,actions:[{scale:{maxwidth:c.width,maxheight:c.height}}]},function(s){if(s.success){q(s.value.file)}})}else{q(m)}});i({success:true})}if(e){e.init(function(k){var j=[{service:"Uploader",version:"3"},{service:"DragAndDrop",version:"1"},{service:"FileBrowse",version:"1"},{service:"FileAccess",version:"2"}];if(c){j.push({service:"ImageAlter",version:"4"})}if(k.success){e.require({services:j},function(l){if(l.success){b()}else{i()}})}else{i()}})}else{i()}}})})(plupload);(function(b){function a(i,l,j,c,k){var e,d,h,g,f;e=document.createElement("canvas");e.style.display="none";document.body.appendChild(e);d=e.getContext("2d");h=new Image();h.onload=function(){var o,m,n;f=Math.min(l/h.width,j/h.height);if(f<1){o=Math.round(h.width*f);m=Math.round(h.height*f)}else{o=h.width;m=h.height}e.width=o;e.height=m;d.drawImage(h,0,0,o,m);g=e.toDataURL(c);g=g.substring(g.indexOf("base64,")+7);g=atob(g);e.parentNode.removeChild(e);k({success:true,data:g})};h.src=i}b.runtimes.Html5=b.addRuntime("html5",{getFeatures:function(){var g,d,f,e,c;d=f=e=c=false;if(window.XMLHttpRequest){g=new XMLHttpRequest();f=!!g.upload;d=!!(g.sendAsBinary||g.upload)}if(d){e=!!(File&&File.prototype.getAsDataURL);c=!!(File&&File.prototype.slice)}return{html5:d,dragdrop:window.mozInnerScreenX!==undefined||c,jpgresize:e,pngresize:e,multipart:e,progress:f}},init:function(e,f){var c={};function d(k){var h,g,j=[],l;for(g=0;g<k.length;g++){h=k[g];l=b.guid();c[l]=h;j.push(new b.File(l,h.fileName,h.fileSize))}if(j.length){e.trigger("FilesAdded",j)}}if(!this.getFeatures().html5){f({success:false});return}e.bind("Init",function(l){var p,n=[],k,o,h=l.settings.filters,j,m,g=document.body;p=document.createElement("div");p.id=l.id+"_html5_container";for(k=0;k<h.length;k++){j=h[k].extensions.split(/,/);for(o=0;o<j.length;o++){m=b.mimeTypes[j[o]];if(m){n.push(m)}}}b.extend(p.style,{position:"absolute",background:e.settings.shim_bgcolor||"transparent",width:"100px",height:"100px",overflow:"hidden",zIndex:99999,opacity:e.settings.shim_bgcolor?"":0});p.className="plupload html5";if(e.settings.container){g=document.getElementById(e.settings.container);g.style.position="relative"}g.appendChild(p);p.innerHTML='<input id="'+e.id+'_html5" style="width:100%;" type="file" accept="'+n.join(",")+'" '+(e.settings.multi_selection?'multiple="multiple"':"")+" />";document.getElementById(e.id+"_html5").onchange=function(){d(this.files);this.value=""}});e.bind("PostInit",function(){var g=document.getElementById(e.settings.drop_element);if(g){b.addEvent(g,"dragover",function(h){h.preventDefault()});b.addEvent(g,"drop",function(i){var h=i.dataTransfer;if(h&&h.files){d(h.files)}i.preventDefault()})}});e.bind("Refresh",function(g){var h,i,j;h=document.getElementById(e.settings.browse_button);i=b.getPos(h,document.getElementById(g.settings.container));j=b.getSize(h);b.extend(document.getElementById(e.id+"_html5_container").style,{top:i.y+"px",left:i.x+"px",width:j.w+"px",height:j.h+"px"})});e.bind("UploadFile",function(g,j){var n=new XMLHttpRequest(),i=n.upload,h=g.settings.resize,m,l=0;function k(o){var s="----pluploadboundary"+b.guid(),q="--",r="\r\n",p="";if(g.settings.multipart){n.setRequestHeader("Content-Type","multipart/form-data; boundary="+s);b.each(g.settings.multipart_params,function(u,t){p+=q+s+r+'Content-Disposition: form-data; name="'+t+'"'+r+r;p+=u+r});p+=q+s+r+'Content-Disposition: form-data; name="'+g.settings.file_data_name+'"; filename="'+j.name+'"'+r+"Content-Type: application/octet-stream"+r+r+o+r+q+s+q+r;l=p.length-o.length;o=p}n.sendAsBinary(o)}if(j.status==b.DONE||j.status==b.FAILED||g.state==b.STOPPED){return}if(i){i.onprogress=function(o){j.loaded=o.loaded-l;g.trigger("UploadProgress",j)}}n.onreadystatechange=function(){var o;if(n.readyState==4){try{o=n.status}catch(p){o=0}j.status=b.DONE;j.loaded=j.size;g.trigger("UploadProgress",j);g.trigger("FileUploaded",j,{response:n.responseText,status:o});if(o>=400){g.trigger("Error",{code:b.HTTP_ERROR,message:"HTTP Error.",file:j,status:o})}}};n.open("post",b.buildUrl(g.settings.url,{name:j.target_name||j.name}),true);n.setRequestHeader("Content-Type","application/octet-stream");b.each(g.settings.headers,function(p,o){n.setRequestHeader(o,p)});m=c[j.id];if(n.sendAsBinary){if(h&&/\.(png|jpg|jpeg)$/i.test(j.name)){a(m.getAsDataURL(),h.width,h.height,/\.png$/i.test(j.name)?"image/png":"image/jpeg",function(o){if(o.success){j.size=o.data.length;k(o.data)}else{k(m.getAsBinary())}})}else{k(m.getAsBinary())}}else{n.send(m)}});f({success:true})}})})(plupload);(function(a){a.runtimes.Html4=a.addRuntime("html4",{getFeatures:function(){return{multipart:true}},init:function(f,g){var d={},c,b;function e(l){var k,j,m=[],n,h;h=l.value.replace(/\\/g,"/");h=h.substring(h.length,h.lastIndexOf("/")+1);n=a.guid();k=new a.File(n,h);d[n]=k;k.input=l;m.push(k);if(m.length){f.trigger("FilesAdded",m)}}f.bind("Init",function(p){var h,x,v,t=[],o,u,m=p.settings.filters,l,s,r=/MSIE/.test(navigator.userAgent),k="javascript",w,j=document.body,n;if(f.settings.container){j=document.getElementById(f.settings.container);j.style.position="relative"}c=(typeof p.settings.form=="string")?document.getElementById(p.settings.form):p.settings.form;if(!c){n=document.getElementById(f.settings.browse_button);for(;n;n=n.parentNode){if(n.nodeName=="FORM"){c=n}}}if(!c){c=document.createElement("form");c.style.display="inline";n=document.getElementById(f.settings.container);n.parentNode.insertBefore(c,n);c.appendChild(n)}c.setAttribute("method","post");c.setAttribute("enctype","multipart/form-data");a.each(p.settings.multipart_params,function(z,y){var i=document.createElement("input");a.extend(i,{type:"hidden",name:y,value:z});c.appendChild(i)});b=document.createElement("iframe");b.setAttribute("src",k+':""');b.setAttribute("name",p.id+"_iframe");b.setAttribute("id",p.id+"_iframe");b.style.display="none";a.addEvent(b,"load",function(B){var C=B.target,z=f.currentfile,A;try{A=C.contentWindow.document||C.contentDocument||window.frames[C.id].document}catch(y){p.trigger("Error",{code:a.SECURITY_ERROR,message:"Security error.",file:z});return}if(A.location.href=="about:blank"||!z){return}var i=A.documentElement.innerText||A.documentElement.textContent;if(i!=""){z.status=a.DONE;z.loaded=1025;z.percent=100;if(z.input){z.input.removeAttribute("name")}p.trigger("UploadProgress",z);p.trigger("FileUploaded",z,{response:i});if(c.tmpAction){c.setAttribute("action",c.tmpAction)}if(c.tmpTarget){c.setAttribute("target",c.tmpTarget)}}});c.appendChild(b);if(r){window.frames[b.id].name=b.name}x=document.createElement("div");x.id=p.id+"_iframe_container";for(o=0;o<m.length;o++){l=m[o].extensions.split(/,/);for(u=0;u<l.length;u++){s=a.mimeTypes[l[u]];if(s){t.push(s)}}}a.extend(x.style,{position:"absolute",background:"transparent",width:"100px",height:"100px",overflow:"hidden",zIndex:99999,opacity:0});w=f.settings.shim_bgcolor;if(w){a.extend(x.style,{background:w,opacity:1})}x.className="plupload_iframe";j.appendChild(x);function q(){v=document.createElement("input");v.setAttribute("type","file");v.setAttribute("accept",t.join(","));v.setAttribute("size",1);a.extend(v.style,{width:"100%",height:"100%",opacity:0});if(r){a.extend(v.style,{filter:"alpha(opacity=0)"})}a.addEvent(v,"change",function(i){var y=i.target;if(y.value){q();y.style.display="none";e(y)}});x.appendChild(v);return true}q()});f.bind("Refresh",function(h){var i,j,k;i=document.getElementById(f.settings.browse_button);j=a.getPos(i,document.getElementById(h.settings.container));k=a.getSize(i);a.extend(document.getElementById(f.id+"_iframe_container").style,{top:j.y+"px",left:j.x+"px",width:k.w+"px",height:k.h+"px"})});f.bind("UploadFile",function(h,i){if(i.status==a.DONE||i.status==a.FAILED||h.state==a.STOPPED){return}if(!i.input){i.status=a.ERROR;return}i.input.setAttribute("name",h.settings.file_data_name);c.tmpAction=c.getAttribute("action");c.setAttribute("action",a.buildUrl(h.settings.url,{name:i.target_name||i.name}));c.tmpTarget=c.getAttribute("target");c.setAttribute("target",b.name);this.currentfile=i;c.submit()});f.bind("FilesRemoved",function(h,k){var j,l;for(j=0;j<k.length;j++){l=k[j].input;if(l){l.parentNode.removeChild(l)}}});g({success:true})}})})(plupload);

/*!
Math.uuid.js (v1.4)
http://www.broofa.com
mailto:robert@broofa.com

Copyright (c) 2009 Robert Kieffer
Dual licensed under the MIT and GPL licenses.

Usage: Math.uuid()
*/
Math.uuid = (function() {
  // Private array of chars to use
  var CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.split(''); 

  return function (len, radix) {
    var chars = CHARS, uuid = [];
    radix = radix || chars.length;

    if (len) {
      // Compact form
      for (var i = 0; i < len; i++) uuid[i] = chars[0 | Math.random()*radix];
    } else {
      // rfc4122, version 4 form
      var r;

      // rfc4122 requires these characters
      uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
      uuid[14] = '4';

      // Fill in random data.  At i==19 set the high bits of clock sequence as
      // per rfc4122, sec. 4.1.5
      for (var i = 0; i < 36; i++) {
        if (!uuid[i]) {
          r = 0 | Math.random()*16;
          uuid[i] = chars[(i == 19) ? (r & 0x3) | 0x8 : r];
        }
      }
    }

    return uuid.join('');
  };
})();

/*
 * Courtesy of PHPjs -- http://phpjs.org
 * @license GPL, version 2
 */
function basename (path, suffix) {
	var b = path.replace(/^.*[\/\\]/g, '');
	if (typeof(suffix) == 'string' && b.substr(b.length-suffix.length) == suffix) {
		b = b.substr(0, b.length-suffix.length);
	}
	return b;
}

function number_format( number, decimals, dec_point, thousands_sep ) {
	var n = number, c = isNaN(decimals = Math.abs(decimals)) ? 2 : decimals;
	var d = dec_point == undefined ? "," : dec_point;
	var t = thousands_sep == undefined ? "." : thousands_sep, s = n < 0 ? "-" : "";
	var i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
	
	return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function size_format (filesize) {
	if (filesize >= 1073741824) {
		filesize = number_format(filesize / 1073741824, 2, '.', '') + ' Gb';
	} else {
		if (filesize >= 1048576) {
			filesize = number_format(filesize / 1048576, 2, '.', '') + ' Mb';
		} else {
			filesize = number_format(filesize / 1024, 2, '.', '') + ' Kb';
		}
	}
	return filesize;
}
