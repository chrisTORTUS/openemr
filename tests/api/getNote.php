<?php
require_once(__DIR__ . "/../../interface/globals.php");
echo __DIR__ . "/../../interface/globals.php";

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

// CALL the api via route handler
//  This allows same notation as the calls in the api (ie. '/api/facility'), but
//  is limited to get requests at this time.
use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Common\Http\HttpRestRouteHandler;

require_once(__DIR__ . "/../../_rest_config.php");
$gbl = RestConfig::GetInstance();
$gbl::setNotRestCall();
$restRequest = new HttpRestRequest($gbl, $_SERVER);
$restRequest->setRequestMethod("GET");
$restRequest->setRequestPath("/api/facility");
$restRequest->setIsLocalApi(true);
$restRequest->setApiType("oemr");
// below will return as json
echo "<b>api via route handler call returning json:</b><br />";
echo HttpRestRouteHandler::dispatch($gbl::$ROUTE_MAP, $restRequest, 'direct-json');
echo "<br /><br />";
?>