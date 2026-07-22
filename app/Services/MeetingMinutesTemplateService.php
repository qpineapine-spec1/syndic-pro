<?php

namespace App\Services;

use App\Models\Meeting;
use PhpOffice\PhpWord\PhpWord;

class MeetingMinutesTemplateService
{
    public function generate(PHPWord $phpWord, ?Meeting $meeting = null): void
    {
        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 800,
            'marginBottom' => 800,
            'marginLeft' => 800,
            'marginRight' => 800,
        ]);

        $section->addText('SYNDIC PROFESSIONNEL', ['bold' => true, 'size' => 22, 'color' => '1B3A5C'], ['alignment' => 'center']);
        $section->addText('Compte rendu de réunion', ['bold' => true, 'size' => 20], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $propertyName = $meeting?->property?->name ?? 'Immeuble';
        $section->addText('Immeuble : ' . ($propertyName ?: 'À renseigner'), ['bold' => true]);
        $section->addText('Type de réunion : ' . ($meeting?->type_reunion ? $this->formatMeetingType($meeting->type_reunion) : 'À renseigner'), ['bold' => true]);
        $section->addText('Date : ' . ($meeting?->meeting_date ? \Illuminate\Support\Carbon::parse($meeting->meeting_date)->translatedFormat('d/m/Y') : 'À renseigner'), ['bold' => true]);
        $section->addText('Heure : ' . ($meeting?->meeting_date ? \Illuminate\Support\Carbon::parse($meeting->meeting_date)->translatedFormat('H:i') : 'À renseigner'), ['bold' => true]);
        $section->addText('Lieu : ' . ($meeting?->lieu ?: 'À renseigner'), ['bold' => true]);
        $section->addText('Ordre du jour : ' . ($meeting?->agenda ?: 'À renseigner'), ['bold' => true]);
        $section->addTextBreak(1);

        $section->addText('À compléter par le syndic', ['italic' => true, 'size' => 12]);
        $section->addTextBreak(1);

        $this->addTitle($section, 'Présents');
        $this->addEmptyLines($section, 6);

        $this->addTitle($section, 'Décisions prises');
        $this->addEmptyLines($section, 6);

        $this->addTitle($section, 'Notes complémentaires');
        $this->addEmptyLines($section, 6);
    }

    private function addTitle($section, string $title): void
    {
        if (method_exists($section, 'addTitle')) {
            $section->addTitle($title, 2);
        } else {
            $section->addText($title, ['bold' => true, 'size' => 14]);
        }
    }

    private function addEmptyLines($section, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $section->addText('');
        }
    }

    private function formatMeetingType(string $type): string
    {
        return match ($type) {
            'assemblee_generale' => 'Assemblée générale',
            'reunion_conseil' => 'Réunion de conseil',
            'reunion_extraordinaire' => 'Réunion extraordinaire',
            default => 'Autre',
        };
    }
}
