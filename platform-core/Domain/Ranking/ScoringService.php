<?php

namespace Poradnik\Platform\Domain\Ranking;

if (! defined('ABSPATH')) {
    exit;
}

final class ScoringService
{
    /**
     * @param array<string, float|int|string> $metrics
     */
    public static function score(array $metrics): float
    {
        $quality = self::normalize($metrics['quality'] ?? 0, 10);
        $price = self::normalize($metrics['price'] ?? 0, 10);
        $features = self::normalize($metrics['features'] ?? 0, 10);
        $support = self::normalize($metrics['support'] ?? 0, 10);

        $weighted = ($quality * 0.35) + ($price * 0.25) + ($features * 0.25) + ($support * 0.15);

        return round($weighted, 2);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function rank(array $items): array
    {
        foreach ($items as $index => $item) {
            $metrics = [
                'quality' => (float) ($item['quality'] ?? 0),
                'price' => (float) ($item['price'] ?? 0),
                'features' => (float) ($item['features'] ?? 0),
                'support' => (float) ($item['support'] ?? 0),
            ];

            $items[$index]['score'] = self::score($metrics);
        }

        usort($items, static fn (array $a, array $b): int => (float) ($b['score'] ?? 0) <=> (float) ($a['score'] ?? 0));

        $position = 1;
        foreach ($items as $index => $item) {
            $items[$index]['position'] = $position;
            $position++;
        }

        return $items;
    }

    private static function normalize($value, float $max): float
    {
        $value = (float) $value;

        if ($value < 0) {
            return 0;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
