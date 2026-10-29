<?php

declare(strict_types=1);

namespace App\Core;

final class TopsisEngine
{
    /**
     * Compute TOPSIS in-memory with zero disk write.
     *
     * @param array<string, array{id: string, nama: string, bobot: float, jenis: string}> $criteria
     * @param array<string, string> $alternatives [id => nama]
     * @param array<string, array<string, float>> $matrix [id_alt => [id_krit => nilai]]
     * @return array{
     *   divisors: array<string, float>,
     *   normalized: array<string, array<string, float>>,
     *   weighted: array<string, array<string, float>>,
     *   idealPositive: array<string, float>,
     *   idealNegative: array<string, float>,
     *   distancePositive: array<string, float>,
     *   distanceNegative: array<string, float>,
     *   rankings: list<array{rank: int, id: string, nama: string, score: float, dPlus: float, dMinus: float}>
     * }
     */
    public static function calculate(array $criteria, array $alternatives, array $matrix, int $decimals = 4): array
    {
        if (empty($criteria) || empty($alternatives)) {
            return [
                'divisors'         => [],
                'normalized'       => [],
                'weighted'         => [],
                'idealPositive'    => [],
                'idealNegative'    => [],
                'distancePositive' => [],
                'distanceNegative' => [],
                'rankings'         => [],
            ];
        }

        // 1. Root sum of squares per criterion
        $divisors = [];
        foreach ($criteria as $cid => $crit) {
            $sumSq = 0.0;
            foreach ($alternatives as $aid => $name) {
                $val = (float)($matrix[$aid][$cid] ?? 0.0);
                $sumSq += $val * $val;
            }
            $divisors[$cid] = $sumSq > 0.0 ? sqrt($sumSq) : 1.0;
        }

        // 2. Normalized and weighted matrix
        $normalized = [];
        $weighted   = [];
        foreach ($alternatives as $aid => $name) {
            foreach ($criteria as $cid => $crit) {
                $val = (float)($matrix[$aid][$cid] ?? 0.0);
                $r = $val / $divisors[$cid];
                $normalized[$aid][$cid] = $r;
                $weighted[$aid][$cid]   = $r * (float)$crit['bobot'];
            }
        }

        // 3. Positive and negative ideal solutions
        $idealPositive = [];
        $idealNegative = [];
        foreach ($criteria as $cid => $crit) {
            $colValues = [];
            foreach ($alternatives as $aid => $name) {
                $colValues[] = $weighted[$aid][$cid];
            }
            $maxVal = max($colValues);
            $minVal = min($colValues);

            if ($crit['jenis'] === 'benefit') {
                $idealPositive[$cid] = $maxVal;
                $idealNegative[$cid] = $minVal;
            } else {
                $idealPositive[$cid] = $minVal;
                $idealNegative[$cid] = $maxVal;
            }
        }

        // 4. Separation distances and relative closeness
        $distancePositive = [];
        $distanceNegative = [];
        $rankings = [];

        foreach ($alternatives as $aid => $name) {
            $sumDPlusSq  = 0.0;
            $sumDMinusSq = 0.0;
            foreach ($criteria as $cid => $crit) {
                $y = $weighted[$aid][$cid];
                $sumDPlusSq  += ($y - $idealPositive[$cid]) ** 2;
                $sumDMinusSq += ($y - $idealNegative[$cid]) ** 2;
            }
            $dPlus  = sqrt($sumDPlusSq);
            $dMinus = sqrt($sumDMinusSq);
            $distancePositive[$aid] = $dPlus;
            $distanceNegative[$aid] = $dMinus;

            $totalDist = $dPlus + $dMinus;
            $v = $totalDist > 0.0 ? ($dMinus / $totalDist) : 0.0;

            $rankings[] = [
                'id'     => $aid,
                'nama'   => $name,
                'score'  => round($v, $decimals),
                'dPlus'  => round($dPlus, $decimals),
                'dMinus' => round($dMinus, $decimals),
            ];
        }

        // 5. Rank descending by preference score
        usort($rankings, static fn($a, $b) => $b['score'] <=> $a['score']);
        foreach ($rankings as $idx => &$item) {
            $item['rank'] = $idx + 1;
        }
        unset($item);

        return [
            'divisors'         => $divisors,
            'normalized'       => $normalized,
            'weighted'         => $weighted,
            'idealPositive'    => $idealPositive,
            'idealNegative'    => $idealNegative,
            'distancePositive' => $distancePositive,
            'distanceNegative' => $distanceNegative,
            'rankings'         => $rankings,
        ];
    }
}
