<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('notification');
    }

    public function markAsRead($id)
    {
        try {
            if($id == 'all') {
                current_user()->unreadNotifications->markAsRead();
            }
            else {
                // Verify the notification belongs to the current user
                $notification = current_user()->notifications()->where('id', $id)->first();

                if(!$notification) {
                    return response()->json([
                        'success' => false,
                        'errors' => 'Notification not found'
                    ], 404);
                }

                $notification->markAsRead();
            }

            return response()->json([
                'success' => true
            ], 200);

        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false ,
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
