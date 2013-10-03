<?php
/**
 * Simple request router, take inbound messages from Nexmo and collect responses to a simple survey.
 *
 * @author Tim Lytle <tim@timlytle.net>
 */

require_once __DIR__ . '/../local.php';

require_once __DIR__ . '/../bootstrap.php'; //credentials and such

//some common setup
$mongo = new MongoClient(MONGO);
$db = $mongo->feedback;
$nexmo = new Nexmo(NEXMO_KEY, NEXMO_SECRET);
$feedback = new Feedback($nexmo, NEXMO_FROM, $db);

//request looks to be from Nexmo
$request = array_merge($_GET, $_POST); //method configurable via Nexmo API / Dashboard
if(isset($request['msisdn'], $request['text'])){
    try{
        $feedback->process($request['msisdn'], $request['text']);
    } catch (Exception $e) {
        error_log($e); //NOTE: if you want Nexmo to retry, just give a non-2XX response
    }
    return;
}