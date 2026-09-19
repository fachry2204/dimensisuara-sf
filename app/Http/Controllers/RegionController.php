<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;


class RegionController extends Controller
{
    public function provinces(Request $request) {
        return DB::table('wilayah')->whereRaw("kode NOT LIKE '%.%'")
            ->select('kode as id', 'nama as name')
            ->orderBy('name')
            ->get();
    }

    public function children(Request $request) {
        $parentCode = $request->query('parent_code');

        // Child = starts with parent + '.' and only one more dot
        return DB::table('wilayah')->where('kode', 'like', "$parentCode.%")
            ->whereRaw('LENGTH(kode) - LENGTH(REPLACE(kode, ".", "")) = ?', [substr_count($parentCode, '.') + 1])
            ->select('kode as id', 'nama as name')
            ->orderBy('name')
            ->get();
    }

    public function postcode(Request $request) {
        $parentCode = $request->query('parent_code');

        return DB::table('wilayah_kodepos')->where('kode', $parentCode)
            ->select('kodepos as id', 'kodepos as name')
            ->orderBy('name')
            ->get();
    }
}
