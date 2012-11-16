/**
 * creates a Mock event to be used with fire event
 * @param Element target an element to set as the target of the event - not required
 *  @param string type the type of the event to be fired. Will not be used by IE - not required.
 *
 */
Event.Mock = function(target,type){
var e = window.event;
type = type || 'click';

if (document.createEvent){
    e = document.createEvent('HTMLEvents');
    e.initEvent(
        type, //event type
        false, //bubbles - set to false because the event should like normal fireEvent
        true //cancelable
    );
}
e = new Event(e);
e.target = target;
return e;
}

function CloneObject(what, recursive, asreference) {
	if($type(what) != 'object')
      return what;
	var h = $H(what);
	h.each(function(v, k) {
		if($type(v) === 'object' && recursive === true && !asreference.contains(k)) {
			this[k] = new CloneObject(v, recursive, asreference);
		}else{
			this[k] = v;
		}
	}.bind(this));
	return this;
}

var FbAsset = Asset.extend({

		// only loads in the script if its not already included in the document head
		javascriptchecked: function(domain, source, properties) {
			var scripturl = domain + source;
			var found = document.getElements('script').some(function(s) {
				return  (scripturl == s.src);
			});
			if (found) {
				return;
			}
			// $$$ rob need full url for loading in sef urls.
			//var ok = new Asset.javascript(source, properties);
			var ok = new Asset.javascript(scripturl, properties);
	}
});

String.extend({
	
	toObject:function()
	{
		var o = {};
		this.split('&').each(function(pair) {
			var b = pair.split('=');
			o[b[0]] = b[1];
		});
		return o;
	}
});

(function(){

Element.extend({
	
	within: function(p) {
		var parenttest = this;
		while(parenttest.parentNode != null) {
			if(parenttest == p) {
				return true;
			}
			parenttest = parenttest.parentNode;
		}
		return false;
	},
	
	cloneWithIds:function(c) {
		return this.clone(c, true);
	},
	
	down: function(expression, index) {
	    var descendants = this.getChildren();
		if (arguments.length == 0) return descendants[0];
	    return descendants[index];
    },
	
	up: function(index) {
		index = index ? index : 0;
		var el = this;
		for (i=0;i<=index;i++) {
			el = el.getParent();
		}
		return el;
	},
	
	findUp: function(tag) {
		if(this.getTag() == tag)
			return this;
		var el = this;
		while(el && el.getTag() != tag) {
			el = el.getParent();
		}
		return el;
	},
	
		
	findClassUp: function(classname) {
		if(this.hasClass(classname)) {
			return this;
		}
		var el = $(this);
		while(el && !el.hasClass(classname)) {
			if($type(el.getParent()) != 'element') {
				return false;
			}
			el = el.getParent();
		}
		return el;
	},
	
	toggle: function() {
		if(this.style.display == 'none') {
			this.setStyles({'display':'block'});
		}else{
			this.setStyles({'display':'none'});
		}		
	},
	
	hide: function() {
		this.setStyles({'display':'none'});
	},
	
	show: function(mode) {
		this.setStyles({'display':$pick(mode, 'block')});
	},
	
	//x, y = mouse location
	mouseInside: function(x, y) {
		var coords = this.getCoordinates();
		var elLeft = coords.left;
		var elRight =  coords.left + coords.width;
		var elTop = coords.top;
		var elBottom = coords.bottom;
		if( x >= elLeft && x <= elRight) {
			if( y >= elTop && y <= elBottom) {
				return true;
			}
		}
		return false;
	}
,
	
	getRealOffsetParent: function(){
		var element = this;
		if (isBody(element)) return null;
		while ((element = element.parentNode) && !isBody(element)){
			if ($(element).getStyle('position') == 'relative') return element;
		}
		return null;
	}
});

function isBody(element){
	return (/^(?:body|html)$/i).test(element.tagName);
};

})();

/**
 * Misc. functions, nothing to do with Mootools ... we just needed
 * some common js include to put them in!
 */

function fconsole(thing) {
	if (typeof(window["console"]) != "undefined") {
		console.log(thing);
	}
}