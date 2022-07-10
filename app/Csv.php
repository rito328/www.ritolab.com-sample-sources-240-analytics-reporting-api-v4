<?php

declare(strict_types=1);

namespace App\Analytics;

use SplFileObject;

/**
 * class Csv
 *
 * Create and write a CSV file.
 */
class Csv
{
    private readonly SplFileObject $file;

    public function __construct(array $header, string $outputFileName)
    {
        // Be sure to output to the project root excluded from the commit target so that you do not accidentally `git commit` the output data.
        $this->file = new SplFileObject(basename($outputFileName), "w");
        $this->file->fputcsv($header);
    }

    public function output(array $data)
    {
        foreach ($data as $line) {
            mb_convert_variables('sjis-win', 'UTF-8', $line);
            $this->file->fputcsv($line);
        }
    }
}
