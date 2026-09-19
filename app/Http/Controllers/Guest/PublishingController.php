<?php

namespace App\Http\Controllers\Guest;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;

use App\Http\Requests\Publishing\RejectRequest;

use App\Services\Publishing\PublishingService;

class PublishingController extends Controller
{
    protected $publishingService;

    public function __construct(PublishingService $publishingService)
    {
        $this->publishingService = $publishingService;
    }

    public function show(Request $request)
    {
        $signee = $request->signee;
        $token = $request->token;

        return view('guest.sign.publishing.sign', compact('signee', 'token'));
    }

    public function update(Request $request)
    {
        // Get signee
        $signee = $request->signee;
        $context = [];

        try {
            $data = $request->only(['name', 'date']);
            $result = $this->publishingService->signeeUpdatePublisher($signee, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Signee data updated.', $context);

            return response()->json([
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to update signee data.', $context);

            return response()->json([
                'success' => false,
                'error' => $th->getMessage()
            ], 422);
        }
    }

    public function approve(Request $request)
    {
        $request->validate([
            'accept_terms' => 'required|in:on',
            'signature' => 'nullable|string',
            'upload-signature' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        // Get signee
        $signee = $request->signee;
        $context = [];

        // Validate that at least one signature source is provided
        $signatureInput = $request->input('signature');
        $hasUpload = $request->hasFile('upload-signature');

        // Check if signature input is empty base64 string
        $isCanvasEmpty = empty($signatureInput) || $signatureInput === 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        if ($isCanvasEmpty && !$hasUpload) {
            return redirect()->back()->withErrors('Please draw a signature or upload a signature file to approve the contract.');
        }

        try {
            // Store signature file
            $signatureFile = $hasUpload ? $request->file('upload-signature') : $signatureInput;
            \App\Services\FileService::store($signatureFile, $signee, $signee->publishing->requestor, 'signature', 'files');

            $data = $request->only(['accept_terms']);
            $result = $this->publishingService->signeeApprove($signee, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Signee has approved a publishing request with signature.', $context);

            return redirect()->back()->with('success', 'Request approved.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Signee failed to approve publishing request.', $context);

            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function reject(RejectRequest $request)
    {
        // Get signee model
        $signee = $request->signee;
        $context = [];

        try {
            $result = $this->publishingService->signeeReject($signee, $request->reason);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Signee has rejected a publising request.', $context);

            return redirect()->back()->with('success', 'Request rejected.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Signee failed to reject publishing request.', $context);

            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
