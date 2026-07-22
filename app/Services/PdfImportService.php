<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class PdfImportService
{
    protected $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser();
    }

    public function extractText(string $path): string
    {
        $pdf = $this->parser->parseFile($path);
        return (string) $pdf->getText();
    }

    public function parse(string $path): array
    {
        $text = $this->extractText($path);
        $text = $this->normalizeText($text);

        $result = [
            'owners' => [],
            'service_providers' => [],
            'budget' => [],
            'expenses_fixes' => [],
            'expenses_variables' => [],
            'totals' => [
                'fixed' => null,
                'variable' => null,
                'total' => null,
            ],
            'warnings' => [],
        ];

        $lines = preg_split('/\r?\n/', $text);
        $currentSection = null;
        $ownerLines = [];
        $serviceProviderLines = [];
        $fixedExpenseBuffer = '';
        $variableExpenseBuffer = '';
        $knownProviders = [];

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') {
                continue;
            }

            if ($this->sectionMatches($line, 'LISTE DES COPROPRIETAIRES')) {
                $currentSection = 'LISTE DES COPROPRIETAIRES';
                continue;
            }

            if ($this->sectionMatches($line, 'LISTE DES PRESTATAIRES')) {
                $currentSection = 'LISTE DES PRESTATAIRES';
                continue;
            }

            if ($this->sectionMatches($line, 'BUDGET PREVISIONNEL')) {
                $currentSection = 'BUDGET PREVISIONNEL';
                continue;
            }

            if ($this->sectionMatches($line, 'CHARGES FIXES')) {
                $currentSection = 'CHARGES FIXES';
                $fixedExpenseBuffer = '';
                $variableExpenseBuffer = '';
                // build known provider names from earlier collected provider lines (if present)
                $parsed = $this->parseServiceProvidersFromLines($serviceProviderLines);
                $knownProviders = array_map(function ($p) { return mb_strtolower(trim($p['name'] ?? ''), 'UTF-8'); }, $parsed);
                continue;
            }

            if ($this->sectionMatches($line, 'CHARGES VARIABLES')) {
                $currentSection = 'CHARGES VARIABLES';
                $fixedExpenseBuffer = '';
                $variableExpenseBuffer = '';
                $parsed = $this->parseServiceProvidersFromLines($serviceProviderLines);
                $knownProviders = array_map(function ($p) { return mb_strtolower(trim($p['name'] ?? ''), 'UTF-8'); }, $parsed);
                continue;
            }

            if ($this->sectionMatches($line, 'TOTAL CHARGES FIXES ANNUELLES')) {
                $result['totals']['fixed'] = $this->parseAmount(explode(':', $line, 2)[1] ?? '');
                // reset any running buffers when totals encountered
                $fixedExpenseBuffer = '';
                $variableExpenseBuffer = '';
                continue;
            }

            if ($this->sectionMatches($line, 'TOTAL CHARGES VARIABLES ANNUELLES')) {
                $result['totals']['variable'] = $this->parseAmount(explode(':', $line, 2)[1] ?? '');
                $fixedExpenseBuffer = '';
                $variableExpenseBuffer = '';
                continue;
            }

            if ($this->sectionMatches($line, 'MONTANT TOTAL BUDGET')) {
                $result['totals']['total'] = $this->parseAmount(explode(':', $line, 2)[1] ?? '');
                $fixedExpenseBuffer = '';
                $variableExpenseBuffer = '';
                continue;
            }

            switch ($currentSection) {
                case 'LISTE DES COPROPRIETAIRES':
                    if ($this->isOwnerHeaderLine($line) || $this->isOwnerHeaderFragmentLine($line)) {
                        break;
                    }

                    $ownerLines[] = $line;
                    break;
                case 'LISTE DES PRESTATAIRES':
                    if ($this->isServiceProviderHeaderLine($line)) {
                        break;
                    }

                    $serviceProviderLines[] = $line;
                    break;
                case 'BUDGET PREVISIONNEL':
                    // Accept both 'Année' and 'Annee' (with/without accents)
                    if ($this->sectionMatches($line, 'Annee') || preg_match('/\bAnnee\b|\bAnn[eé]e\b/ui', $line)) {
                        $parts = preg_split('/:\s*/', $line, 2);
                        $year = trim($parts[1] ?? '');
                        if ($year !== '') {
                            $result['budget']['year'] = $year;
                        }
                    }
                    break;
                case 'CHARGES FIXES':
                    if ($this->sectionMatches($line, 'Categorie') || $this->sectionMatches($line, 'Montant mensuel') || $this->sectionMatches($line, 'Montant annuel')) {
                        break;
                    }

                    // If this line contains no digits, treat it as category/title fragment
                    // Decide whether to buffer this fragment. If the fragment matches a known provider name,
                    // treat it as a stray provider line and skip buffering so it doesn't contaminate categories.
                    $frag = trim($line);
                    $lowerFrag = mb_strtolower($frag, 'UTF-8');
                    $isKnownProvider = false;
                    foreach ($knownProviders as $kp) {
                        if ($kp === '') continue;
                        if ($lowerFrag === $kp || str_starts_with($lowerFrag, $kp . ' ') || str_contains($kp, $lowerFrag)) {
                            $isKnownProvider = true;
                            break;
                        }
                    }
                    if ($isKnownProvider) {
                        // do not treat as expense fragment
                        break;
                    }

                    if (!preg_match('/\d/', $line)) {
                        // attach fragment to buffer (do not ignore short tokens)
                        $fixedExpenseBuffer = trim($fixedExpenseBuffer . ' ' . $line);
                        break;
                    }

                    // Line contains digits -> likely amounts / justificatif, combine with previous fragments
                    $combined = trim($fixedExpenseBuffer . ' ' . $line);
                    // Remove possible header fragments that leaked into buffer
                    $combined = preg_replace('/^(?:.*(?:Categorie|Montant mensuel|Montant annuel|Justificatif|\(€\)|€)\s*)+/iu', '', $combined);
                    // strip orphan punctuation at ends
                    $combined = preg_replace('/^[\)\(\-:;\s]+|[\)\(\-:;\s]+$/u', '', $combined);
                    // Normalize multiple spaces into tab-like separators for parsing
                    $combinedForParse = preg_replace('/\s{2,}/u', "\t", $combined);
                    $expense = $this->parseFixedExpenseLine($combinedForParse);
                    if ($expense !== null) {
                        $result['expenses_fixes'][] = $expense;
                    }
                    $fixedExpenseBuffer = '';
                    break;
                case 'CHARGES VARIABLES':
                    if ($this->sectionMatches($line, 'Type') || $this->sectionMatches($line, 'Categorie') ) {
                        break;
                    }

                    // Accumulate category/type fragments until a digits-containing line appears
                    $fragV = trim($line);
                    $lowerFragV = mb_strtolower($fragV, 'UTF-8');
                    $isKnownProviderV = false;
                    foreach ($knownProviders as $kp) {
                        if ($kp === '') continue;
                        if ($lowerFragV === $kp || str_starts_with($lowerFragV, $kp . ' ') || str_contains($kp, $lowerFragV)) {
                            $isKnownProviderV = true;
                            break;
                        }
                    }
                    if ($isKnownProviderV) {
                        break;
                    }

                    if (!preg_match('/\d/', $line)) {
                        $variableExpenseBuffer = trim($variableExpenseBuffer . ' ' . $line);
                        break;
                    }

                    $combinedVar = trim($variableExpenseBuffer . ' ' . $line);
                    $combinedVar = preg_replace('/^(?:.*(?:Type|Categorie|Montant estim[e|é] annu|Justificatif|\(€\)|€)\s*)+/iu', '', $combinedVar);
                    $combinedVar = preg_replace('/^[\)\(\-:;\s]+|[\)\(\-:;\s]+$/u', '', $combinedVar);
                    $combinedVarForParse = preg_replace('/\s{2,}/u', "\t", $combinedVar);
                    $expenseVar = $this->parseVariableExpenseLine($combinedVarForParse);
                    if ($expenseVar !== null) {
                        $result['expenses_variables'][] = $expenseVar;
                    }
                    $variableExpenseBuffer = '';
                    break;
                default:
                    break;
            }
        }

        $result['owners'] = $this->parseOwnersFromLines($ownerLines);
        $result['service_providers'] = $this->parseServiceProvidersFromLines($serviceProviderLines);

        return $result;
    }

    protected function parseOwnersFromLines(array $lines): array
    {
        $owners = [];
        $buffer = '';

        for ($index = 0; $index < count($lines); $index++) {
            $line = trim(preg_replace('/\s+/u', ' ', $lines[$index]));
            if ($this->isOwnerMetadataLine($line)) {
                continue;
            }

            $nextLine = isset($lines[$index + 1]) ? trim(preg_replace('/\s+/u', ' ', $lines[$index + 1])) : '';
            $buffer = trim($buffer . ' ' . $line);
            $buffer = $this->fixBrokenEmail($buffer);

            if ($this->isStandaloneOwnerStatusLine($nextLine) && $this->ownerLineIsComplete($buffer)) {
                continue;
            }

            if ($this->ownerLineIsComplete($buffer)) {
                $parsed = $this->parseOwnerLine($buffer);
                if ($parsed !== null) {
                    $owners[] = $parsed;
                }
                $buffer = '';
            }

            if ($buffer !== '' && $this->isStandaloneOwnerStatusLine($line) && !empty($owners)) {
                $last = &$owners[count($owners) - 1];
                if (!str_contains($this->normalizeForCompare($last['status']), $this->normalizeForCompare($line))) {
                    $last['status'] = trim($last['status'] . ' ' . $line);
                }
                $buffer = '';
            }
        }

        if ($buffer !== '' && $this->ownerLineIsComplete($buffer)) {
            $parsed = $this->parseOwnerLine($buffer);
            if ($parsed !== null) {
                $owners[] = $parsed;
            }
        }

        return $owners;
    }

    protected function parseServiceProvidersFromLines(array $lines): array
    {
        $providers = [];
        $buffer = '';

        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($this->isServiceProviderMetadataLine($line)) {
                continue;
            }

            $buffer = trim($buffer . ' ' . $line);

            // Clean common header fragments that sometimes prefix the company name
            $cleanBuffer = preg_replace('/^(?:\(?€\)?|eur|nb\s*visites(?:\s*\/\s*mois)?|nbvisitesmois|nbvisites)\s*/iu', '', $buffer);
            $cleanBuffer = preg_replace('/^\s*[\p{P}\p{S}]++\s*/u', '', $cleanBuffer);

            if ($this->serviceProviderLineIsComplete($cleanBuffer)) {
                $parsed = $this->parseServiceProviderLine($cleanBuffer);
                if ($parsed !== null) {
                    $providers[] = $parsed;
                }
                $buffer = '';
            }
        }

        if ($buffer !== '' && $this->serviceProviderLineIsComplete($buffer)) {
            $parsed = $this->parseServiceProviderLine($buffer);
            if ($parsed !== null) {
                $providers[] = $parsed;
            }
        }

        return $providers;
    }

    protected function fixBrokenEmail(string $line): string
    {
        return preg_replace('/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{1,3})\s+([A-Za-z]{1,4})\s+(\d{6,})/u', '$1$2 $3', $line);
    }

    protected function isOwnerMetadataLine(string $line): bool
    {
        $normalized = $this->normalizeKey($line);
        return $normalized === 'm2'
            || $normalized === 'm²'
            || $normalized === 'statut'
            || str_contains($normalized, 'nomprenom')
            || str_contains($normalized, 'email')
            || str_contains($normalized, 'telephone')
            || str_contains($normalized, 'nbureau')
            || str_contains($normalized, 'etage')
            || str_contains($normalized, 'superficielot');
    }

    protected function isStandaloneOwnerStatusLine(string $line): bool
    {
        $normalized = $this->normalizeForCompare($line);
        return $normalized === 'occupant'
            || $normalized === 'bailleur'
            || $normalized === 'locataire'
            || $normalized === 'vacataire'
            || $normalized === 'proprietaire'
            || $normalized === 'proprietaire occupant'
            || $normalized === 'proprietaire bailleur';
    }

    protected function isServiceProviderMetadataLine(string $line): bool
    {
        $trim = trim($line);
        if ($trim === '') {
            return true;
        }

        // lines that are only punctuation or currency symbol
        if (preg_match('/^[\p{P}\p{S}\s]+$/u', $line)) {
            return true;
        }

        $normalized = $this->normalizeForCompare($line);
        if (str_contains($normalized, 'nb visites') || str_contains($normalized, 'nbvisites') || str_contains($normalized, 'montant') || str_contains($normalized, 'euros') || str_contains($normalized, 'eur')) {
            return true;
        }

        $key = $this->normalizeKey($line);
        return $key === '€'
            || $key === 'nbvisites'
            || str_contains($key, 'nbsvisites')
            || str_contains($key, 'nomsociete')
            || str_contains($key, 'montantmensuel')
            || str_contains($key, 'categorie')
            || str_contains($key, 'justificatif')
            || str_contains($key, 'type')
            || str_contains($key, 'annuel');
    }

    

    protected function normalizeText(string $text): string
    {
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = preg_replace('/([^\s])\r?\n([^\s])/u', '$1$2', $text);
        $text = preg_replace('/[ ]+/', ' ', $text);
        $text = preg_replace('/ *\r?\n */', "\n", $text);
        return trim($text);
    }

    protected function sectionMatches(string $line, string $needle): bool
    {
        $normalize = function (string $value): string {
            $value = $this->stripAccents($value);
            $value = mb_strtolower($value, 'UTF-8');
            $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
            return trim($value);
        };

        $normalizedLine = $normalize($line);
        $normalizedNeedle = $normalize($needle);
        return $normalizedLine !== '' && $normalizedNeedle !== '' && strpos($normalizedLine, $normalizedNeedle) !== false;
    }

    protected function isOwnerHeaderLine(string $line): bool
    {
        $normalized = $this->normalizeForCompare($line);
        return str_contains($normalized, 'nom')
            && str_contains($normalized, 'prenom')
            && str_contains($normalized, 'email');
    }

    protected function isServiceProviderHeaderLine(string $line): bool
    {
        $normalized = $this->normalizeKey($line);
        return $normalized === '€'
            || $normalized === 'nbvisites'
            || str_contains($normalized, 'nbsvisites')
            || str_contains($normalized, 'nomsociete')
            || str_contains($normalized, 'montantmensuel')
            || str_contains($normalized, 'datedebutcontrat')
            || str_contains($normalized, 'datefincontrat');
    }

    protected function normalizeForCompare(string $line): string
    {
        $line = $this->stripAccents($line);
        $line = mb_strtolower($line, 'UTF-8');
        $line = preg_replace('/[\s\x{00A0}]+/u', ' ', $line);
        return trim($line);
    }

    protected function normalizeKey(string $line): string
    {
        $line = $this->stripAccents($line);
        $line = mb_strtolower($line, 'UTF-8');
        $line = preg_replace('/[^\p{L}\p{N}]+/u', '', $line);
        return trim($line);
    }

    protected function stripAccents(string $string): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        return $converted !== false ? $converted : $string;
    }

    protected function parseOwnerLine(string $line): ?array
    {
        if (strpos($line, '|') !== false) {
            $cols = array_map('trim', explode('|', $line));
            if (count($cols) >= 8) {
                return [
                    'last_name' => $cols[0],
                    'first_name' => $cols[1],
                    'email' => $cols[2],
                    'phone' => $cols[3],
                    'lot_number' => $cols[4],
                    'floor' => $cols[5],
                    'surface' => $cols[6],
                    'status' => $cols[7],
                ];
            }

            return null;
        }

        if (preg_match('/^([^\s]+)\s+([^\s]+)\s+([^\s]+@[^\s]+)\s+(\d{6,})\s+([^\s]+)\s+(\d+)\s+(\d+)\s+(.+)$/u', $line, $matches)) {
            return [
                'last_name' => $matches[1],
                'first_name' => $matches[2],
                'email' => $matches[3],
                'phone' => $matches[4],
                'lot_number' => $matches[5],
                'floor' => $matches[6],
                'surface' => $matches[7],
                'status' => trim($matches[8]),
            ];
        }

        return null;
    }

    protected function parseServiceProviderLine(string $line): ?array
    {
        if (strpos($line, '|') !== false) {
            $cols = array_map('trim', explode('|', $line));
            if (count($cols) >= 5) {
                return [
                    'name' => $cols[0],
                    'contract_start' => $cols[1],
                    'contract_end' => $cols[2],
                    'monthly_amount' => $cols[3],
                    'visits_per_month' => $cols[4],
                ];
            }

            return null;
        }

        if (preg_match('/^(.+?)\s+(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s+([0-9]+(?:[\.,][0-9]+)?)\s+(\d+)$/u', $line, $matches)) {
            return [
                'name' => trim($matches[1]),
                'contract_start' => $matches[2],
                'contract_end' => $matches[3],
                'monthly_amount' => $matches[4],
                'visits_per_month' => $matches[5],
            ];
        }

        return null;
    }

    protected function isOwnerHeaderFragmentLine(string $line): bool
    {
        $normalized = $this->normalizeForCompare($line);
        return str_contains($normalized, 'statut')
            || str_contains($normalized, 'nom prenom email')
            || str_contains($normalized, 'telephone n bureau')
            || str_contains($normalized, 'superficie lot');
    }

    protected function parseOwnersSection(string $section): array
    {
        $section = trim(preg_replace('/\s+/u', ' ', $section));
        $owners = [];

        $pattern = '/([A-Za-zÀ-ÖØ-öø-ÿ\'’\-]+)\s+([A-Za-zÀ-ÖØ-öø-ÿ\'’\-]+)\s+([^\s]+@[^"]+)\s+(\d{6,})\s+([A-Za-z0-9]+)\s+(\d+)\s+(\d+)\s+((?:Propri[ée]taire|occupant|bailleur|locataire|vacataire|proprietaire)(?:\s+[A-Za-zÀ-ÖØ-öø-ÿ\'’\-]+)?)/u';
        if (preg_match_all($pattern, $section, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $owners[] = [
                    'last_name' => $match[1],
                    'first_name' => $match[2],
                    'email' => $match[3],
                    'phone' => $match[4],
                    'lot_number' => $match[5],
                    'floor' => $match[6],
                    'surface' => $match[7],
                    'status' => trim($match[8]),
                ];
            }
        }

        return $owners;
    }

    protected function parseServiceProvidersSection(string $section): array
    {
        $section = trim(preg_replace('/\s+/u', ' ', $section));
        $providers = [];
        $pattern = '/(.+?)\s+(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s+([0-9]+(?:[.,][0-9]+)?)\s+(\d+)/u';

        if (preg_match_all($pattern, $section, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $providers[] = [
                    'name' => trim($match[1]),
                    'contract_start' => $match[2],
                    'contract_end' => $match[3],
                    'monthly_amount' => $match[4],
                    'visits_per_month' => $match[5],
                ];
            }
        }

        return $providers;
    }

    protected function ownerLineIsComplete(string $line): bool
    {
        return preg_match('/[A-Za-zÀ-ÖØ-öø-ÿ]+\s+[A-Za-zÀ-ÖØ-öø-ÿ]+\s+[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u', $line)
            && preg_match('/\d{6,}/', $line)
            && preg_match('/\b(Propriétaire|occupant|bailleur|locataire|vacataire|occupant|proprietaire)\b/ui', $line);
    }

    protected function serviceProviderLineIsComplete(string $line): bool
    {
        return preg_match('/\d{2}\/\d{2}\/\d{4}\s+\d{2}\/\d{2}\/\d{4}/', $line) === 1
            && preg_match('/\d+(\.|,)?\d*/', $line) === 1;
    }

    protected function fixedExpenseLineIsComplete(string $line): bool
    {
        return preg_match('/\d+(?:[\.,]\d+)?\s+(?:\d+(?:[\.,]\d+)?)\s+.+$/u', $line) === 1
            || preg_match('/\d+\s*\t\s*\d+\s*\t\s*.+$/u', $line) === 1;
    }

    protected function variableExpenseLineIsComplete(string $line): bool
    {
        return preg_match('/\d+(?:[\.,]\d+)?\s+(?:.+)$/u', $line) === 1
            || preg_match('/\d+(?:[\.,]\d+)?\s*\t\s*.+$/u', $line) === 1;
    }

    protected function parseFixedExpenseLine(string $line): ?array
    {
        // Collapse whitespace
        $line = preg_replace('/\s+/u', ' ', trim($line));

        // Try splitting on obvious separators first
        $parts = preg_split('/\s{2,}|\t/', $line);
        if (count($parts) >= 3) {
            return [
                'categorie' => trim($parts[0]),
                'monthly' => $this->parseAmount($parts[1] ?? ''),
                'annual' => $this->parseAmount($parts[2] ?? ''),
                'justificatif' => trim($parts[3] ?? ''),
            ];
        }

        // Fallback: look for last two numeric tokens as monthly and annual
        $tokens = preg_split('/\s+/', $line);
        $count = count($tokens);
        $annual = null;
        $monthly = null;
        // find last numeric token
        for ($i = $count - 1; $i >= 0; $i--) {
            if (preg_match('/^-?\d+(?:[\.,]\d+)?$/', $tokens[$i])) {
                if ($annual === null) {
                    $annual = $tokens[$i];
                    continue;
                }
                if ($monthly === null) {
                    $monthly = $tokens[$i];
                    // remove detected tokens and stop
                    $catTokens = array_slice($tokens, 0, $i);
                    $justifTokens = array_slice($tokens, $i + 2);
                    $categorie = implode(' ', $catTokens);
                    $justificatif = implode(' ', $justifTokens);
                    return [
                        'categorie' => trim($categorie),
                        'monthly' => $this->parseAmount($monthly),
                        'annual' => $this->parseAmount($annual),
                        'justificatif' => trim($justificatif),
                    ];
                }
            }
        }

        return null;
    }

    protected function parseVariableExpenseLine(string $line): ?array
    {
        $line = preg_replace('/\s+/u', ' ', trim($line));
        $parts = preg_split('/\s{2,}|\t/', $line);
        if (count($parts) >= 3) {
            return [
                'type' => trim($parts[0]),
                'categorie' => trim($parts[1] ?? ''),
                'annual_estimate' => $this->parseAmount($parts[2] ?? ''),
                'justificatif' => trim($parts[3] ?? ''),
            ];
        }

        // Fallback: find last numeric token as annual estimate
        $tokens = preg_split('/\s+/', $line);
        $count = count($tokens);
        for ($i = $count - 1; $i >= 0; $i--) {
            if (preg_match('/^-?\d+(?:[\.,]\d+)?$/', $tokens[$i])) {
                $annual = $tokens[$i];
                $type = $tokens[0] ?? '';
                $categorie = implode(' ', array_slice($tokens, 1, $i - 1));
                $justificatif = implode(' ', array_slice($tokens, $i + 1));
                return [
                    'type' => trim($type),
                    'categorie' => trim($categorie),
                    'annual_estimate' => $this->parseAmount($annual),
                    'justificatif' => trim($justificatif),
                ];
            }
        }

        return null;
    }

    protected function parseAmount(string $str)
    {
        $clean = preg_replace('/[^0-9,\.\-]/', '', $str);
        // if no digit remains, return null
        if (!preg_match('/[0-9]/', $clean)) {
            return null;
        }
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }
}
