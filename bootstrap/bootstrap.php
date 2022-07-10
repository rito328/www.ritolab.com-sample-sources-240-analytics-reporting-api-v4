<?php

declare(strict_types=1);

require_once __PROJECT_ROOT__ . '/vendor/autoload.php';
require_once __PROJECT_ROOT__ . '/app/RescueUniversalAnalyticsData.php';
require_once __PROJECT_ROOT__ . '/app/Csv.php';

$dotenv = Dotenv\Dotenv::createImmutable(__PROJECT_ROOT__);
$dotenv->load();
$dotenv->required(['VIEW_ID', 'CREDENTIAL_FILE_NAME']);

Configure\Configure::Instance();
