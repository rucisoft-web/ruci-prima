<?php

namespace App\Support;

use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\Priority;
use App\Enums\RiskLevel;

/**
 * Rumus penilaian Asset Care, sama dengan modul Odoo gpch_asset_care 18.0.1.3.
 */
class Scoring
{
    /** Bobot komponen skor kesehatan (total 100%). */
    public const HEALTH_WEIGHTS = [
        'physical_condition' => 0.25,
        'maintenance_compliance' => 0.20,
        'breakdown_reliability' => 0.15,
        'remaining_life_score' => 0.15,
        'safety_condition' => 0.10,
        'operational_reliability' => 0.10,
        'documentation_score' => 0.05,
    ];

    public const CRITICALITY_FIELDS = ['safety_impact', 'guest_impact', 'revenue_impact', 'operational_impact', 'replacement_difficulty'];

    /** @return array{0: float, 1: Condition} */
    public static function health(array $values): array
    {
        $score = 0.0;

        foreach (self::HEALTH_WEIGHTS as $field => $weight) {
            $score += (int) ($values[$field] ?? 0) * $weight;
        }

        $condition = match (true) {
            $score >= 90 => Condition::Excellent,
            $score >= 75 => Condition::Good,
            $score >= 60 => Condition::Fair,
            $score >= 40 => Condition::Poor,
            default => Condition::Critical,
        };

        return [round(max(0, min($score, 100)), 1), $condition];
    }

    /** @return array{0: float, 1: Criticality} */
    public static function criticality(array $values): array
    {
        $sum = 0;

        foreach (self::CRITICALITY_FIELDS as $field) {
            $sum += max(1, min((int) ($values[$field] ?? 1) ?: 1, 5));
        }

        $score = $sum / 5;

        $level = match (true) {
            $score >= 4.2 => Criticality::Critical,
            $score >= 3.3 => Criticality::High,
            $score >= 2.2 => Criticality::Medium,
            default => Criticality::Low,
        };

        return [round($score, 2), $level];
    }

    /** Risiko aset = (100 − kesehatan) × (kekritisan / 5). @return array{0: float, 1: Priority} */
    public static function assetRisk(float $health, float $criticalityScore): array
    {
        $score = (100 - $health) * ($criticalityScore / 5);

        $priority = match (true) {
            $score >= 55 => Priority::Urgent,
            $score >= 35 => Priority::High,
            $score >= 18 => Priority::Medium,
            default => Priority::Low,
        };

        return [round($score, 1), $priority];
    }

    /** Matriks risiko 5×5: probabilitas × konsekuensi. @return array{0: int, 1: RiskLevel} */
    public static function riskMatrix(int $probability, int $consequence): array
    {
        $score = max(1, min($probability, 5)) * max(1, min($consequence, 5));

        $level = match (true) {
            $score >= 20 => RiskLevel::Critical,
            $score >= 12 => RiskLevel::High,
            $score >= 6 => RiskLevel::Medium,
            default => RiskLevel::Low,
        };

        return [$score, $level];
    }

    /** Skor prioritas CAPEX 0–100. */
    public static function capexPriority(int $guest, int $safety, int $revenue, int $urgency): float
    {
        return round(($guest * 0.20 + $safety * 0.30 + $revenue * 0.20 + $urgency * 0.30) * 20, 1);
    }
}
