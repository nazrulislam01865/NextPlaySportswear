<?php

namespace App\Services\Promotions;

class PromotionBannerPositionService
{
    public function evenMiddleRow(int $totalRows): ?int
    {
        if ($totalRows < 2) {
            return null;
        }

        $midpoint = ($totalRows + 1) / 2;
        $bestRow = 2;
        $bestDistance = abs(2 - $midpoint);

        for ($row = 4; $row <= max(2, $totalRows); $row += 2) {
            $distance = abs($row - $midpoint);
            if ($distance < $bestDistance) {
                $bestRow = $row;
                $bestDistance = $distance;
            }
        }

        return $bestRow;
    }

    public function insertionIndex(int $productCount, int $columns): ?int
    {
        if ($productCount <= 0 || $columns <= 0) {
            return null;
        }

        $totalRows = (int) ceil($productCount / $columns);
        $middleRow = $this->evenMiddleRow($totalRows);
        if ($middleRow === null) {
            return null;
        }

        return min($productCount, $middleRow * $columns);
    }

    /**
     * @param  array<int, int>  $sectionProductCounts
     * @return array{section_index:int, product_index:int, row:int}|null
     */
    public function groupedInsertionPoint(array $sectionProductCounts, int $columns): ?array
    {
        if ($columns <= 0) {
            return null;
        }

        $rowCounts = array_map(
            fn ($count): int => max(0, (int) ceil(max(0, (int) $count) / $columns)),
            array_values($sectionProductCounts)
        );
        $totalRows = array_sum($rowCounts);
        $middleRow = $this->evenMiddleRow($totalRows);
        if ($middleRow === null) {
            return null;
        }

        $rowsBefore = 0;
        foreach ($rowCounts as $sectionIndex => $rowsInSection) {
            if ($rowsInSection <= 0) {
                continue;
            }

            if ($middleRow <= $rowsBefore + $rowsInSection) {
                $localRow = $middleRow - $rowsBefore;
                $productCount = max(0, (int) ($sectionProductCounts[$sectionIndex] ?? 0));

                return [
                    'section_index' => $sectionIndex,
                    'product_index' => min($productCount, $localRow * $columns),
                    'row' => $middleRow,
                ];
            }

            $rowsBefore += $rowsInSection;
        }

        return null;
    }
}
