<?php

// lock for direct script calling
define('LOCK_START', 1);

// setup environment
define('ENVIRONMENT', 'development');
// define('ENVIRONMENT', 'production');
define('DEBUGGING', false);
define('THREAD', 'coralParseXml');
define('_R', __DIR__ . DIRECTORY_SEPARATOR);
require _R . 'parser'. DIRECTORY_SEPARATOR .'bootstrap.php';