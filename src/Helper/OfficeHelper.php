<?php

namespace Shishima\Thumbnail\Helper;

use Illuminate\Support\Facades\Storage;
use Shishima\Thumbnail\Exception\ConvertToPdf;

class OfficeHelper
{
    /**
     * Handles any necessary conversion of Microsoft Office files to PDFs before they are processed.
     *
     * @param  string  $tempPath  The path of the file to be processed
     * @throws ConvertToPdf If the conversion to PDF fails
     */
    public static function pretreatmentOfficeFile(string $tempPath, $extension): string
    {
        return match ($extension)
        {
            'doc', 'docx', 'xls', 'xlsx' => static::convertMsOfficeToPdf($tempPath, $extension),
            'pdf' => static::splitFistPageOfPdf($tempPath),
            default => $tempPath
        };
    }

    /**
     * Split the first page of a PDF file and return the path of the resulting file.
     *
     * @param  string  $tempPath  The path of the original PDF file.
     * @return string The path of the file containing the first page of the PDF.
     * @throws ConvertToPdf
     */
    public static function splitFistPageOfPdf(string $tempPath): string
    {
        $output   = $tempPath;
        $tempPath = substr_replace($tempPath, '_temp.pdf', strrpos($tempPath, '.') + 1);

        // Rename the original file to the temporary file path
        StorageHelper::rename($output, $tempPath);

        // Use Ghostscript to split the first page of the PDF
        shell_exec("gs -dNOPAUSE -dBATCH -dFirstPage=1 -dLastPage=1 -sDEVICE=pdfwrite -sOutputFile=$output $tempPath");

        // Remove the temporary file
        StorageHelper::removeFile($tempPath);

        return static::removeTempFileAfterConvert($output, $tempPath);
    }

    /**
     * Converts the Microsoft Office file at the given path to a PDF.
     *
     * This method uses the unoconv command-line utility to convert the file to a PDF. If the file is an Excel
     * file, it first runs the "FitToPage" macro to ensure that the entire spreadsheet is visible in the PDF.
     *
     * @param  string  $tempPath  The path of the Microsoft Office file to be converted
     * @return array|string The path of the resulting PDF file, or an array containing the default thumbnail path if the conversion fails
     * @throws ConvertToPdf If the conversion fails
     */
    public static function convertMsOfficeToPdf(string $tempPath, $extension): string
    {
        if (static::isExcelFile($extension))
        {
            static::addMacroToExcel($tempPath);
        }

        $output = substr_replace($tempPath, 'pdf', strrpos($tempPath, '.') + 1);

        shell_exec("unoconv -f pdf -e PageRange=1-1 $tempPath --output=$output");

        // backup for case if unoconv is no longer used
        // $outdir = Storage::disk('temp_thumbnail')->path('');
        // shell_exec("/usr/bin/libreoffice --headless --convert-to 'pdf:writer_pdf_Export:{\"PageRange\":{\"type\":\"string\",\"value\":\"1\"}}' --outdir $outdir $tempPath");

        return static::removeTempFileAfterConvert($output, $tempPath);
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
     * @param  string  $tempPath  The path to the temporary file that was created.
     */
    public static function addMacroToExcel(string $tempPath): void
    {
        $cmd = "/usr/bin/libreoffice --headless --nologo --nofirststartwizard --norestore $tempPath macro:///Standard.Module1.FitToPage";
        shell_exec($cmd);
    }

    /**
     * Remove the temporary file and return the path of the converted file.
     *
     * @param  string  $output  The path of the converted file.
     * @param  string  $tempPath  The path of the temporary file.
     * @return string The path of the converted file.
     * @throws ConvertToPdf If the converted file does not exist, indicating conversion failure.
     */
    public static function removeTempFileAfterConvert(string $output, string $tempPath): string
    {
        if (file_exists($output))
        {
            StorageHelper::removeFile($tempPath);
            return $output;
        }

        throw new ConvertToPdf('Cannot Convert File To PDF.');
    }
}
