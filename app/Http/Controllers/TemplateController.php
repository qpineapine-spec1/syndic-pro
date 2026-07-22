<?php

namespace App\Http\Controllers;

use App\Services\MeetingReportTemplateService;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function download()
    {
        $relPath = 'templates/modele-premiere-assemblee.docx';
        $fullPath = storage_path('app/' . $relPath);
        $tempPath = storage_path('app/templates/modele-premiere-assemblee-' . uniqid('', true) . '.tmp.docx');

        if (!class_exists('\PhpOffice\PhpWord\PhpWord')) {
            abort(500, 'Template generator requires phpoffice/phpword. Run composer require phpoffice/phpword');
        }

        Storage::makeDirectory('templates');

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $service = new MeetingReportTemplateService();
        $service->generate($phpWord);

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        rename($tempPath, $fullPath);

        return response()->download($fullPath, 'modele-premiere-assemblee.docx');
    }
}
