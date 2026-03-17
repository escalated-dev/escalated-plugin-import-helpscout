<?php

if (! defined('ESCALATED_LOADED')) {
    exit('Direct access not allowed.');
}

require_once __DIR__ . '/src/HelpScoutImportAdapter.php';
require_once __DIR__ . '/src/HelpScoutClient.php';
require_once __DIR__ . '/src/HelpScoutFieldMapper.php';

use Escalated\Plugins\ImportHelpScout\HelpScoutImportAdapter;

escalated_add_filter('import.adapters', function (array $adapters) {
    $adapters[] = new HelpScoutImportAdapter();
    return $adapters;
}, 10);
