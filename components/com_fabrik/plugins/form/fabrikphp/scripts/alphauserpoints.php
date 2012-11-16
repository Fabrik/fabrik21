<?php
/** simple alpha user points scritp to award points when submitting a form */

$api_AUP = JPATH_SITE.DS.'components'.DS.'com_alphauserpoints'.DS.'helper.php';
if (JFile::exists($api_AUP)) {
	require_once($api_AUP);
	AlphaUserPointsHelper::newpoints('plgaup_fabrik_points');
}
