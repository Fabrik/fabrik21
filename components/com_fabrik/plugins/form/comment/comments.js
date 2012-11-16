var FabrikComment = new Class({
	getOptions: function() {
		return {
			'formid':0,
			'rowid':0,
			'livesite':''
		};
	},

	initialize: function(element, options, lang) {
		this.element = $(element);
		if($type(this.element) === false) {
			return;
		}
   	this.lang = Object.extend(
   	{'prompt':'Type a comment here',
   	'entercomment':'Please enter a comment before posting',
  	 	'entername':'Please enter your name before posting',
  	 	'enteremail':'Please enter your email address before posting'
   	}
   	, lang || {});
		this.setOptions(this.getOptions(), options);
		this.fx = {};
		this.fx.toggleForms = $H();
		this.spinner = new Element('img', {'styles':{'display':'none'},	'src':this.options.livesite+'media/com_fabrik/images/ajax-loader.gif'});
		this.doAjaxComplete = this.ajaxComplete.bindAsEventListener(this);
		this.doAjaxDeleteComplete = this.deleteComplete.bindAsEventListener(this);
		this.saveCommentEvent = this.saveComment.bindAsEventListener(this);
		this.ajax = {};
		var url = this.options.liveSite + 'index.php';
		this.ajax.deleteComment = new Ajax(url, 
			{
			'method':'get',
			'data':{
				'option':'com_fabrik',
				'format':'raw',
				'controller':'plugin',
				'task':'pluginAjax',
				'plugin':'comment',
				'method':'deleteComment',
				'g':'form',
				'formid':this.options.formid,
				'rowid':this.options.rowid
			},
			'onComplete':this.doAjaxDeleteComplete
			});
	 	this.ajax.updateComment = new Ajax(url + '?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&plugin=comment&method=updateComment&g=form', 
		{
			'method':'post',
			'data':{
				'formid':this.options.formid,
				'rowid':this.options.rowid
			}
		});
		this.watchReply();
		this.watchInput();
	},
    
    ajaxComplete: function(d) {
			d = Json.evaluate(d);
			var depth = (d.depth.toInt() * 20) + 'px';
			var id = 'comment_' + d.id;
			var li = new Element('li', {'id':id, styles:{'margin-left':depth}}).setHTML(d.content);
			if(this.currentLi.getTag() === 'li') {
				li.injectAfter(this.currentLi);
			}else{
				li.injectInside(this.currentLi);
			}
		var fx = new Fx.Style(li, 'opacity', {duration:5000});
		fx.set(0);
		fx.start(0, 100);

		this.watchReply();
		if($type(d.message) !== false) {
			alert(d.message.title, d.message.message);
		}
		//for update
		if(this.spinner) {
			this.spinner.setStyle('display', 'none');
		}
		this.watchInput();
		this.updateDigg();
	},

	// ***************************//
	// CAN THE LIST BE ADDED TO //
	// ***************************//
    
  watchInput: function()
  {
  	var url = this.options.liveSite + 'index.php';
  	
  	this.ajax.addComment = new Ajax(url, 
 		{
  		'method':'get',
 			'data':
			{
				'option':'com_fabrik',
				'format':'raw',
				'controller':'plugin',
				'task':'pluginAjax',
				'plugin':'comment',
				'method':'addComment',
				'g':'form',
				'formid':this.options.formid,
				'rowid':this.options.rowid
			},
 			'onComplete':this.doAjaxComplete
 		});
 		
  	this.element.getElements('.replyform').each(function(f) {
  		var input = f.getElement('textarea');
  		if(!input) {
	   		return;
	   	}
			f.getElement('input[type=button]').addEvent('click', this.doInput.bindAsEventListener(this));
  		input.addEvent('click', this.testInput.bindAsEventListener(this));
  		
			(this.spinner).injectAfter(this.element.getElement('input[type=button]'));
  	}.bind(this));
  },
    
	testInput: function(e) {
   	var event = new Event(e);
   	if($(event.target).get('value') === this.lang.prompt) {
   		$(event.target).value = '';
   	}
   },
   
   updateDigg:function() {
   if($type(this.digg) !== false) {
  	 this.digg.removeEvents();
  	 this.digg.addEvents();
   }
	},
   
  //check details and then submit the form  
	doInput: function(e)
   {
		var event = new Event(e);
		this.spinner.injectAfter($(event.target));
		var replyform = $(event.target).findClassUp('replyform');
		if(replyform.id === 'master-comment-form') {
			var lis = this.element.getElement('ul').getElements('li');
			if(lis.length > 0) {
				this.currentLi = lis.pop();
			}else{
				this.currentLi = this.element.getElement('ul');
			}
		}else{
			this.currentLi = replyform.findUp('li');
		}
		
	 	if(e.type === 'keydown') {
	 		if(e.keyCode.toInt() !== 13) {
		 		this.spinner.setStyle('display', 'none');
		 		return;
	 		}
		}
   	this.spinner.setStyle('display', '');
		var v = replyform.getElement('textarea').get('value');
 	 	if(v === '') {
 	 		this.spinner.setStyle('display', 'none');
 	 		alert(this.lang.entercomment);
 	 		return;
 	 	}
 	 	event.stop();
 	 	var name = replyform.getElement('input[name=name]');
 	 	if(name) {
 	 		var namestr = name.get('value');
 	 		if(namestr === '') {
 	 			this.spinner.setStyle('display', 'none');
 	 			alert(this.lang.entername);
 	 			return;
 	 		}
 	 		this.ajax.addComment.options.data.name = namestr;
 	 	}
		
		var comment_plugin_notify = replyform.getElements('input[name^=comment-plugin-notify]').filter(function(i) {
		return i.checked;
		});
			
 	 	var email = replyform.getElement('input[name=email]');
 	 	if(email) {
 	 		var emailstr = email.get('value');
 	 		if(emailstr == '') {
 	 			this.spinner.setStyle('display', 'none');
 	 			alert(this.lang.enteremail);
 	 			return;
 	 		}
 	 	}
		var replyto = replyform.getElement('input[name=reply_to]').get('value');
		if(replyto === '') {
			replyto = 0;
		}
		if(replyform.getElement('input[name=email]')) {
			this.ajax.addComment.options.data.email = replyform.getElement('input[name=email]').get('value');
		}
		this.ajax.addComment.options.data.renderOrder = replyform.getElement('input[name=renderOrder]').get('value');
		if(replyform.getElement('select[name=rating]')) {
			this.ajax.addComment.options.data.rating = replyform.getElement('select[name=rating]').get('value');
		}
		if(replyform.getElement('input[name^=annonymous]')) {
			var sel = replyform.getElements('input[name^=annonymous]').filter(function(i) {
					return i.checked == true;
			});
			this.ajax.addComment.options.data.annonymous = sel[0].get('value');
		}

   	this.ajax.addComment.options.data.reply_to = replyto;
   	this.ajax.addComment.options.data.comment = v;
    this.ajax.addComment.request();
   
    this.element.getElement('textarea').value = '';
   },
   
   saveComment: function(div)
   {
	   var id = div.findClassUp('comment').id.replace('comment-', '');
	   this.ajax.updateComment.options.data.comment_id = id;
			if ($type(comment_plugin_notify) !== false) {
				this.ajax.updateComment.options.data.comment_plugin_notify = comment_plugin_notify.get('value');
			}
	   this.ajax.updateComment.options.data.comment = div.getText();
	   this.ajax.updateComment.request();
	   this.updateDigg();
   },
   
  //toggle fx the reply forms - recalled each time a comment is added via ajax
	watchReply: function() {
   	this.element.getElements('.replybutton').each(function(a) {
   		a.removeEvents();
   		var commentform = a.getParent().getParent().getNext();
   		if($type(commentform) === false) {
   			//wierd ie7 ness?
   			commentform = a.findClassUp('comment').getElement('.replyform');
   		}
   		if($type(commentform)!== false) {
	   		var li = a.findClassUp('comment').findUp('li');
	   		if(window.ie) {
	   			var fx = new Fx.Slide(commentform, 'opacity', {duration:5000});
	   			
	   		}else{
		   		if(this.fx.toggleForms.hasKey(li.id)) {
		   			var fx = this.fx.toggleForms.get(li.id);
		   		}else{
		   			var fx = new Fx.Slide(commentform, 'opacity', {duration:5000});
		   			this.fx.toggleForms.set(li.id, fx);
		   		}
	   		}
	   		
				fx.hide();
	   		a.addEvent('click', function(e) {
	   			e = new Event(e).stop();
	   			fx.toggle();
	   		}.bind(this));
			}
   	}.bind(this));
   	//watch delete comment buttons
   	this.element.getElements('.del-comment').each(function(a) {
   		a.removeEvents();
   		a.addEvent('click', function(e) {
   			var event = new Event(e);
   			this.ajax.deleteComment.options.data.comment_id = $(event.target).findClassUp('comment').id.replace('comment-', '');
   			this.ajax.deleteComment.request();
   			this.updateDigg();
   			event.stop();
   		}.bind(this));
   		}.bind(this)
   	);
   	//if admin watch inline edit
   	if(this.options.admin) {
   		
   		this.element.getElements('.comment-content').each(function(a) {
	   		a.removeEvents();
	   		a.addEvent('click', function(e) {
	   			var event = new Event(e);
	   			$(e.target).inlineEdit({'defaultval':'','type':'textarea', 'onComplete':this.saveCommentEvent});
	   			var c = $(e.target).getParent();
	   			var commentid = c.id.replace('comment-', '');
	   			var url = this.options.liveSite + 'index.php';
	  	  	new Ajax(url, 
	  	 		{
	  	 		'method':'get',
	  	 			'data':
	  			{
	  				'option':'com_fabrik',
	  				'format':'raw',
	  				'controller':'plugin',
	  				'task':'pluginAjax',
	  				'plugin':'comment',
	  				'method':'getEmail',
	  				'commentid':commentid,
	  				'g':'form',
	  				'formid':this.options.formid,
	  				'rowid':this.options.rowid
	  			},
	  				'onComplete':function(r) {
	  				c.getElements('.info').dispose();
	  				new Element('span', {'class':'info'}).setHTML(r).injectInside(c);
	  			}.bind(this)
	  	 		}).request();
	  	  	
	   			event.stop();
	   		}.bind(this));
	   	}.bind(this));
   	}
	},
	
	deleteComplete: function(r) {
		var c = $('comment_' + r);
		var fx = c.effects({duration: 1000, transition: Fx.Transitions.Quart.easeOut});
		fx.start({'opacity':0,'height':0}).chain(
				function() {
				c.remove();
				}
		);
	}
});

FabrikComment.implement(new Events);
FabrikComment.implement(new Options);