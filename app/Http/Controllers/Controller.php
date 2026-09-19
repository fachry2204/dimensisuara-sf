<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function safeAction (
        callable $callback,
        string $successMessage,
        string $errorMessage,
        array $context = []
    )
    {
        try {
            $result = $callback();

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info($successMessage, $context);

            return $result;
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error($errorMessage, $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
}
