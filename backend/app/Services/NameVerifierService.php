<?php

namespace App\Services;

class NameVerifierService
{
    private const TOKEN_SIMILARITY_THRESHOLD   = 0.85;
    private const SURNAME_SIMILARITY_THRESHOLD = 0.92;
    private const FORENAME_COVERAGE_THRESHOLD  = 0.80;

    /**
     * Determine whether a learner's registered name is acceptable against
     * the structured name fields returned from the national DB.
     *
     * @param  string       $firstName        Learner's registered first name
     * @param  string|null  $middleName       Learner's registered middle name (optional)
     * @param  string       $lastName         Learner's registered last name
     * @param  string       $verifiedForenames  Forenames string from the national DB (may include middle name)
     * @param  string       $verifiedSurname    Surname string from the national DB
     */
    public function isAcceptable(
        string $firstName,
        ?string $middleName,
        string $lastName,
        string $verifiedForenames,
        string $verifiedSurname
    ): bool {
        $submittedForenames = $this->buildSubmittedForenames($firstName, $middleName);
        $submittedSurname   = $this->buildSubmittedSurname($lastName);

        $vForenames = $this->tokenize($verifiedForenames);
        $vSurname   = $this->normalizeSurname($verifiedSurname);

        if (empty($submittedSurname) || empty($vSurname)) {
            return false;
        }

        if (! $this->surnamesMatch($submittedSurname, $vSurname)) {
            if (! $this->isCrossMatchAcceptable($submittedForenames, $submittedSurname, $vForenames, $vSurname)) {
                return false;
            }
            return true;
        }

        $submittedForenames = $this->stripSurnameComponents($submittedForenames, $vSurname);

        if (empty($submittedForenames)) {
            return true;
        }

        return $this->forenamesCoverageAcceptable($submittedForenames, $vForenames);
    }

    /**
     * Return a detailed result array instead of a boolean.
     * Useful for logging near-misses or feeding an admin review queue.
     *
     * @return array{
     *   acceptable: bool,
     *   reason: string|null,
     *   surname_similarity: float,
     *   forename_coverage: float|null,
     *   submitted_forenames: string[],
     *   submitted_surname: string,
     *   verified_forenames: string[],
     *   verified_surname: string
     * }
     */
    public function diagnose(
        string $firstName,
        string $lastName,
        ?string $middleName,
        string $verifiedForenames,
        string $verifiedSurname
    ): array {
        $submittedForenames = $this->buildSubmittedForenames($firstName, $middleName);
        $submittedSurname   = $this->buildSubmittedSurname($lastName);

        $vForenames = $this->tokenize($verifiedForenames);
        $vSurname   = $this->normalizeSurname($verifiedSurname);

        if (empty($submittedSurname) || empty($vSurname)) {
            return $this->result(
                false,
                'empty_tokens',
                0.0,
                null,
                $submittedForenames,
                $submittedSurname,
                $vForenames,
                $vSurname
            );
        }

        $surnameSimilarity = $this->bestSurnameSimilarity($submittedSurname, $vSurname);

        if (! $this->surnamesMatch($submittedSurname, $vSurname)) {
            if (! $this->isCrossMatchAcceptable($submittedForenames, $submittedSurname, $vForenames, $vSurname)) {
                return $this->result(
                    false,
                    'surname_mismatch',
                    $surnameSimilarity,
                    null,
                    $submittedForenames,
                    $submittedSurname,
                    $vForenames,
                    $vSurname
                );
            }

            $submittedTokens = array_values(array_filter(array_merge($submittedForenames, explode('-', $submittedSurname))));
            $verifiedTokens = array_values(array_filter(array_merge($vForenames, explode('-', $vSurname))));
            $overallCoverage = $this->forenameCoverage($submittedTokens, $verifiedTokens);

            return $this->result(
                true,
                null,
                $surnameSimilarity,
                $overallCoverage,
                $submittedForenames,
                $submittedSurname,
                $vForenames,
                $vSurname
            );
        }

        $cleanedForenames = $this->stripSurnameComponents($submittedForenames, $vSurname);

        if (empty($cleanedForenames)) {
            return $this->result(
                true,
                null,
                $surnameSimilarity,
                1.0,
                $submittedForenames,
                $submittedSurname,
                $vForenames,
                $vSurname
            );
        }

        $coverage   = $this->forenameCoverage($cleanedForenames, $vForenames);
        $acceptable = $coverage >= self::FORENAME_COVERAGE_THRESHOLD;

        return $this->result(
            $acceptable,
            $acceptable ? null : 'forename_mismatch',
            $surnameSimilarity,
            $coverage,
            $submittedForenames,
            $submittedSurname,
            $vForenames,
            $vSurname
        );
    }

    /**
     * Normalize a name string the same way the verifier does internally.
     * Useful for storing a canonical form alongside the raw input.
     */
    public function normalize(string $name): string
    {
        return implode(' ', $this->tokenize($name));
    }

    /**
     * Compute a raw 0.0–1.0 similarity score between two name strings.
     * Useful for ranking or sorting candidates in a review queue.
     */
    public function similarity(string $nameA, string $nameB): float
    {
        return $this->jaroWinkler(
            implode(' ', $this->tokenize($nameA)),
            implode(' ', $this->tokenize($nameB))
        );
    }

    // -------------------------------------------------------------------------
    // Name preparation
    // -------------------------------------------------------------------------

    /**
     * Tokenize first + optional middle name into a flat forename list.
     *
     * @return string[]
     */
    private function buildSubmittedForenames(string $firstName, ?string $middleName): array
    {
        return array_values(array_filter([
            ...$this->tokenize($firstName),
            ...($middleName ? $this->tokenize($middleName) : []),
        ]));
    }

    /**
     * Normalize the submitted last name into a single hyphen-joined surname token
     * so it is structurally comparable to national DB surnames like "gyekye-boateng".
     *
     * "Gyekye Boateng" → "gyekye-boateng"
     * "Gyekye-Boateng" → "gyekye-boateng"
     */
    private function buildSubmittedSurname(string $lastName): string
    {
        return implode('-', $this->tokenize($lastName));
    }

    /**
     * Normalize the verified surname from the national DB the same way.
     *
     * "GYEKYE-BOATENG" → "gyekye-boateng"
     */
    private function normalizeSurname(string $surname): string
    {
        return implode('-', $this->tokenize($surname));
    }

    // -------------------------------------------------------------------------
    // Matching logic
    // -------------------------------------------------------------------------

    private function surnamesMatch(string $submitted, string $verified): bool
    {
        if ($submitted === $verified) {
            return true;
        }

        // Direct fuzzy match — handles minor typos across the full surname
        if ($this->jaroWinkler($submitted, $verified) >= self::SURNAME_SIMILARITY_THRESHOLD) {
            return true;
        }

        // Allow a submitted surname to match one component of a hyphenated verified surname.
        // e.g. submitted "boateng", verified "gyekye-boateng" → matches on "boateng" segment.
        // Guarded so this only applies when the verified surname is actually hyphenated —
        // we never allow a partial match against a plain single-word surname.
        if (str_contains($verified, '-')) {
            foreach (explode('-', $verified) as $part) {
                if ($this->jaroWinkler($submitted, $part) >= self::SURNAME_SIMILARITY_THRESHOLD) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if the user swapped their names by computing overall token coverage,
     * ensuring at minimum that the given surnames exist in each other's full token sets.
     *
     * @param  string[]  $submittedForenames
     * @param  string    $submittedSurname
     * @param  string[]  $vForenames
     * @param  string    $vSurname
     * @return bool
     */
    private function isCrossMatchAcceptable(
        array $submittedForenames,
        string $submittedSurname,
        array $vForenames,
        string $vSurname
    ): bool {
        $submittedTokens = array_values(array_filter(array_merge($submittedForenames, explode('-', $submittedSurname))));
        $verifiedTokens = array_values(array_filter(array_merge($vForenames, explode('-', $vSurname))));

        // We require the verified surname to be present among the submitted tokens
        $vSurnameMatched = false;
        foreach (explode('-', $vSurname) as $vSurPart) {
            foreach ($submittedTokens as $sTok) {
                if ($this->tokensSimilar($sTok, $vSurPart)) {
                    $vSurnameMatched = true;
                    break 2;
                }
            }
        }

        if (! $vSurnameMatched) {
            return false;
        }

        // We require the submitted surname to be present among the verified tokens
        $submittedSurnameMatched = false;
        foreach (explode('-', $submittedSurname) as $sSurPart) {
            foreach ($verifiedTokens as $vTok) {
                if ($this->tokensSimilar($sSurPart, $vTok)) {
                    $submittedSurnameMatched = true;
                    break 2;
                }
            }
        }

        if (! $submittedSurnameMatched) {
            return false;
        }

        return $this->forenameCoverage($submittedTokens, $verifiedTokens) >= self::FORENAME_COVERAGE_THRESHOLD;
    }

    /**
     * Drop any submitted forename tokens that are actually components of the
     * verified hyphenated surname written out as separate words.
     *
     * e.g. submitted forenames ["seth", "gyekye"], verified surname "gyekye-boateng"
     *      → "gyekye" is a surname component → returns ["seth"]
     *
     * @param  string[]  $submittedForenames
     * @param  string    $verifiedSurname     Already normalized (hyphen-joined, lowercased)
     * @return string[]
     */
    private function stripSurnameComponents(array $submittedForenames, string $verifiedSurname): array
    {
        if (! str_contains($verifiedSurname, '-')) {
            return $submittedForenames;
        }

        $components = explode('-', $verifiedSurname);

        return array_values(array_filter(
            $submittedForenames,
            function (string $token) use ($components): bool {
                foreach ($components as $component) {
                    if ($this->jaroWinkler($token, $component) >= self::SURNAME_SIMILARITY_THRESHOLD) {
                        return false;
                    }
                }
                return true;
            }
        ));
    }

    private function forenamesCoverageAcceptable(array $submittedForenames, array $verifiedForenames): bool
    {
        return $this->forenameCoverage($submittedForenames, $verifiedForenames) >= self::FORENAME_COVERAGE_THRESHOLD;
    }

    private function forenameCoverage(array $submittedForenames, array $verifiedForenames): float
    {
        if (empty($verifiedForenames)) {
            return 0.0;
        }

        $matched = 0;
        foreach ($submittedForenames as $sToken) {
            foreach ($verifiedForenames as $vToken) {
                if ($this->tokensSimilar($sToken, $vToken)) {
                    $matched++;
                    break;
                }
            }
        }

        return $matched / count($submittedForenames);
    }

    private function bestSurnameSimilarity(string $submitted, string $verified): float
    {
        $best = $this->jaroWinkler($submitted, $verified);

        if (str_contains($verified, '-')) {
            foreach (explode('-', $verified) as $part) {
                $best = max($best, $this->jaroWinkler($submitted, $part));
            }
        }

        return $best;
    }

    // -------------------------------------------------------------------------
    // String utilities
    // -------------------------------------------------------------------------

    private function tokenize(string $name): array
    {
        $name = mb_strtolower($name);
        $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $name = preg_replace('/[^a-z\s\-]/', '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        return array_values(array_filter(explode(' ', $name)));
    }

    private function tokensSimilar(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return $this->jaroWinkler($a, $b) >= self::TOKEN_SIMILARITY_THRESHOLD;
    }

    private function jaroWinkler(string $s1, string $s2): float
    {
        $jaro   = $this->jaro($s1, $s2);
        $prefix = 0;
        $limit  = min(4, min(strlen($s1), strlen($s2)));

        while ($prefix < $limit && $s1[$prefix] === $s2[$prefix]) {
            $prefix++;
        }

        return $jaro + ($prefix * 0.1 * (1 - $jaro));
    }

    private function jaro(string $s1, string $s2): float
    {
        if ($s1 === $s2) return 1.0;

        $len1      = strlen($s1);
        $len2      = strlen($s2);
        $matchDist = max((int) floor(max($len1, $len2) / 2) - 1, 0);

        $s1Matches = array_fill(0, $len1, false);
        $s2Matches = array_fill(0, $len2, false);
        $matches   = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDist);
            $end   = min($i + $matchDist + 1, $len2);
            for ($j = $start; $j < $end; $j++) {
                if ($s2Matches[$j] || $s1[$i] !== $s2[$j]) continue;
                $s1Matches[$i] = $s2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) return 0.0;

        $transpositions = 0;
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (! $s1Matches[$i]) continue;
            while (! $s2Matches[$k]) $k++;
            if ($s1[$i] !== $s2[$k]) $transpositions++;
            $k++;
        }

        return (
            ($matches / $len1) +
            ($matches / $len2) +
            (($matches - $transpositions / 2) / $matches)
        ) / 3;
    }

    private function result(
        bool $acceptable,
        ?string $reason,
        float $surnameSimilarity,
        ?float $forenameCoverage,
        array $submittedForenames,
        string $submittedSurname,
        array $verifiedForenames,
        string $verifiedSurname
    ): array {
        return [
            'acceptable'          => $acceptable,
            'reason'              => $reason,
            'surname_similarity'  => round($surnameSimilarity, 4),
            'forename_coverage'   => $forenameCoverage !== null ? round($forenameCoverage, 4) : null,
            'submitted_forenames' => $submittedForenames,
            'submitted_surname'   => $submittedSurname,
            'verified_forenames'  => $verifiedForenames,
            'verified_surname'    => $verifiedSurname,
        ];
    }
}
