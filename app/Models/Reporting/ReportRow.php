<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\Track;
use App\Models\Release;
use App\Models\User\Profile;

class ReportRow extends Model
{
    protected $fillable = [
        'trackid',
        'profileid',

        'report_import_id',
        'reporting_month',
        'sales_month',
        'platform',
        'region',
        'label_name',
        'artist_name',
        'release_title',
        'track_title',
        'upc',
        'isrc',
        'release_catalog',
        'subscription_type',
        'release_type',
        'sales_type',
        'quantity',
        'currency_code',

        'unit_price',
        'mechanical_fee',
        'gross_revenue',
        'client_share_rate',
        'net_revenue',
        'country',
        'rate',

        'idr_rate',

        'rupiah',
        'rupiah_exact',
        'aggregator_rupiah',
        'aggregator_rupiah_exact',
        'client_rupiah',
        'client_rupiah_exact',
        
        'status',
        'releaseid',
    ];

    /**
     * Get the batch that owns the ReportRow
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ReportImport::class, 'report_import_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'trackid');
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'releaseid');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profileid');
    }

    public static function baseAggregateQuery()
    {
        return self::query()
            ->select(
                'profileid',
                'trackid',
                'platform',
                'country',
                'artist_name',
                'sales_type',
                'reporting_month',
                'sales_month',
                DB::raw('SUM(quantity) as quantity'),
                DB::raw('SUM(rupiah_exact) as rupiah_exact'),
                DB::raw('SUM(aggregator_rupiah_exact) as aggregator_rupiah_exact'),
                DB::raw('SUM(client_rupiah_exact) as client_rupiah_exact'),
            )
            ->whereNotNull('profileid')
            ->where('status', 'finalized')
            ->groupBy(
                'profileid',
                'trackid',
                'platform',
                'country',
                'artist_name',
                'sales_type',
                'reporting_month',
                'sales_month'
            );
    }
}
