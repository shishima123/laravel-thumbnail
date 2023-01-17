<?php

namespace Shishima\Thumbnail\Helper;

use Shishima\Thumbnail\Exception\ConvertToPdf;

class OfficeHelper
{
    protected static array $officeExtensions = ['doc', 'docx', 'xls', 'xlsx'];

    /**
     * Handles any necessary conversion of Microsoft Office files to PDFs before they are processed.
     *
     * @param string $tempPath The path of the file to be processed
     * @throws ConvertToPdf If the conversion to PDF fails
     */
    public static function pretreatmentOfficeFile(string $tempPath, $extension): string
    {
        if (in_array($extension, static::$officeExtensions)) {
            $tempPath = static::convertMsOfficeToPdf($tempPath, $extension);
        }

        return $tempPath;
    }

    /**
     * Converts the Microsoft Office file at the given path to a PDF.
     *
     * This method uses the unoconv command-line utility to convert the file to a PDF. If the file is an Excel
     * file, it first runs the "FitToPage" macro to ensure that the entire spreadsheet is visible in the PDF.
     *
     * @param string $tempPath The path of the Microsoft Office file to be converted
     * @return array|string The path of the resulting PDF file, or an array containing the default thumbnail path if the conversion fails
     * @throws ConvertToPdf If the conversion fails
     */
    public static function convertMsOfficeToPdf(string $tempPath, $extension): array|string
    {
        if (static::isExcelFile($extension)) {
            static::addMacroToExcel($tempPath);
        }
        $output = substr_replace($tempPath, 'pdf', strrpos($tempPath, '.') + 1);

        shell_exec("unoconv -f pdf -e PageRange=1-1 $tempPath --output=$output");

        // remove temp file after convert successfully
        if (file_exists($output)) {
            StorageHelper::removeFile($tempPath);
            return $output;
        }

        throw new ConvertToPdf('Cannot Convert MsOffice To PDF.');
    }

    /**
     * Determines if the file is an Excel file.
     *
     * @return bool `true` if the file is an Excel file, `false` otherwise
     */
    public static function isExcelFile($extension): bool
    {
        return in_array($extension, ['xls', 'xlsx']);
    }

    /**
     * Add FitToPage macro to file excel
     *
     * @param string $tempPath The path to the temporary file that was created.
     */
    public static function addMacroToExcel(string $tempPath): void
    {
        $cmd = "/usr/bin/libreoffice --headless --nologo --nofirststartwizard --norestore $tempPath macro:///Standard.Module1.FitToPage";
        shell_exec($cmd);
    }
}
