/*
This extension to the fabrik utility class has function for making collections out of the fu.element_list hash, making collections of plugin objects, and having collection of
plugin objects call functions while recording the outcome of those function calls.


The construction of the collections is handled by the sortElementList function. It's pretty wide open in terms of the arguments it can take and the collections it will return. It can take a single character as an argument and will return all the elements that have that letter in their table name, element id, label, group name or group label, although it checks for straight equivalencies between ids and the passed arguments first. 
*/

var Fabrik_Utility_Ext = new Class({
	
			Extends: Fabrik_Utility,
			initialize: function( params, user, el_list, grp_list )
			{
				this.parent(params, user, el_list, grp_list);		
			},		

			getElNames: function(long_col_name)
			{
				var bits = long_col_name.split("___");
				var el_info = new Object({
					column: bits.pop(),
					table: bits.pop()									
				});
				el_info['id'] = el_info['table'] + "___" + el_info['column'];
				return el_info;
			},
			
			/*
			When items are pulled out of the element_list into different collections, their key names are just the column name of the element. This is an ambiguous identifier outside of the
			structure of the fu.element_list associative array, so this function returns a hash with the table names padded on on to the column name for the key values.			
			*/

			padTableName: function (assoc_list)
			{
				
				var new_list = new Hash();
				var column_index = "";
				for(column_index in assoc_list)
				{
					var new_key = assoc_list[column_index].table + "___" + column_index;
					new_list.set(new_key, assoc_list[column_index]);
				}
				return new_list;
			},
			
			/*
			return a hash of element list objects			
			identifier - if group_or_table argument = 'group', identifier is tested against group_id, group name and group label
			if group_or_table = 'table', identifier is tested for match against table name (why not store table id...?)
			
			if group_or_table = null (left blank), functions tests all the different values for a match, but with a specific order. The function gives precedence to matching with plugin name first and then element_id, 
			then group_name/title, and then a loose table_name/column_name match. Although you'd have to be intentionally vague (ie just a piece of an element's id or a part of a table's/group's name) to not get list of element's you expect, the function errs on the side of returning lists regardless of the quality of the input, instead of trying to make a specific list as quickly as possible.
			
			*/			
			
			sortElementList: function(identifier, group_or_table)
			{
				var sorted_list = new Hash();				
				
				if(group_or_table == 'group')
				{					
						this.element_list.each(function(table_group, table_name)
						{
							sorted_list.extend($H(table_group).filter(function(element, column_name)
							{
								if( $type(identifier) == 'number')
								{
									return element.group_id == identifier;
								}else
								{
									var group_match = (element.group_name == identifier || element.group_title == identifier);
									if(group_match) return true;
									else return (element.group_name.toLowerCase().contains(identifier.toLowerCase()) || element.group_title.toLowerCase().contains(identifier.toLowerCase()));
								}
							}));
						});	
					
				}else if(group_or_table == 'table')
				{
						this.element_list.each(function(table_group, table_name)
						{
							sorted_list.extend($H(table_group).filter(function(element, column_name)
							{
								var table_match = (table_name == identifier);
								if(table_match) return true;
								else return (element.table.toLowerCase().contains(identifier.toLowerCase()));
							}));
						});	
					
				}else if(group_or_table == null)
				{
						this.element_list.each(function(table_group, table_name)
						{
							sorted_list.extend($H(table_group).filter(function(element, column_name)
							{
								if($type(identifier) == 'number')
								{
									/* 
										not sure why I'm not storing table and element key ids, it seems arbitrary to just have the group_id in there 
										it's on the todo-list
										
										note that if you pass the number as a string it will go to the next conditional chunk.
									*/									
									return element.group_id == identifier;
								}else
								{									
									var plugin_match = ( (element.plugin == identifier) || element.plugin.toLowerCase().contains(identifier.toLowerCase()));
									if(plugin_match) return true;
									else
									{
										var id_match = ( (element.element_id == identifier) || element.element_id.toLowerCase().contains(identifier.toLowerCase()));
										if(id_match) return true;
										else
										{
											var group_match = ( (element.group_name == identifier || element.group_title == identifier) || (element.group_name.toLowerCase().contains(identifier.toLowerCase())) || element.group_title.toLowerCase().contains(identifier.toLowerCase()));
											if(group_match) return true;
											else
											{
												var table_match = element.table.toLowerCase().contains(identifier.toLowerCase());
												var column_match = column_name.toLowerCase().contains(identifier.toLowerCase());																			
												return (table_match || column_match);
											}
										}
									}									
//									
								}
							}));
						});						
										
					
				}
				return this.padTableName(sorted_list);
				
			},			

			/*			
			These two function are used by the class, but they're probably useful by themselves, too
			*/			
			
			getObjectsFromSortedElementList: function(sorted_element_list)
			{
				var element_inded = "";
				var object_list = new Hash();
				for(element_index in sorted_element_list)
				{
					object_list.set(element_index, sorted_element_list[element_index].fabrik_object);
				}
				return object_list;
			},			

			getObjectsFromElementList: function(identifier, group_or_table)
			{
				var object_list = new Hash();
				object_list = this.getObjectsFromSortedElementList(this.sortElementList(identifier, group_or_table));
				return object_list;				
			},			
			

			/*
			return a hash of plugin objects
			object_name  - string name of fabrik plugin
			identifier and group_or_table arguments work the same as with sortElementList function.
			identifier can be a column name or the name of en element plugin	
			
			ex:	 fu.getElementObjects('fabrikfield') will get all the forms fabrikfield plugin objects
			
			*/			
			
			getElementObjects: function(plugin_name, identifier, group_or_table)
			{
				
				var object_list = new Hash();
				if(typeof group_or_table == 'undefined'){ group_or_table = null; }
				if(typeof identifier == 'undefined'){ identifier = null; }
				
				if(identifier == null)
				{
					var element_list = this.sortElementList(plugin_name, group_or_table);					
				}else 
				{
					var element_list = this.sortElementList(identifier, group_or_table);
				}
				
				if(plugin_name == null && identifier != null)
				{
					object_list = getObjectsFromElementList(identifier, group_or_table);
					
				}else
				{			
					element_list.each(function(element, element_id){
							if(element.plugin == plugin_name)
							{
								object_list.set(element_id, element.fabrik_object);
							}
					});	
				}
				
				return object_list;		
			},
			
			
			/*
			Used internally, unless you've made a hash of element plugin objects in your own code I suppose
			*/

			doObjectFunction: function(function_name, function_arguments, object_list)
			{
				var object_return = new Hash();
				
			
				object_list.each(function(el_obj, element_id)
				{	
					object_return.set(element_id, new Object({
								 'list_el':this.element_list[this.getElNames(element_id)['table']][this.getElNames(element_id)['column']], 								
								 'func_name':function_name,
								 'func_args':function_arguments,
								 'func_return':el_obj[function_name].run(function_arguments, el_obj)
					}));
					
					

				}.bind(this));
				
				return object_return;
				
			},		

			/*	
				collects a set of plugin objects and has each one call the specified function with the specified arguments.
				returns a hash of objects keyed to the element_id with a list element object as a member,
				as well as the name of the function that was passed and the arguments, as well as the values returned for each plugin object that ran the function.
				
				if a value for plugin_name is passed, identifier and group_or_table can be left blank. As with the other functions that have group_or_table as an argument,
				its purpose is just to speed up the function when you aren't passing a straight element_id, but
				you know the for sure the grouping of elements that you want to constitute the collection.
				
				plugin_name can be set to null, but if so, a value for identifier needs to be passed.
			*/				
			
			doObjFunc:function(function_name, function_arguments, plugin_name, identifier, group_or_table)
			{
				if(typeof identifier == 'undefined') identifier = null;
				if(typeof group_or_table == 'undefined') group_or_table = null;
				var object_list = this.getElementObjects(plugin_name, identifier, group_or_table);
				return this.doObjectFunction(function_name, function_arguments, object_list);
			},


		});