/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, CloneObject:true, $A:true, $H:true,unescape:true,Asset:true,FloatingTips:true,head:true,IconGenerator:true */

/**
 * keeps the element posisiton in the center even when scroll/resizing
 */


Element.implement({
	keepCenter: function () {
		this.makeCenter();
		window.addEvent('scroll', function () {
			this.makeCenter();
		}.bind(this));
		window.addEvent('resize', function () {
			this.makeCenter();
		}.bind(this));
	},
	makeCenter: function () {
		var l = window.getWidth() / 2 - this.getWidth() / 2;
		var t = window.getScrollTop() + (window.getHeight() / 2 - this.getHeight() / 2);
		this.setStyles({left: l, top: t});
	}
});

/**
 * loading aninimation class, either inline next to an element or 
 * full screen
 */

var Loader = new Class({
	
	initialize: function (options) {
		this.spinners = {};			
	},
	
	getSpinner: function (inline, msg) {
		msg = msg ? msg : 'loading';
		if ($type(document.id(inline)) === false) {
			inline = false;
		}
		inline = inline ? inline : false;
		var target = inline ? inline : document.body;
		if (!this.spinners[inline]) {
			this.spinners[inline] = 'tmp';new Spinner(target, {'message': msg});
		}
		return this.spinners[inline];
	},
	
	start: function (inline, msg) {
		this.getSpinner(inline, msg).position().show();
	},
	
	stop: function (inline, msg, keepOverlay) {
		//dont keep the spinner once stop is called - causes issue when loading ajax form for 2nd time
		this.getSpinner(inline, msg).destroy();
		delete this.spinners[inline];
	}
});

/**
 * create the Fabrik name space
 */
(function () {
	if (typeof(Fabrik) === "undefined") {
		
		Fabrik = {};
		Fabrik.events = {};
		Fabrik.Windows = {};
		Fabrik.loader = new Loader();
		Fabrik.blocks = {};
		Fabrik.addBlock = function (blockid, block) {
			Fabrik.blocks[blockid] = block;
		};
		//was in head.ready but that cause js error for fileupload in admin when it wanted to 
		//build its window.
		Fabrik.iconGen = new IconGenerator({scale: 0.5});
		
		//events test: replacing window.addEvents as they are reset when you reload mootools in ajax window.
		// need to load mootools in ajax window otherwise Fabrik classes dont correctly load
		Fabrik.addEvent = function (type, fn) {
			if (!Fabrik.events[type]) {
				Fabrik.events[type] = [];
			}
			if (!Fabrik.events[type].contains(fn)) {
				Fabrik.events[type].push(fn);
			}
		};
		
		Fabrik.addEvents = function (events) {
			for (var event in events) {
				Fabrik.addEvent(event, events[event]);
			}
			return this;
		};
		
		Fabrik.fireEvent = function (type, args, delay) {
			var events = Fabrik.events;
			if (!events || !events[type]) {
				return this;
			}
			args = Array.from(args);
	
			events[type].each(function (fn) {
				if (delay) {
					fn.delay(delay, this, args);
				} else {
					fn.apply(this, args);
				}
			}, this);
			return this;
		};
	}
}());
	

