<?php

namespace App\Actions\DataAnalysis;

use App\Models\QueryAnalytics;

class SaveQueryAnalyticsAction
{
    public function invoke(array $analyticsData): QueryAnalytics
    {
        return QueryAnalytics::create($analyticsData);
    }
} 