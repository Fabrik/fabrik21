var Fabrik_Utility = new Class({
			initialize: function( params, user, el_list, grp_list )
			{				
				this.params = params;								
				this.user = user;
				this.element_list = new Hash(el_list);
				this.group_list = grp_list;					
				this.form_obj = window[this.params.formid];
				
				window.addEvent('domready', this.initDomReady.bind(this));							
			},
			
			/*
			Get the form DOM element as a member variable and set up the fu.buttons array
			items in buttons array are named as you would expect, with the exceptions of
			res (reset) and del (delete).
			*/
			
			initFormElAndButtons: function(){
				this.form_el = $(this.params.formid);
				this.fab_act = this.form_el.getElement('div.fabrikActions');				
				this.button = new Object({'submit':$('fabrikSubmit'+this.params.fabrik)});				
				if(this.params.show_reset === 1) this.button.res = this.fab_act.getElement('input[name=Reset]');
				if(this.params.show_goback === 1) this.button.goback = this.fab_act.getElement('input[name=Goback]');
				if(this.params.show_apply === 1) this.button.apply = this.fab_act.getElement('input[name=apply]');
				if(this.params.show_copy === 1) this.button.copy = this.fab_act.getElement('input[name=Copy]');
				if(this.params.show_delete === 1) this.button.del = this.fab_act.getElement('input[name=delete]');
			},
			
			/*
			Get the plugin objects out of the form_#.formElements hash and stick them in the appropriate places in the element_list array			
			*/
			
			initElementObjects: function(){
				var table_name = "";
				var column_name = "";				
				for(table_name in this.element_list)
				{						
					for(column_name in this.element_list[table_name])
					{						
						var key = this.element_list[table_name][column_name].element_id;						
						this.element_list[table_name][column_name].fabrik_object = new Object();						
						this.element_list[table_name][column_name].fabrik_object = this.form_obj.formElements.get(key);
					}
				}				
			},
			
			/*
			Stuff that has to be done during/after/within the domready event
			*/			
			initDomReady: function(){
				this.initFormElAndButtons();
				this.initElementObjects();		
			},
			
			
			/*
			method = function in user_ajax.php
			request_options are normal options you pass to new Reqest({...}) mootools object, except leave off the url, as it's taken care of by the class.
			If you do set the url in the request options, it's treated as a query string and added to the end of the url stored with the class.
			
			*/
			
			userAjax: function(method, request_options)
			{
				var url = this.params.ajax_url + method;
				if(request_options == null)
				{
					request_options = new Object({'url':url});
				}else
				{				
					request_options.url = (typeof request_options.url == 'undefined') ? url : url + request_options.url;
				}
				
				return new Request(request_options);
			},
			
			/*
			Let's not bury ourselves in repeated if-else structures if we don't have to
			*/			
			
			isSingleGet: function(r_group)
			{
				if(r_group == 'array' || r_group == 'elements' || r_group == 'all') return false;
				else return true;
			},
			
			//
			// fu.getInput(...)			
			// gets a form input element (input, select, etc..)
			// joined elements are treated the same as un-joined elements (look over fu.element_list on a form with a join to see what's going on)
			// example:
			// fu.getElement('your_table','your_element').set('value','new value');
			//
			// if the elements are in a repeating group you can enter a value for repeat_group to specify which repeated-group to get the element from.
			// if repeat_group is left empty, the function returns elements from the first group.
			// possible values for repeat_group argument:
			//		'first' - looks to the first sub group for the element. is the same as leaving the argument empty. 			
			//		'last' - looks to the last sub group for the element. is the same as 'first' if the repeating group only has one sub group			
			//		'array' - instead of returning an element, returns an array of all the instances of the element from the sub groups.
			//		'elements' - returns a new Elements object (mootools extended array of elements). so you can do code like fu.getInput(table,el,'element').set('value','some value');
			//		 integer - integer value, 0 = first repeat group instance of element, 1 = second, etc..	
			

			getInput: function(table_name, element_name, repeat_group)
			{
					  
					  if(repeat_group == null || repeat_group == 'first')	
					  {
						  return $(this.element_list[table_name][element_name]['element_id']);
					  }else
					  {
						  var group_id = this.element_list[table_name][element_name]['group_id'];
						  
						  if(this.form_obj.duplicatedGroups[group_id] == null)
						  {
							  return $(this.element_list[table_name][element_name]['element_id']);
							  
						  }else
						  {
							 
							  var sub_count = $('group'+group_id).getElements('.fabrikSubGroup').length;
							  
							  if( $type(repeat_group) == 'number')
							  {
								  if(repeat_group == 0)
								  {
									  return $(this.element_list[table_name][element_name]['element_id']);
									  
								  }else
								  {
									  return $(this.element_list[table_name][element_name]['element_id']+'_'+repeat_group);
								  }
								  
							  }else if(repeat_group == 'last')
							  {
								  return $(this.element_list[table_name][element_name]['element_id']+'_'+(sub_count - 1));
								  
							  }else if(repeat_group == 'array' || repeat_group == 'all')
							  {
								  var element_array = [];
								  element_array.push($(this.element_list[table_name][element_name]['element_id']));
								  for(var x = 1; x < sub_count; x++)
								  {
									  element_array.push($(this.element_list[table_name][element_name]['element_id']+'_'+x));
								  }
								 
								  return element_array;
								  
							  }else if(repeat_group == 'elements')
							  {
								  var element_array = this.getInput(table_name,element_name,'array');
								  return new Elements(element_array);													  
							  }
						  }
						  						  
					  }
			},
			
			/*
			The old mis-named getInput function
			*/
				  
			getElement: function(table_name, element_name, repeat_group){
				return this.getInput(table_name,element_name,repeat_group);
			},
			
			/*
			gets div.fabrikElement parent of input element
			*/			

			getFabrikElement: function(table_name, element_name, repeat_group)
			{
				if(this.isSingleGet(repeat_group))
				{
					return this.getInput(table_name,element_name,repeat_group).getParent('div.fabrikElement');
										
				}else
				{ 
					var input_el_array = this.getInput(table_name,element_name,'array');					
					var index = "";
					
					if(repeat_group == 'array' || repeat_group == 'all')
					{						
						for(var x = 0; x < input_el_array.length; x++)
						{
							input_el_array[x] = input_el_array[x].getParent('div.fabrikElement');
						}
						
						return input_el_array;
					}else if(repeat_group == 'elements')
					{
						return new Elements(input_el_array).getParent('div.fabrikElement');						
					}
				}				
			},  
			
			//
			// fu.getLabel(...)
			//gets label element for the fabrik element
			//
			
				  getLabel: function(table_name, element_name, repeat_group){
					  
					  if(repeat_group == null || repeat_group == 'first')	
					  {
						  return $(this.element_list[table_name][element_name]['label_id']).getElement('label');
					  }else
					  {
						  var group_id = this.element_list[table_name][element_name]['group_id'];
						  
						  if(this.form_obj.duplicatedGroups[group_id] == null)
						  {
							  return $(this.element_list[table_name][element_name]['label_id']).getElement('label');
							  
						  }else
						  {
							  var repeating_groups = $('group'+group_id).getElements('.fabrikSubGroup');
							  var sub_count = repeating_groups.length;
						  
							  if( $type(repeat_group) == 'number')
							  {
								  if(repeat_group == 0)
								  {
									  return $(this.element_list[table_name][element_name]['label_id']).getElement('label');
									  
								  }else
								  {									  
									  return repeating_groups[repeat_group].getElement('.fabrikSubGroupElements ul').getElement('label[for='+this.element_list[table_name][element_name]['element_id']+'_'+repeat_group+']');

								  }
								  
							  }else if(repeat_group == 'last')
							  {
								   return $('group'+group_id).getLabelast('.fabrikSubGroup').getElement('.fabrikSubGroupElements ul').getElement('label[for='+this.element_list[table_name][element_name]['element_id']+'_'+(sub_count-1)+']');
								  
							  }else if(repeat_group == 'array' || repeat_group == 'all')
							  {
								  var label_el = [];				
								  label_el.push(repeating_groups[0].getElement('.fabrikSubGroupElements ul').getElement('label[for='+this.element_list[table_name][element_name]['element_id']+']'));				  
								  for(var x = 1; x < sub_count; x++)
								  {
	label_el.push(repeating_groups[x].getElement('.fabrikSubGroupElements ul').getElement('label[for='+this.element_list[table_name][element_name]['element_id']+'_'+x+']'));
								  }
								  return label_el;
								  
							  }else if(repeat_group == 'elements')
							  {
								  var label_el_array = this.getLabel(table_name,element_name,'array');
								  return new Elements(label_el_array);
								  
								  
							  }
						  }
						  						  
					  }
				  },
				  
				  /*				  
				  gets div.fabrikLabel parent of label element
				  */
				  
				  getFabrikLabel: function(table_name, element_name, repeat_group)
				  {  
					  if(this.isSingleGet(repeat_group))
					  {
						  return this.getLabel(table_name,element_name,repeat_group).getParent('div.fabrikLabel');					
					  }else
					  { 
						  var label_el_array = this.getLabel(table_name,element_name,'array');
						  if(repeat_group == 'array' || repeat_group == 'all')
						  {						
							  for(var x = 0; x < label_el_array.length; x++)
							  {
								  label_el_array[x] = label_el_array[x].getParent('div.fabrikLabel');
							  }
							  return label_el_array;
							  
						  }else if(repeat_group == 'elements')
						  {
							  return new Elements(label_el_array).getParent('div.fabrikLabel');						
						  }
					  }						  
				  },
				  
				  /*
				  gets li.fabrikElementContainer of fabrik element
				  */
				  
				  getFabrikElementContainer: function(table_name, element_name, repeat_group)
				  {					  
					  if(this.isSingleGet(repeat_group))
					  {
						  return this.getFabrikElement(table_name,element_name,repeat_group).getParent('li.fabrikElementContainer');					
					  }else
					  { 
						  var input_el_array = this.getFabrikElement(table_name,element_name,'array');
						
						  if(repeat_group == 'array' || repeat_group == 'all')
						  {						
							  for(var x = 0; x < input_el_array.length; x++)
							  {
								  input_el_array[x] = input_el_array[x].getParent('li.fabrikElementContainer');
							  }
							
							  return input_el_array;
							  
						  }else if(repeat_group == 'elements')
						  {
							  return this.getFabrikElement(table_name,element_name,'elements').getParent('li.fabrikElementContainer');
						  }
					  }	
					  
				  },
				  
				  /*
				  shorter version of above function
				  */
				  
				  getLi: function(table_name, element_name, repeat_group){					  
					 return this.getFabrikElementContainer(table_name, element_name, repeat_group);					  
				  },
				  
				  /*
				  adds fabrikHide class to div.fabrikElementContainer parent of element
				  */
				 
				
				hideElement: function(table_name, element_name, repeat_group)
				{
				      if(this.isSingleGet(repeat_group))
					  {
						  return this.getLi(table_name,element_name,repeat_group).addClass('fabrikHide');							
						  
					  }else
					  {						  
						  var container_el_array = this.getLi(table_name, element_name, 'array');
						  if(repeat_group == 'array' || repeat_group == 'all')
						  {
							  for(var x = 0; x < container_el_array.length; x++)
							  {
								  container_el_array[x] = container_el_array[x].addClass('fabrikHide');
							  }
							  return container_el_array;
							  
						  }else if(repeat_group == 'elements')
						  {
							  return new Elements(container_el_array).addClass('fabrikHide');							  
						  }
					  }						
				},
				
				  /*
				 	removes fabrikHide class from div.fabrikElementContainer parent of element
				  */
				 
				 
				showElement: function(table_name, element_name, repeat_group)
				{
				      if(this.isSingleGet(repeat_group))
					  {
						  return this.getLi(table_name,element_name,repeat_group).removeClass('fabrikHide');							
						  
					  }else
					  {						  
						  var container_el_array = this.getLi(table_name, element_name, 'array');
						  if(repeat_group == 'array' || repeat_group == 'all')
						  {
							  for(var x = 0; x < container_el_array.length; x++)
							  {
								  container_el_array[x] = container_el_array[x].removeClass('fabrikHide');
							  }
							  return container_el_array;
							  
						  }else if(repeat_group == 'elements')
						  {
							  return new Elements(container_el_array).removeClass('fabrikHide');							  
						  }
					  }						
				},				

			//
			// fu.getGroup(...)
			// if repeat_group argument is not entered, gets fabrikGroup fieldset/DD element (if tab template)
			//
			// with the subgroup argument, function returns specified fabrikSubGroup element from within the group specified by the group_identifier argument
			// possible values for repeat_group argument:
			//		'first' - gets first subgroup	
			//		'last' - gets last				
			//		'elements' - gets new Elements array of subgroups
			//		 integer - a number, 0 for first subgroup				
				  getGroup: function(group_identifier, repeat_group)
				  {					  
					  if(repeat_group == null || repeat_group == 'first')
					  {
						  if( $type(group_identifier) == 'number')
						  {
							  for(var x = 0; x < this.group_list.length; x++)
							  {
								  if(this.group_list[x]['id'] == group_identifier) return $('group'+this.group_list[x]['id']);
							  }
							  
						  }else if( $type(group_identifier) == 'string')
						  {
							  for(var x = 0; x < this.group_list.length; x++)
							  {
								  if(this.group_list[x]['name'].toLowerCase() == group_identifier.toLowerCase() || this.group_list[x]['label'].toLowerCase() == group_identifier.toLowerCase()) return $('group'+this.group_list[x]['id']);
							  }						
							  
						  }
					  }else
					  {

						  if( $type(group_identifier) == 'number')
						  {
							  for(var x = 0; x < this.group_list.length; x++)
							  {
								  if(this.group_list[x]['id'] == group_identifier)
								  {
									   var group_id = this.group_list[x]['id'];
									   var repeating_groups = $('group'+group_id).getElements('.fabrikSubGroup');
			 						   var sub_count = repeating_groups.length;
										if( $type(repeat_group) == 'number')
										{
											return repeating_groups[repeat_group];
											
										}else if(repeat_group == 'last')
										{
											return repeating_groups[sub_count-1];
				
											
										}else if(repeat_group == 'elements' || repeat_group == 'all')
										{
											return repeating_groups;
																							
										}

									  
								  }
							  }
							  
						  }else if( $type(group_identifier) == 'string')
						  {
							  for(var x = 0; x < this.group_list.length; x++)
							  {
								  if(this.group_list[x]['name'].toLowerCase() == group_identifier.toLowerCase() || this.group_list[x]['label'].toLowerCase() == group_identifier.toLowerCase())
								  {
									   var group_id = this.group_list[x]['id'];
									   var repeating_groups = $('group'+group_id).getElements('.fabrikSubGroup');
			 						   var sub_count = repeating_groups.length;
										if( $type(repeat_group) == 'number')
										{
											return repeating_groups[repeat_group];
											
										}else if(repeat_group == 'last')
										{
											return repeating_groups[sub_count-1];
				
											
										}else if(repeat_group == 'elements' || repeat_group == 'all')
										{
											return repeating_groups;
																							
										}


								  }
							  }						
							  
						  }						  
					  }
				  },	

			/*
			
			Returns an element list object from fu.element_list.
			
			The possible values for the identifier argument are less vague here than in other utility functions  (particularly functions in the fabrik utility extension).
			It needs to be equal to or part of the element's id or label.	
			*/				
	

			getListElement: function(element_identifier)
			{	
				var column_index = "";
				var list_el = null;
				
				this.element_list.each(function(table_group, table_name)
				{
					for(column_name in table_group)
					{
						if(table_group[column_name].element_id == element_identifier) list_el = this.element_list[table_name][column_name];
						else if(table_group[column_name].label == element_identifier) list_el = this.element_list[table_name][column_name];					
						else if(table_group[column_name].element_id.toLowerCase().contains(element_identifier.toLowerCase())) list_el = this.element_list[table_name][column_name];
						else if(table_group[column_name].label.toLowerCase().contains(element_identifier.toLowerCase())) list_el = this.element_list[table_name][column_name];
					}					
					
				}.bind(this));
				return list_el;
				
			},
			
			/*
			get the plugin object of a fabrik element			
			*/
			getElementObj: function(table_name, element_name)
			{
				return this.element_list[table_name][element_name].fabrik_object;				
			},				
			
			/*
			Call a function of the fabrik form object.
			*/
			

			doFormFunc: function(function_name, function_arguments)
			{					  
				 var form_func_result = this.form_obj[function_name].run(function_arguments, this.form_obj);
					  return form_func_result;
			},	
			
			/*
			Add a form plugin to the form object
			*/			

			addFormPlugin: function(event_name, func)
			{					  
				var form_plugin = new Object({event_name:null});	
				form_plugin[event_name] = func.bind(this);					
				this.form_obj.options.plugins.push(form_plugin);
			},
			
			/*
			old name of above function
			*/
			
			addPlugin: function(event_name, func)
			{	
			  this.addFormPlugin(event_name, func);				  
			}						  
		
		});