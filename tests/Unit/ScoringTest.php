<?php

namespace Tests\Unit;

use App\Enums\Condition;
use App\Enums\Criticality;
use App\Enums\Priority;
use App\Enums\RiskLevel;
use App\Support\Scoring;
use PHPUnit\Framework\TestCase;

/** Rumus harus identik dengan modul Odoo gpch_asset_care. */
class ScoringTest extends TestCase
{
    public function test_health_score_weights_and_condition(): void
    {
        $all = fn (int $v) => array_fill_keys(array_keys(Scoring::HEALTH_WEIGHTS), $v);

        $this->assertSame([80.0, Condition::Good], Scoring::health($all(80)));
        $this->assertSame([90.0, Condition::Excellent], Scoring::health($all(90)));
        $this->assertSame([59.0, Condition::Poor], Scoring::health($all(59)));
        $this->assertSame([39.0, Condition::Critical], Scoring::health($all(39)));

        // Kondisi fisik berbobot 25%.
        [$score] = Scoring::health(['physical_condition' => 100] + $all(0));
        $this->assertSame(25.0, $score);
    }

    public function test_criticality_and_asset_risk(): void
    {
        $this->assertSame([5.0, Criticality::Critical], Scoring::criticality(array_fill_keys(Scoring::CRITICALITY_FIELDS, 5)));
        $this->assertSame([3.4, Criticality::High], Scoring::criticality(['safety_impact' => 5, 'guest_impact' => 4, 'revenue_impact' => 3, 'operational_impact' => 3, 'replacement_difficulty' => 2]));
        $this->assertSame([2.0, Criticality::Low], Scoring::criticality(array_fill_keys(Scoring::CRITICALITY_FIELDS, 2)));
        // Nilai di luar 1–5 dipotong.
        $this->assertSame([1.0, Criticality::Low], Scoring::criticality(array_fill_keys(Scoring::CRITICALITY_FIELDS, 0)));

        $this->assertSame([60.0, Priority::Urgent], Scoring::assetRisk(40, 5));
        $this->assertSame([8.0, Priority::Low], Scoring::assetRisk(80, 2));
        $this->assertSame([18.0, Priority::Medium], Scoring::assetRisk(70, 3));
    }

    public function test_risk_matrix_and_capex_priority(): void
    {
        $this->assertSame([25, RiskLevel::Critical], Scoring::riskMatrix(5, 5));
        $this->assertSame([12, RiskLevel::High], Scoring::riskMatrix(3, 4));
        $this->assertSame([6, RiskLevel::Medium], Scoring::riskMatrix(2, 3));
        $this->assertSame([4, RiskLevel::Low], Scoring::riskMatrix(2, 2));

        $this->assertSame(60.0, Scoring::capexPriority(3, 3, 3, 3));
        $this->assertSame(100.0, Scoring::capexPriority(5, 5, 5, 5));
    }
}
