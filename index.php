<?php

declare(strict_types=1);

use App\Analytics\RescueUniversalAnalyticsData;

const __PROJECT_ROOT__ = __DIR__;

require_once "bootstrap/bootstrap.php";

$analytics = new RescueUniversalAnalyticsData();
$analytics->handle();
