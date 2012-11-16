<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
require_once( JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );

define( "SHOW_ALL_PARAMS", false );
define( "USE_WITH_NO_DB_FORMS", true );
define( "USE_EXTENDED", true );

class plgSystemFabrik_utility extends JPlugin
{
	function plgFabrik_utility(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}

	function p($var)
	{
		echo "<pre style='background-color:white'>";
		print_r($var);
		echo "</pre>";
	}

	function initCheck(){

		global $mainframe;
		// Don't run in admin
		if ($mainframe->isAdmin())
		{
			return false;
		}

		if (JRequest::getVar('format') == 'raw'){
			return false;
		}
		// Don't run if the page isn't loading a fabrik form
		if(JRequest::getVar('option') != 'com_fabrik')
		{
			return false;
		}else
		{
			if((JRequest::getVar('view') == 'form' || JRequest::getVar('view') == 'details') && JRequest::getVar('task') != 'ajax_validate')
			{
				return true;
			}else
			{
				return false;
			}
		}
	}


	function getPageUrl(){

		  $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		  $protocol = substr(strtolower($_SERVER["SERVER_PROTOCOL"]), 0, strpos(strtolower($_SERVER["SERVER_PROTOCOL"]), "/")) . $s;
		  $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		  return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
	}

	function getArrayFromObj($obj)
	{
		$arr = array();
		foreach($obj as $key => $value)
		{
			$arr[$key] = $value;
		}
		return $arr;
	}

	function getFirst($list)
	{
	   foreach($list as $key => $value)
		{
			$k = $key . "";
			if($k != '0')
			{
				$first = $value;
				break;
			}
		}
		return $first;
	}

	function setArrayNum(&$arr)
	{
		foreach($arr as $key => &$value)
		{
			if(is_numeric($value)){ $value += 0; }
		}
	}

	function getAttribArray($attribs)
	{
		 	 $attrib_arr = explode("\n",$attribs);
			 $return_arr = array();

			  for($x = 0; $x < sizeof($attrib_arr); $x++)
			  {
				$attrib = explode("=", $attrib_arr[$x]);
				$return_arr[$attrib[0]] = $attrib[1];

			  }
			  $this->setArrayNum($return_arr);
			  return $return_arr;
	}


	function initProperties()
	{
		$user =& JFactory::getUser(); 		$user_data = array();
		$user_data['id'] = $user->id; 		$user_data['username'] = $user->username;
		$user_data['name'] = $user->name; 	$user_data['type'] = $user->usertype;
		$this->set('user', $user_data);

		$view_name = JRequest::getVar( 'view', 'form', 'default', 'cmd' );
		$this->set('view',$view_name);
		$id = JRequest::getVar('fabrik');
		$model_name = $view_name;
		if ($view_name == 'emailform' || $view_name == 'details'){ $model_name = 'form'; }
		$model =& JModel::getInstance($model_name,'FabrikModel');
		$model->setId( $id );
		$model->getRowId();

		$this->set('data', $model->getData() );
		$this->set('elements_by_group', $model->getGroupView());
		$this->set('form_groups', $model->getPublishedGroups());


		$form =& $model->getForm();
		$record_in_database = $form->record_in_database + 0;
		if($record_in_database== 0 && USE_WITH_NO_DB_FORMS == false) return false;


		$attribs_exist = true;
		if($form->attribs == NULL || $form->attribs == "") $attribs_exist = false;

		if($attribs_exist) $form_attribs = $this->getAttribArray($form->attribs);

		$page_url = $this->getPageUrl();
		$query_arr = array();
		parse_str(parse_url($page_url, PHP_URL_QUERY), $query_arr);


		$form_params = array();

		if(SHOW_ALL_PARAMS)
		{
			$form_arr = $this->getArrayFromObj($form);
			unset($form_arr['attribs']);
			unset($form_arr['_db']);
			if($attribs_exist)
			{
				$form_params = array_merge($query_arr,$form_arr,$form_attribs);
			}else
			{
				$form_params = array_merge($query_arr,$form_arr);
			}
		}else
		{
			$form_params = $query_arr;
			unset($form_params['option']);
			$form_params['ajax_url'] =  $page_url . "&fu_ajax=1&method=";
			$form_params['formid'] = $view_name . "_". $id;
			$form_params['label'] = $form->label;
			$form_params['record_in_database'] = $form->record_in_database + 0;
			$layout = JRequest::getVar( 'fabriklayout', JRequest::getVar( 'layout', NULL ) );

			if($layout == NULL)
			{
				if($view_name == 'details')
				{
					$form_params['layout'] = $form->view_only_template;
				}else
				{
					$form_params['layout'] = $form->form_template;
				}
			}
			if($attribs_exist)
			{
				$form_params['show_reset'] = JArrayHelper::getValue($form_attribs, 'reset_button');
				$form_params['show_copy'] = JArrayHelper::getValue($form_attribs, 'copy_button');
				$form_params['show_goback'] = JArrayHelper::getValue($form_attribs, 'goback_button');
				$form_params['show_apply'] = JArrayHelper::getValue($form_attribs, 'apply_button');
				$form_params['show_delete'] = JArrayHelper::getValue($form_attribs, 'delete_button');
				$form_params['ajax_validations'] = JArrayHelper::getValue($form_attribs, 'ajax_validations');
			}
		}

		$this->setArrayNum($form_params);

		$this->set('form_params', $form_params);
		return true;
	}

	function initLists()
	{

		 $c = 0;
		 $element_list = array();
		 $group_list = array();
		 $view = $this->get('view');

		  foreach($this->get('elements_by_group') as $grp_name => $grp_obj)
		  {
  			  $group_id = $grp_obj->id;
			  $group_name = $grp_obj->name;
			  $group_label = $grp_obj->title;

			  $elements = $grp_obj->elements;
			  $first_el = $this->getFirst($elements);
			  $el_name = explode("___",$first_el->id);

			  	  $join_id = 0;

			  if(sizeof($el_name) == 2)
			  {
				  $table = $el_name[0];
				  $column = $el_name[1];

			  }else if(sizeof($el_name) == 4)
			  {
				  $join_id = $el_name[1];
				  $table = $el_name[2];
				  $column = $el_name[3];
			  }


			  $group_list[$c]['join_id'] = $join_id+0;
			  $group_list[$c]['table_name'] = $table;
			  $group_list[$c]['editable'] = $grp_obj->editable+0;
			  $group_list[$c]['canRepeat'] = $grp_obj->canRepeat+0;
			  $group_list[$c]['displaystate'] = $grp_obj->displaystate+0;
			  $c++;

			  if(!array_key_exists($table, $element_list))
			  {
				  $element_list[$table] = array();
			  }

			  foreach($elements as $key => $value)
			  {
				  $k = $key . "";
				  if($k != '0')
				  {
					  $el_id = $value->id;
					  $column = FabrikString::shortColName($el_id);

					  $element_list[$table][$column] = array();
					  $element_list[$table][$column]['plugin'] = $value->plugin;
  				      $element_list[$table][$column]['table'] = $table;

					  if($view == 'details') $el_id .= "_ro";

					  $element_list[$table][$column]['element_id'] = $el_id;
					  if($value->plugin == 'fabrikdate' && $view == 'form')
					  {
						  $element_list[$table][$column]['element_id'] .= '_cal';
					  }
					  $element_list[$table][$column]['label_id'] = 'fb_el_'.$el_id.'_text';
					  $element_list[$table][$column]['label'] = $value->label_raw;
					  $element_list[$table][$column]['hidden'] = $value->hidden+0;
					  $element_list[$table][$column]['group_id'] = $group_id+0;
					  $element_list[$table][$column]['group_name'] = $group_name;
					  $element_list[$table][$column]['group_title'] = $group_label;

					  if(is_array($value->value) && sizeof($value->value) == 1)
					  {
						  $element_list[$table][$column]['value'] = $value->value[0];

					  }else if(is_array($value->value) && sizeof($value->value) > 1 && $value->value[0] == "" && $value->value[sizeof($value->value)-1] == "")
					  {
					  	  $element_list[$table][$column]['value'] = $value->value[0];
					  }else
					  {
						  $element_list[$table][$column]['value'] = $value->value;
					  }

					  if(is_array($value->element_raw) && sizeof($value->element_raw) == 1)
					  {
						  $element_list[$table][$column]['value'] = $value->element_raw[0];
					  }else if(is_array($value->element_raw) && sizeof($value->element_raw) > 1 && $value->element_raw[0] == "" && $value->element_raw[sizeof($value->element_raw)-1] == "")
					  {
					  	  $element_list[$table][$column]['value'] = $value->element_raw[0];
					  }else
					  {
						  $element_list[$table][$column]['value'] = $value->element_raw;
					  }

				  }
			  }
		  }


		  $c = 0;
		  foreach($this->get('form_groups') as $grp_id => $grp_obj)
		  {
			  $group_list[$c]['name'] = $grp_obj->name;
			  $group_list[$c]['label'] = $grp_obj->label;
			  $group_list[$c]['ordering'] = $grp_obj->ordering+0;
			  $group_list[$c]['id'] = $grp_obj->id+0;
			  $group_list[$c]['state'] = $grp_obj->state+0;
			  $group_list[$c]['is_join'] = $grp_obj->is_join+0;
			  $group_list[$c]['attribs'] = $this->getAttribArray($grp_obj->attribs);
			  $c++;
		  }

			$this->set('element_list', $element_list);
			$this->set('group_list', $group_list);
	}



	function doAjax(){

		$db =& JFactory::getDBO();
		require_once(COM_FABRIK_FRONTEND . DS. "user_ajax.php" );

		ob_start();
		$method = JRequest::getVar( 'method', '' );
		$userAjax = new userAjax ( $db );
		if (method_exists( $userAjax, $method )) {

				$userAjax->$method();
		}
		$ajax_response = ob_get_contents();
		ob_end_clean();
		echo $ajax_response;

	}


/*	function onGetWebServices()
	{if(!$this->initCheck()){return;}else{


	}}


	function onAfterInitialise()
	{if(!$this->initCheck()){return;}else{

	}}
*/

	function onAfterRoute()
	{if(!$this->initCheck()){return;}else{
		if(JRequest::getVar('fu_ajax') == 1)
		{
			JResponse::clearHeaders();
			JResponse::setBody("");
			$this->doAjax();
			$app =& JFactory::getApplication();
			exit;
		}else if(JRequest::getVar('fu_off') == 1)
		{
			exit;
		}
	}}

	function onAfterDispatch()

	{if(!$this->initCheck()){return;}else{

		$init_fu = $this->initProperties();
		if(!$init_fu) return;

		$this->initLists();


		$js_params = $this->get('form_params');
		$js_user = $this->get('user');
		$js_el_list = $this->get('element_list');
		$js_grp_list =$this->get('group_list');

		FabrikHelperHTML::script( 'fabrik_utility.js', 'plugins/system/', false);
		$script = "";

		if(USE_EXTENDED)
		{
			FabrikHelperHTML::script( 'fabrik_utility_ext.js', 'plugins/system/', false);
			$script = "var fu = new Fabrik_Utility_Ext(".json_encode($js_params).", ".json_encode($js_user).", ".json_encode($js_el_list).", ".json_encode($js_grp_list).");";
		}else
		{
			$script = "var fu = new Fabrik_Utility(".json_encode($js_params).", ".json_encode($js_user).", ".json_encode($js_el_list).", ".json_encode($js_grp_list).");";
		}
		FabrikHelperHTML::addScriptDeclaration( $script );

	}}

	function onAfterRender()
	{if(!$this->initCheck()){return;}else{

		if(JRequest::getVar('fu_element_list') == 1)
		{
			$this->p($this->get('element_list'));
		}

		if(JRequest::getVar('fu_group_list') == 1)
		{
			$this->p($this->get('group_list'));
		}

		if(JRequest::getVar('fu_elements_by_group') == 1)
		{
			$this->p($this->get('elements_by_group'));
		}

		if(JRequest::getVar('fu_published_groups') == 1)
		{
			$this->p($this->get('published_groups'));
		}

		if(JRequest::getVar('fu_all') == 1)
		{
			$this->p($this->getProperties());
		}

	}}

}