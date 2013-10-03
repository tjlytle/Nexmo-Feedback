<?php
/**
 * @author Tim Lytle <tim@timlytle.net>
 */
defined('NEXMO_KEY') || (getenv('NEXMO_KEY') AND define('NEXMO_KEY', getenv('NEXMO_KEY')));
defined('NEXMO_SECRET') || (getenv('NEXMO_SECRET') AND define('NEXMO_SECRET', getenv('NEXMO_SECRET')));
defined('NEXMO_FROM') || (getenv('NEXMO_FROM') AND define('NEXMO_FROM', getenv('NEXMO_FROM')));
defined('MONGO') || (getenv('MONGO') AND define('MONGO', getenv('MONGO')));

require_once __DIR__ . '/src/Feedback.php';
require_once __DIR__ . '/src/Nexmo.php';