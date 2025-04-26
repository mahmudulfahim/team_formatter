<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class CsvReaderService
{
	/**
	 * Reads the csv file and returns an array of rows each row contains array of columns
	 *
	 * @param UploadedFile $file
	 * @param bool $hasHeader
	 * @return array
	 */
	public function readCsv(UploadedFile $file, bool $hasHeader = true): array
	{
		$rows = [];
		$filePath = $file->getPathname();

		if (($handle = fopen($filePath, 'r')) !== false) {
			$header = [];
			while (($data = fgetcsv($handle, 1000, ',')) !== false) {
				if ($hasHeader && empty($header)) {
					$header = $data;
					continue;
				}

				$rows[] = $hasHeader ? array_combine($header, $data) : $data;
			}
			fclose($handle);
		}

		return $rows;
	}
}