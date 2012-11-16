<?php

defined('_JEXEC') or die();

$formModel =& $tableModel->getForm();
if (strstr($formModel->_formData['us_streets___date_time'],'1899')) {
   $formModel->_formData['us_streets___date_time'] = str_replace('1899','1999',$formModel->_formData['us_streets___date_time']);
}

$formModel->_formData['us_streets___street_desc'] = "testing 1 2 3 testing";
$formModel->_formData['us_streets___street_pic'] = "images/stories/fabrik/streets/foo.jpg"
?>
