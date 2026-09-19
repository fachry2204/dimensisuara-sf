<?php

namespace App\Http\Controllers\Admin\Publishing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function index()
    {
        return view('admin.publishing.reporting.index');
    }
}
