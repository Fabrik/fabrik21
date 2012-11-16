
var fbVisCoverflow = new Class({
	initialize: function(json, options) {
		json = eval(json);
		this.options = Object.extend({
		}, options || {});
		
		window.addEvent('domready', function() {

			widget = Runway.createOrShowInstaller(
			    document.getElementById("coverflow"),
			    {
			        // examples of initial settings
			        // slideSize: 200,
			        // backgroundColorTop: "#fff",
			        
			        // event handlers
			        onReady: function() {
			            widget.setRecords(json);
			        }
			    }
			);
			
		}.bind(this))
		
	}

});