/**
 * @author Robert
*/

var fbImage = FbFileElement.extend({
	initialize: function(element, options) {
		//this.parent(element, options);
		this.folderlist = [];
		this.setOptions(element, options);
		this.plugin = 'image';
		this.element = $(element);
		this.options.rootPath = options.rootPath;
		if (options.editable) {
			this.getMyElements();
			
			this.folderlist = options.folderlist;
			this.imageFolderList = [];

			this.selectedImage = '';
			if (this.imageDir) {
				if (this.imageDir.options.length !== 0) {
					this.selectedImage = this.imageDir.get('value');
				}
				this.imageDir.addEvent('change', this.showImage.bindAsEventListener(this));
			}
			if (this.options.canSelect == true) {
				this.addEvent('onBrowse', this.changeFolder);
				this.ajaxFolder();
				this.element = this.hiddenField;
				this.selectedFolder = this.getFolderPath();
			}
		}
	},

	getMyElements : function() {
		var element = this.options.element;
		this.image = $(element).findClassUp('fabrikSubElementContainer').getElement('.imagedisplayor');
		this.folderDir = $(element).findClassUp('fabrikSubElementContainer').getElement('.folderselector');
		this.imageDir = $(element).findClassUp('fabrikSubElementContainer').getElement('.imageselector');
		// this.hiddenField is set in FbFileElement
	},

	cloned : function(c) {
		this.renewChangeEvents();
		this.getMyElements();
		this.ajaxFolder();
	},

	hasSubElements : function() {
		return true;
	},

	getFolderPath : function() {
		return this.options.rootPath + this.folderlist.join('/');
	},

	changeFolder: function(e) {
		var folder = this.imageDir;
		this.selectedFolder = this.getFolderPath();
		folder.empty();
		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikimage&method=ajax_files';
		var myAjax = new Ajax(url, { method:'post',
		'data':{'folder':this.selectedFolder}, 
			
		onComplete: function(r) {
			var newImages = eval(r);
			newImages.each(function(opt) {
				folder.adopt(
					new Element('option', {'value':opt.value}).appendText(opt.text)
				);
			});
			this.showImage();
		}.bind(this)}).request();
	},
	
	showImage: function(e) {
		if(this.imageDir) {
			if(this.imageDir.options.length === 0) {
				this.image.src = '';
				this.selectedImage = '';
			}else{
				this.selectedImage = this.imageDir.get('value');
				this.image.src = this.options.liveSite + this.selectedFolder + '/' + this.selectedImage;
			}
			this.hiddenField.value =  this.getValue();
		}
	},
	
	getValue: function()
	{
  	return this.folderlist.join('/') + '/' + this.selectedImage;
  }
});