<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

use App\Models\User\Profile;
use App\Models\User\User;

class AdminHelper
{
    public static function getAllAdmins()
    {
        $ids = DB::table('access_user_roles')->get()->pluck('user_id')->toArray();
        return User::whereIn('id', $ids)->get();
    }

}