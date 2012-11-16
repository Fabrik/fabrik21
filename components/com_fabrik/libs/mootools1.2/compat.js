//ROB
Object.extend = $extend;
//end

// $$$ rob commnted this out in dev.fabrikar.com as it was giving errors in the array.slice part in ie8
$A = function(iterable, start, length) {
	if($type(iterable) === false) {
	iterable = [];
	}
	if (Browser.Engine.trident && $type(iterable) == 'collection') {
		start = start || 0;
		if (start < 0) start = iterable.length + start;
		length = length || (iterable.length - start);
		var array = [];
		for (var i = 0; i < length; i++) array[i] = iterable[start++];
		return array;
	}
	start = (start || 0) + ((start < 0) ? iterable.length : 0);
	var end = ((!$chk(length)) ? iterable.length : length) + start;
	return Array.prototype.slice.call(iterable, start, end);
};

(function() {
	var natives = [Array, Function, String, RegExp, Number];
	for (var i = 0, l = natives.length; i < l; i++) natives[i].extend = natives[i].implement;
})();

window.extend = document.extend = function(properties) {
	for (var property in properties) this[property] = properties[property];
};

window[Browser.Engine.name] = window[Browser.Engine.name + Browser.Engine.version] = true;

window.ie = window.trident;
window.ie6 = window.trident4;
window.ie7 = window.trident5;

Class.empty = $empty;

//legacy .extend support

Class.prototype.extend = function(properties) {
	properties.Extends = this;
	return new Class(properties);
};

Array.implement({

	copy: function(start, length) {
		return $A(this, start, length);
	}

});

Array.alias({erase: 'remove', combine: 'merge'});

Function.extend({

	bindAsEventListener: function(bind, args) {
		return this.create({'bind': bind, 'event': true, 'arguments': args});
	}

});

Function.empty = $empty;

Hash.alias({getKeys: 'keys', getValues: 'values', has: 'hasKey', combine: 'merge'});
var Abstract = Hash;

Element.extend = Element.implement;

Elements.extend = Elements.implement;

Element.alias({'dispose': 'remove', 'getLast': 'getLastChild'});



Event.keys = Event.Keys;

Element.implement({

	getFormElements: function() {
		return this.getElements('input, textarea, select');
	},
	
	effect: function(property, options) {
		return new Fx.Tween(this, $extend({property: property}, options));
	},
	
	effects: function(options) {
		return new Fx.Morph(this, options);
	},
	
		filterByTag: function(tag) {
		return this.filter(tag);
	},

	filterByClass: function(className) {
		return this.filter('.' + className);
	},

	filterById: function(id) {
		return this.filter('#' + id);
	},

	filterByAttribute: function(name, operator, value) {
		return this.filter('[' + name + (operator || '') + (value || '') + ']');
	},
	
   getValue: function() {
       return this.get('value');
   },
   //needed for IE8
   getSelected: function() {
  	 return $(this).getElements('option').filter(function(option) {
  	 return option.selected;
  	 });
  },
   
	replaceWith: function(el) {
		el = $(el);
		this.parentNode.replaceChild(el, this);
		return el;
	},
	
	removeElements: function() {
		return this.dispose();
	},
	
	getText: function() {
		return this.get('text');
	},

	setText: function(text) {
		return this.set('text', text);
	},

	setHTML: function() {
		return this.set('html', arguments);
	},
	
	getHTML: function() {
		return this.get('html');
	},

	getTag: function() {
		return this.get('tag');
	},
	
	setOpacity: function(op) {
		return this.set('opacity', op);
	},
	
	getSize: function() {

		//if (isBody(this)) return this.getWindow().getSize();
		//return {x: this.offsetWidth, y: this.offsetHeight};

		if((/^(?:body|html)$/i).test(this.tagName)) {
			return this.getWindow().getSize();
		}else{
			return {
				'x': this.offsetWidth, 'y': this.offsetHeight,
				'scroll': {'x': this.scrollLeft, 'y': this.scrollTop},
				'size': {'x': this.offsetWidth, 'y': this.offsetHeight},
				'scrollSize': {'x': this.scrollWidth, 'y': this.scrollHeight}
			};
		}
	},
	
	clone: function(contents, keepid) {
		//tmp till we use 1.2.3 - clone wth ie8 &mt1.2 was wonkey
		contents = contents !== false;
		var props = {input: 'checked', option: 'selected', textarea: (Browser.Engine.webkit && Browser.Engine.version < 420) ? 'innerHTML' : 'value'};
		var clone = this.cloneNode(contents);
		var clean = function(node, element) {
			if (!keepid) node.removeAttribute('id');
			if (Browser.Engine.trident) {
				node.clearAttributes();
				node.mergeAttributes(element);
				node.removeAttribute('uid');
				if (node.options) {
					var no = node.options, eo = element.options;
					for (var j = no.length; j--;) no[j].selected = eo[j].selected;
				}
			}
			var prop = props[element.tagName.toLowerCase()];
			if (prop && element[prop]) node[prop] = element[prop];
		};

		if (contents) {
			var ce = clone.getElementsByTagName('*');
			te = this.getElementsByTagName('*');
			for (var i = ce.length; i--;) clean(ce[i], te[i]);
		}
		clean(clone, this);
		return $(clone);
	}

});

Elements.implement({
    // I would actually consider this a bug
    // Also I'm sure there's a more consistant way than this to implement it
    empty: function() {
        this.each(function(element) {
            element.empty();
        });
    }
});

Object.toQueryString = Hash.toQueryString;

var XHR = new Class({

	Extends: Request,

	options: {
		update: false
	},

	initialize: function(url, options) {
		this.parent(options);
		this.url = url;
	},

	request: function(data) {
		return this.send(this.url, data || this.options.data);
	},

	send: function(url, data) {
		if (!this.check(arguments.callee, url, data)) return this;
		return this.parent({url: url, data: data});
	},

	success: function(text, xml) {
		text = this.processScripts(text);
		if (this.options.update) $(this.options.update).empty().set('html', text);
		this.onSuccess(text, xml);
	},
	
	failure: function() {
		this.fireEvent('failure', this.xhr);
	}

});

//test
//var Ajax = XHR;

JSON.Remote = new Class({

	options: {
		key: 'json'
	},

	Extends: Request.JSON,

	initialize: function(url, options) {
		this.parent(options);
		this.onComplete = $empty;
		this.url = url;
	},

	send: function(data) {
		if (!this.check(arguments.callee, data)) return this;
		return this.parent({url: this.url, data: {json: Json.encode(data)}});
	},
	
	failure: function() {
		this.fireEvent('failure', this.xhr);
	}

});

Fx.implement({

	custom: function(from, to) {
		return this.start(from, to);
	},

	clearTimer: function() {
		return this.cancel();
	},
	
	stop: function() {
		return this.cancel();
	}

});

Fx.Base = Fx;

Fx.Style = function(element, property, options) {
	return new Fx.Tween(element, $extend({property: property}, options));
};


Fx.Styles = Fx.Morph;



Native.implement([Element, Document], {

	getElementsByClassName: function(className) {
		return this.getElements('.' + className);
	},

	getElementsBySelector: function(selector) {
		return this.getElements(selector);
	}

});


var $E = function(selector, filter) {
	return ($(filter) || document).getElement(selector);
};

var $ES = function(selector, filter) {
	return ($(filter) || document).getElements(selector);
};

var Json = JSON;

JSON.toString = JSON.encode;
JSON.evaluate = JSON.decode;

Cookie.set = function(key, value, options) {
	return new Cookie(key, options).write(value);
};

Cookie.get = function(key) {
	return new Cookie(key).read();
};

Cookie.remove = function(key, options) {
	return new Cookie(key, options).dispose();
};

///more
/*
Fx.Scroll = new Class({

	Extends: Fx
})
	
Fx.Scroll.implement({

	scrollTo: function(y, x) {
		return this.start(y, x);
	}

});
*/


//robs more

Fx.Scroll.implement({

	scrollTo: function(y, x) {
		return this.start(y, x);
	}
});
var Options = new Class({

	/*
	Property: setOptions
		sets this.options

	Arguments:
		defaults - object; the default set of options
		options - object; the user entered options. can be empty too.

	Note:
		if your Class has <Events> implemented, every option beginning with on, followed by a capital letter (onComplete) becomes an Class instance event.
	*/

	setOptions: function() {
		this.options = $merge.apply(null, [this.options].extend(arguments));
		if (this.addEvent) {
			for (var option in this.options) {
				if ($type(this.options[option] == 'function') && (/^on[A-Z]/).test(option)) this.addEvent(option, this.options[option]);
			}
		}
		return this;
	}

});


/*
MooTools 1.2 Custom Backwards-Compatibility Library
By David Isaacson
Portions from Mootools 1.2 by the MooTools production team (http://mootools.net/developers/)
Copyright (c) 2006-2007 Valerio Proietti (http://mad4milk.net/)
Copyright (c) 2008 Siafoo.net

Load after Mootools Core and More and both compatibility files
*/

function $extend(original, extended) {
    if (!extended) {extended=original; original=this;}  // This line added
    for (var key in (extended || {})) original[key] = extended[key];
    return original;
}

Drag.Base = Drag;





Hash.implement({
    remove: function(key) {
        return this.erase(key);
    }
});

Hash.Cookie.implement({
    
    remove: function(key) {
        var value = this.hash.erase(key);
        if (this.options.autoSave) this.save();
        return value;
    }
});


// Completely broken in mootools-core-compat.js
XHR.implement({
    
    initialize: function(options) {
        this.parent(options);
        this.transport = this.xhr;
    }
});

var Ajax = new Class({
    Extends: XHR,
    
    initialize: function(url, options) {
        this.url = url;
        this.parent(options);
    }
    
});

/* For further information, read http://www.siafoo.net/article/62 */
