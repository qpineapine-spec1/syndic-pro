<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;

class MeetingReportTemplateService
{
    public function generate(PHPWord $phpWord): void
    {
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 600,
            'marginBottom' => 600,
            'marginLeft' => 600,
            'marginRight' => 600,
        ]);

        $section->addText('SYNDIC PROFESSIONNEL', ['bold' => true, 'size' => 32, 'color' => '2F5496'], ['alignment' => 'center']);
        $section->addText('PROCÈS-VERBAL DE PREMIÈRE ASSEMBLÉE', ['bold' => true, 'size' => 24], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $section->addText('Immeuble : ' . str_repeat('_', 50), ['bold' => true]);
        $section->addText('Adresse : ' . str_repeat('_', 50), ['bold' => true]);
        $section->addText('Date de l\'assemblée (JJ/MM/AAAA) : ' . str_repeat('_', 42), ['bold' => true]);
        $section->addTextBreak(1);

        $section->addText(
            'Remarque : conservez la structure de ce document (titres de sections, tableaux). Vous pouvez ajouter ou supprimer des lignes vides selon vos besoins. Ne remplissez pas les lignes TOTAL, elles sont calculées automatiquement à l\'import.',
            ['italic' => true, 'size' => 18],
            ['bgColor' => 'FFF3CD', 'spaceBefore' => 200, 'spaceAfter' => 300]
        );
        $section->addTextBreak(1);

        $this->addTitle($section, 'LISTE DES COPROPRIÉTAIRES');
        $this->addTable($section, ['Nom', 'Prénom', 'Email', 'Téléphone', 'N° bureau', 'Étage', 'Superficie lot (m²)', 'Statut'], 6);
        $section->addTextBreak(1);

        $this->addTitle($section, 'LISTE DES PRESTATAIRES');
        $this->addTable($section, ['Nom société', 'Date début contrat', 'Date fin contrat', 'Montant mensuel (€)', 'Nb visites / mois'], 6);
        $section->addTextBreak(1);

        $this->addTitle($section, 'BUDGET PRÉVISIONNEL');
        $section->addText('Année : ' . str_repeat('_', 42), ['bold' => true]);
        $section->addTextBreak(1);

        $section->addText('Charges fixes', ['bold' => true]);
        $this->addTable($section, ['Catégorie', 'Montant mensuel (€)', 'Montant annuel (€)', 'Justificatif'], 6);
        $section->addTextBreak(1);

        $section->addText('Charges variables', ['bold' => true]);
        $this->addTable($section, ['Type', 'Catégorie', 'Montant estimé annuel (€)', 'Justificatif'], 6);
        $section->addTextBreak(1);

        $section->addText('TOTAL CHARGES FIXES ANNUELLES : [calculé automatiquement, ne pas remplir]', ['bold' => true], ['bgColor' => 'D9D9D9']);
        $section->addText('TOTAL CHARGES VARIABLES ANNUELLES : [calculé automatiquement, ne pas remplir]', ['bold' => true], ['bgColor' => 'D9D9D9']);
        $section->addText('MONTANT TOTAL BUDGET : [calculé automatiquement, ne pas remplir]', ['bold' => true], ['bgColor' => 'D9D9D9']);
    }

    private function addTitle($section, string $title): void
    {
        if (method_exists($section, 'addTitle')) {
            $section->addTitle($title, 2);
        } else {
            $section->addText($title, ['bold' => true, 'size' => 14]);
        }
    }

    private function addTable($section, array $headers, int $blankRows): void
    {
        $tableStyle = 'ModelePremiereAssembleeTable';
        $headerStyle = ['bold' => true, 'color' => 'FFFFFF'];
        $section->getPhpWord()->addTableStyle(
            $tableStyle,
            ['borderSize' => 6, 'borderColor' => '2F5496', 'cellMargin' => 80],
            []
        );

        $table = $section->addTable($tableStyle);
        $table->addRow();
        foreach ($headers as $heading) {
            $table->addCell(2000, ['bgColor' => '2F5496'])->addText($heading, $headerStyle, ['alignment' => 'center']);
        }

        for ($i = 0; $i < $blankRows; $i++) {
            $table->addRow();
            foreach ($headers as $heading) {
                $table->addCell(2000)->addText('');
            }
        }
    }
}
