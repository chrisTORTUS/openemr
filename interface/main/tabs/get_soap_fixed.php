<?php
//require_once(__DIR__ . "/../../../interface/globals.php");
//echo __DIR__;
$file_path = __DIR__ . "/../../../interface/globals.php";
if (file_exists($file_path)) {
    require_once($file_path);
} else {
    //echo "File does not exist: $file_path";
    exit;
}
// echo $_GET['arg1'];
$pid = $_GET['pid'];
$eid = $_GET['eid'];
// echo $pid;
// echo $eid;

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

// CALL the api via route handler
//  This allows same notation as the calls in the api (ie. '/api/facility'), but
//  is limited to get requests at this time.
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Http\HttpRestRouteHandler;

require_once(__DIR__ . "/../../../_rest_config.php");
$gbl = RestConfig::GetInstance();
$gbl::setNotRestCall();
$restRequest = new HttpRestRequest($gbl, $_SERVER);
// $restRequest->setRequestMethod("GET");
$restRequest->setRequestPath("/api/patient/1/encounter/5/soap_note");
// $restRequest->setRequestPath("/api/patient/9b7e3d50-26c2-4e40-9af3-7784ca46546c/encounter");
$restRequest->setIsLocalApi(true);
$restRequest->setApiType("oemr");
// below will return as json
//echo "<b>api via route handler call returning json:</b><br />";
echo HttpRestRouteHandler::dispatch($gbl::$ROUTE_MAP, $restRequest, 'direct-json');
//echo "<br /><br />";
?>