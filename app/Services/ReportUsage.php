<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportUsage
{
    public const LABELS = [
        'streams' => 'STREAMING',
        'creations' => 'CREATION',
        'video_views' => 'VIDEO VIEW',
        'promotion_advertising' => 'PROMOTIONADVERTISING',
        'nonpromotion_advertising' => 'NONPROMOTIONADVERTISING',
        'other_units' => 'Unit lainnya',
    ];

    private const TYPES = [
        'streams' => ['STREAM', 'STREAMING'],
        'creations' => ['CREATION', 'CREATIONS'],
        'video_views' => ['VIDEOVIEW', 'VIDEOVIEWS'],
        'promotion_advertising' => ['PROMOTIONADVERTISING'],
        'nonpromotion_advertising' => ['NONPROMOTIONADVERTISING'],
    ];

    public static function display(?string $value): string
    {
        $value = trim(str_replace('_', ' ', (string) $value));
        return $value === '' ? '-' : $value;
    }

    public static function type(?string $value): string
    {
        $value = str_replace(['_', '-', ' '], '', strtoupper(trim((string) $value)));
        foreach (self::TYPES as $metric => $aliases) {
            if (in_array($value, $aliases, true)) return $metric;
        }
        return 'other_units';
    }

    public static function expression(string $metric, string $type = 'sales_type', string $quantity = 'quantity'): string
    {
        // Column names are internal constants, never request input.
        $normalized = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE($type, ''))), '_', ''), '-', ''), ' ', '')";
        $aliases = $metric === 'other_units' ? array_merge(...array_values(self::TYPES)) : self::TYPES[$metric];
        $condition = $normalized.($metric === 'other_units' ? ' NOT IN (' : ' IN (').implode(',', array_map(fn ($alias) => "'$alias'", $aliases)).')';
        return "CASE WHEN $condition THEN COALESCE($quantity,0) ELSE 0 END";
    }

    public static function metrics(string $type = 'sales_type', string $quantity = 'quantity'): string
    {
        return implode(', ', array_map(fn ($metric) => 'SUM('.self::expression($metric, $type, $quantity).') as '.$metric, array_keys(self::LABELS)));
    }

    public static function categoryExpression(string $type = 'sales_type'): string
    {
        $normalized = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE($type, ''))), '_', ''), '-', ''), ' ', '')";
        $sql = 'CASE';
        foreach (self::TYPES as $metric => $aliases) {
            $category = $metric === 'video_views' ? 'VIDEO_VIEW' : self::LABELS[$metric];
            $sql .= ' WHEN '.$normalized.' IN ('.implode(',',array_map(fn ($alias) => "'$alias'",$aliases)).") THEN '".$category."'";
        }
        return $sql." ELSE COALESCE(NULLIF(UPPER(TRIM($type)),''),'Tidak diketahui') END";
    }

    public static function customerRows(): Builder
    {
        // The multi aggregate retains sales_type; platform/track aggregates have already merged it.
        $source = DB::table('report_aggregate_multi')->select('profileid','sales_month','platform','country','artist_name','trackid','client_rupiah_exact');
        foreach (array_keys(self::LABELS) as $metric) {
            $source->selectRaw(self::expression($metric).' as '.($metric === 'streams' ? 'quantity' : $metric));
        }
        return DB::query()->fromSub($source, 'usage_rows');
    }
}
