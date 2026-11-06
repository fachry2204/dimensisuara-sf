<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSubtypes
{
    public static function available(): bool
    {
        return Schema::hasColumn('report_rows', 'sales_sub_type');
    }

    public static function breakdown(Builder $rows, string $revenue = 'client_rupiah_exact'): Collection
    {
        $type = ReportUsage::categoryExpression();
        $subtype = self::available() ? "NULLIF(UPPER(TRIM(sales_sub_type)),'')" : 'NULL';
        $platform = "COALESCE(NULLIF(TRIM(platform),''),'Tidak diketahui')";
        // Raw rows retain subtypes that the older aggregate tables merged together.
        return DB::query()->fromSub(clone $rows, 'subtype_rows')
            ->selectRaw("$platform as platform, $type as sales_type, $subtype as sales_sub_type, SUM(COALESCE(quantity,0)) as quantity, SUM(COALESCE($revenue,0)) as earnings")
            ->groupByRaw("$platform, $type, $subtype")
            ->orderBy('platform')->orderBy('sales_type')->orderBy('sales_sub_type')->get();
    }

    public static function customer(int $profileId, string $start, string $end): Collection
    {
        if (!Schema::hasTable('report_rows') || !Schema::hasTable('report_imports')) return collect();
        $rows = DB::table('report_rows as rr')->join('report_imports as ri', 'ri.id', '=', 'rr.report_import_id')
            ->where('rr.profileid', $profileId)->where('rr.status', 'finalized')
            ->whereIn('ri.status', ['pending_rate','completed'])
            ->whereBetween('rr.sales_month', [$start,$end])->select('rr.*');
        return self::breakdown($rows);
    }

    public static function safeCsvText(?string $value): string
    {
        $value = (string) $value;
        if ($value === '-') return $value;
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
    }

    public static function export(Collection $rows, string $filename, string $revenueLabel = 'Estimasi penghasilan IDR'): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows,$revenueLabel) {
            $out = fopen('php://output','w');
            fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['Platform','Sales Type','Sales Sub Type','Quantity',$revenueLabel],',','"','');
            foreach ($rows as $row) {
                fputcsv($out,[self::safeCsvText($row->platform),self::safeCsvText(ReportUsage::display($row->sales_type)),self::safeCsvText(ReportUsage::display($row->sales_sub_type)),$row->quantity,$row->earnings],',','"','');
            }
            fclose($out);
        },$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
