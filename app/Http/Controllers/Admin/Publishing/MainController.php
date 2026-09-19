<?php

namespace App\Http\Controllers\Admin\Publishing;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;

use App\Http\Requests\Publishing\StorePublishingRequest;

use App\Models\Publishing\Publishing;
use App\Models\Publishing\Signee;

use App\Services\ContractService;
use App\Services\Publishing\PublishingService;

class MainController extends Controller
{
    protected $contractService;
    protected $publishingService;
    public function __construct(ContractService $contractService, PublishingService $publishingService)
    {
        $this->contractService = $contractService;
        $this->publishingService = $publishingService;
    }

    /**
     * View routes
     */
    public function index()
    {
        return view('admin.publishing.index');
    }

    public function create()
    {
        $publishing = new Publishing();
        $songwriters = [];
        return view('admin.publishing.create', compact('publishing', 'songwriters'));
    }

    public function show($id)
    {
        $publishing = Publishing::with([
            'requestor',
            'rejector',
            'signees.signature',
            'publishingContract.file',
            'statementContract.file',
            'statement.file',
            'nuon.file',
        ])->findOrFail($id);

        return view('admin.publishing.show', compact('publishing'));
    }

    public function store(StorePublishingRequest $request)
    {
        $data = $request->all();
        $profileid = $request->profileid;

        // if validation passes, create the record
        $publishing = $this->publishingService->create($data, $profileid);

        // redirect to the appropriate page based on the publishing status
        if ($publishing->status === 'draft') {
            return redirect()->route('customer.publishing.edit', $publishing->id)->with('success', 'Publishing request created.');
        }

        return redirect()->route('admin.publishing.show', $publishing->id)->with('success', 'Publishing request created.');
    }

    /**
     * Get data for datatables
     */
    public function get_data(Request $request, $status = null)
    {
        try {
            // Get records with certain status
            $query = Publishing::query();
            if ($status != 'all') {
                $query = $query->where('publishings.status', $status);
            } else {
                $query = $query->where('publishings.status', '!=', 'draft');
            }
            $recordsTotal = $query->get()->count();

            // Searching
            $query = $query
                ->select(
                    'publishings.id as id',
                    'publishings.title as title',
                    'publishings.performer as performer',
                    'publishings.due_date as due_date',
                    'publishings.finished_at as finished_at',
                    'publishings.created_at as created_at',
                    'publishings.contract_expired_at as contract_expired_at',
                    'publishings.status as status',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname")
                )
                ->leftJoin('user_profiles', 'user_profiles.id', '=', 'publishings.profileid');

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                    $q->orWhere('performer', 'like', "%{$search}%");
                    $q->orWhereRaw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) LIKE ?", ["%{$search}%"]);
                });
            }

            // Sorting
            $columns = ['title', 'status', 'due_date', 'finished_at', 'fullname', 'created_at', 'contract_expired_at'];
            $orderColumn = $columns[$request->input('order.0.column')];
            $orderDir = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered = $query->get()->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get();

            return response()->json([
                "draw" => intval($request->input('draw')),
                "recordsTotal" => $recordsTotal,
                "recordsFiltered" => $recordsFiltered,
                "data" => $data,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function resend($id)
    {
        $signee = Signee::findOrFail($id);
        $context = [
            'parent_id' => $signee->publishing->id,
            'parent_type' => get_class($signee->publishing)
        ];

        try {
            $result = $this->publishingService->resend($signee);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Notification sent.', $context);

            return redirect()->back()->with('success', 'Notification sent.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to send notification.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    /**
     * Status methods
     */
    public function approve($id, Request $request)
    {
        $publishing = Publishing::findOrFail($id);
        $context = [];

        try {
            $request->validate([
                'contract_number'  => 'required|integer|min:1',
                'contract_month'   => 'required|integer|between:1,12',
                'contract_year'    => 'required|integer|min:2020',
                'statement_number' => 'required|integer|min:1',
                'statement_month'  => 'required|integer|between:1,12',
                'statement_year'   => 'required|integer|min:2020',
            ]);

            $data = $request->only([
                'contract_number',
                'contract_month',
                'contract_year',
                'statement_number',
                'statement_month',
                'statement_year',
            ]);

            $result = $this->publishingService->adminApprove($publishing, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing request approved.', $context);

            return redirect()->back()->with('success', 'Publishing request approved.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to approve publishing request.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function reject($id, Request $request)
    {
        $publishing = Publishing::findOrFail($id);
        $context = [];

        try {
            $data = $request->only([
                'reason',
            ]);

            $result = $this->publishingService->adminReject($publishing, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing request rejected.', $context);

            return redirect()->back()->with('success', 'Publishing request rejected.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to reject publishing request.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    /**
     * Contracts
     */
    public function upload($id, string $type, Request $request)
    {
        // Get contract
        $publishing = Publishing::findOrFail($id);
        $context = [];

        try {
            $data = $request->only([
                'file',
            ]);

            // Upload contract
            $result = $this->publishingService->upload($publishing, $data, $type);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing statement/nuon statement PDF uploaded by admin.', $context);

            return redirect()->back()->with('success', 'Publishing statement/nuon statement PDF uploaded by admin.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to upload publishing contract PDF.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function view_statement($id)
    {
        // Get publishing
        $publishing = Publishing::findOrFail($id);

        try {
            // Load statement contract for docnum
            $contract = $publishing->statementContract;

            // Generate PDF and return
            return $this->contractService->generatePublishingStatementPdf($publishing, $contract);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function view_statement_pdf($id)
    {
        // Get publishing
        $publishing = Publishing::findOrFail($id);

        try {
            return $this->publishingService->show($publishing, 'statement');
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function view_nuon($id)
    {
        // Get publishing
        $publishing = Publishing::findOrFail($id);

        try {
            // Generate PDF and return
            return $this->contractService->generatePublishingNuonPdf($publishing);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function view_nuon_pdf($id)
    {
        // Get publishing
        $publishing = Publishing::findOrFail($id);

        try {
            return $this->publishingService->show($publishing, 'nuon');
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function delete_pdf($id, $type)
    {
        // Get publishing
        $publishing = Publishing::findOrFail($id);
        $context = [
            'type' => $type
        ];

        try {
            $result = $this->publishingService->deletePdf($publishing, $type);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing PDF deleted.', $context);

            return redirect()->back()->with('success', 'Publishing PDF deleted.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to delete publishing PDF.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
}
