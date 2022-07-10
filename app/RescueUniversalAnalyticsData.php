<?php

declare(strict_types=1);

namespace App\Analytics;

use Configure\Configure;
use DateTime;
use Google\Client;
use Google\Service\AnalyticsReporting;
use Google\Service\AnalyticsReporting\DateRange;
use Google\Service\AnalyticsReporting\Dimension;
use Google\Service\AnalyticsReporting\DimensionFilter;
use Google\Service\AnalyticsReporting\DimensionFilterClause;
use Google\Service\AnalyticsReporting\GetReportsRequest;
use Google\Service\AnalyticsReporting\GetReportsResponse;
use Google\Service\AnalyticsReporting\Metric;
use Google\Service\AnalyticsReporting\OrderBy;
use Google\Service\AnalyticsReporting\ReportData;
use Google\Service\AnalyticsReporting\ReportRequest;
use Google\Service\AnalyticsReporting\ReportRow;

/**
 * class RescueUniversalAnalyticsData
 *
 * Exporting Universal Analytics Property data using Google Analytics Reporting API v4.
 *
 * @see https://developers.google.com/analytics/devguides/reporting/core/v4
 * @see https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php
 * @see https://www.ritolab.com/entry/240
 */
class RescueUniversalAnalyticsData
{
    private const PAGE_SIZE  = 2000;
    private const START_DATE = ''; // e.g.) 2022-06-01
    private const END_DATE   = ''; // e.g.) 2022-06-30

    private const HEADERS = [
        'date', 'datetime', 'hostname', 'pageview', 'page_location', 'source_medium', 'previous_page_path'
    ];

    private readonly string $viewId;
    private readonly string $credentialFileLocation;

    private readonly AnalyticsReporting $analytics;
    private readonly ReportRequest $request;

    private readonly Csv $file;

    /**
     * @throws \Google\Exception
     */
    public function __construct()
    {
        $this->viewId                 = Configure::read('app.google_analytics.view_id');
        $this->credentialFileLocation = Configure::read('app.google_analytics.reporting_api.credentials');

        $this->analytics = $this->initializeAnalytics();
        $this->request   = $this->makeReportRequest();

        $outputFileName = sprintf('export_data-%d-%s_%s.csv', $this->viewId, self::START_DATE, self::END_DATE);
        $this->file     = new Csv(self::HEADERS, $outputFileName);
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        /** @var DateRange $dateRanges */
        $dateRanges = $this->request->getDateRanges();
        echo "{$dateRanges->getStartDate()} - {$dateRanges->getEndDate()} \n";

        $remaining = $this->getTotal();
        if ($remaining === 0) {
            echo 'There was no data available.';
            exit;
        }

        while($remaining > 0) {
            echo "remaining: $remaining \n";
            $response  = $this->getReportData();
            $this->file->output($this->format($response));

            $nextPageToken = $response->getReports()[0]->getNextPageToken();
            $this->request->setPageToken($nextPageToken);
            $remaining = $remaining - self::PAGE_SIZE;
        }
    }

    /**
     * Format the response data.
     *
     * @param GetReportsResponse $response
     * @return array
     */
    private function format(GetReportsResponse $response): array
    {
        $reports = $response->getReports();

        /** @var ReportData $reportData */
        $reportData = $reports[0]['data'];

        $formatted = [];

        /** @var ReportRow $reportRow */
        foreach ($reportData->getRows() as $reportRow) {
            $dimensions = $reportRow->getDimensions();
            $metrics = $reportRow->getMetrics();

            $datetime = new DateTime($dimensions[0]);
            $formatted[] = [
                'date'          => $datetime->format('Y-m-d'),
                'datetime'      => $datetime->format('Y-m-d H:i:s'),
                'hostname'      => $dimensions[1],
                'pageview'      => (int)$metrics[0]->getValues()[0],
                'page_location' => $dimensions[2],
                'source_medium' => $dimensions[3],
                'previous_page_path' => $dimensions[4],
            ];
        }

        return $formatted;
    }

    /**
     * Data acquisition execution.
     *
     * @return GetReportsResponse
     */
    private function getReportData(): GetReportsResponse
    {
        $body = new GetReportsRequest();
        $body->setReportRequests([$this->request]);
        return $this->analytics->reports->batchGet($body);
    }

    /**
     * The total number of request segments will be returned.
     *
     * @return int
     */
    private function getTotal(): int
    {
        $response = $this->getReportData();
        return (int)$response->getReports()[0]->getData()->getRowCount();
    }

    /**
     * Build a request.
     *
     * @return ReportRequest
     */
    private function makeReportRequest(): ReportRequest
    {
        $request = new ReportRequest();
        $request->setViewId($this->viewId);

        $request->setDateRanges($this->getDateRange());
        $request->setDimensions($this->makeDimensions());
        $request->setDimensionFilterClauses($this->makeFilter());
        $request->setMetrics($this->makeMetrics());
        $request->setOrderBys($this->makeOrders());
        $request->setPageSize(self::PAGE_SIZE);

        return $request;
    }

    /**
     * Set the date range.
     *
     * @return DateRange
     */
    private function getDateRange(): DateRange
    {
        $dateRange = new DateRange();
        $dateRange->setStartDate(self::START_DATE);
        $dateRange->setEndDate(self::END_DATE);

        return $dateRange;
    }

    /**
     * Defines a metrics request.
     *
     * @return Metric[]
     *
     * @see https://ga-dev-tools.web.app/dimensions-metrics-explorer/
     */
    private function makeMetrics(): array
    {
        $pageView = new Metric();
        $pageView->setExpression("ga:pageviews");
        $pageView->setAlias("pageviews");

        return [
            $pageView
        ];
    }

    /**
     * Defines a Dimensions request.
     *
     * @return Dimension[]
     *
     * @see https://ga-dev-tools.web.app/dimensions-metrics-explorer/
     */
    private function makeDimensions(): array
    {
        $pagePath = new Dimension();
        $pagePath->setName("ga:pagePath");

        $hostname = new Dimension();
        $hostname->setName("ga:hostname");

        $dateHourMinute = new Dimension();
        $dateHourMinute->setName("ga:dateHourMinute");

        $sourceMedium = new Dimension();
        $sourceMedium->setName("ga:sourceMedium");

        $previousPagePath = new Dimension();
        $previousPagePath->setName("ga:previousPagePath");

        // Up to 9 pieces.
        return [
            $dateHourMinute, $hostname, $pagePath, $sourceMedium, $previousPagePath
        ];
    }

    /**
     * Defines a Orders request.
     *
     * @return OrderBy[]
     */
    private function makeOrders(): array
    {
        $orderByDate = new OrderBy();
        $orderByDate->setFieldName('ga:dateHourMinute');
        $orderByDate->setSortOrder('ASCENDING');

        $orderLocation = new OrderBy();
        $orderLocation->setFieldName('ga:pagePath');
        $orderLocation->setSortOrder('ASCENDING');

        return [
            $orderByDate
        ];
    }

    /**
     * Defines a Dimension Filter request.
     *
     * @return DimensionFilterClause
     *
     * @see https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#dimensionfilter
     */
    private function makeFilter(): DimensionFilterClause
    {
        $pathFilter = new DimensionFilter();
        $pathFilter->setDimensionName('ga:pagePath');
        $pathFilter->setOperator('EXACT');
        $pathFilter->setExpressions(['YouSpecify']);

        $filters = new DimensionFilterClause();
        $filters->setFilters([$pathFilter]);

        return $filters;
    }

    /**
     * Initialize the AnalyticsReporting object.
     *
     * @return AnalyticsReporting
     * @throws \Google\Exception
     */
    private function initializeAnalytics(): AnalyticsReporting
    {
        $client = new Client();
        $client->setApplicationName("Analytics Reporting");
        $client->setAuthConfig($this->credentialFileLocation);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new AnalyticsReporting($client);

        return $analytics;
    }
}

