<?php

namespace App\Http\Controllers\Admin\Publishing;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\CreateContractRequest;

use App\Models\Contract;
use App\Models\User\User;
use App\Models\User\Profile;

use App\Services\FileService;
use App\Services\ContractService;

class ContractController extends Controller
{
    protected $service;

    public function __construct(ContractService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('admin.publishing.contract.index');
    }

    public function store(CreateContractRequest $request)
    {
        // Get contract
        $context = [];

        try {
            $data = $request->only([
                'profileid',
                'number',
                'month',
                'year',
                'type',
            ]);

            // Create contract
            $result = $this->service->store($data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Contract created by admin.', $context);

            return redirect()->back()->with('success', 'Contract created by admin.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to create contract by admin.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function view(int $id)
    {
        // Get contract
        $contract = Contract::findOrFail($id);

        try {
            // Generate PDF and return
            return ContractService::generatePublishingContractPdf($contract);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function view_uploaded(int $id)
    {
        // Get contract
        $contract = Contract::findOrFail($id);

        try {
            return $this->service->show($contract);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }

    public function upload(int $id, Request $request)
    {
        // Get contract
        $contract = Contract::findOrFail($id);
        $context = [];

        try {
            $data = $request->only([
                'file',
            ]);

            // Upload contract
            $result = $this->service->upload($contract, $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing contract PDF uploaded by admin.', $context);

            return redirect()->back()->with('success', 'Publishing contract PDF uploaded by admin.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to upload contract by admin.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function delete_pdf($id)
    {
        // Get publishing
        $contract = Contract::findOrFail($id);
        $context = [];

        try {
            $result = $this->service->deletePdf($contract);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Publishing PDF deleted.', $context);

            return redirect()->back()->with('success', 'Publishing contract PDF deleted.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to delete publishing contract PDF.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function destroy(int $id)
    {
        // Get contract
        $contract = Contract::findOrFail($id);
        $context = [
            'model_id' => $id,
            'model_type' => get_class($contract)
        ];

        try {

            // Delete contract
            $result = $this->service->destroy($contract);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id'] = $result->getKey();
                $context['model_type'] = get_class($result);
            }

            // Log success
            Log::info('Contract deleted by admin.', $context);

            return redirect()->back()->with('success', 'Contract deleted by admin.');
        } catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack'] = $th->getTraceAsString();

            // Log error
            Log::error('Failed to delete contract by admin.', $context);

            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    /**
     * AJAX
     */
    public function get_data(Request $request)
    {
        try {
            // Searching
            $query = Contract::where('type', 'publishing')
                ->select(
                    'contracts.id as id',
                    'contracts.created_at as created_at',
                    'contracts.type as type',
                    'contracts.number as number',
                    'contracts.month as month',
                    'contracts.year as year',
                    'contracts.sort as sort',
                    'contracts.docnum as docnum',
                    'publishings.id as publishingid',
                    'publishings.title as title',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname"),
                    DB::raw("COALESCE(CONCAT_WS(' ', publishing_songwriters.firstname, publishing_songwriters.lastname), CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname)) AS songwriter")
                )
                ->leftJoin('user_profiles', 'user_profiles.id', 'contracts.profileid')
                ->leftJoin('publishing_signees', function ($join) {
                    $join->on('publishing_signees.id', '=', 'contracts.owner_id')
                         ->where('contracts.owner_type', '=', 'App\Models\Publishing\Signee');
                })
                ->leftJoin('publishings', function ($join) {
                    $join->on('publishings.id', '=', 'contracts.owner_id')
                         ->where('contracts.owner_type', '=', 'App\Models\Publishing\Publishing')
                         ->orOn('publishings.id', '=', 'publishing_signees.publishingid');
                })
                ->leftJoin('publishing_songwriters', 'publishing_songwriters.id', 'publishing_signees.songwriterid');

            $recordsTotal = $query->get()->count();

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                    $q->orWhere('month', 'like', "%{$search}%");
                    $q->orWhere('year', 'like', "%{$search}%");
                    $q->orWhere('docnum', 'like', "%{$search}%");
                    $q->orWhereRaw("CONCAT_WS(' ', publishing_songwriters.firstname, publishing_songwriters.lastname) LIKE ?", ["%{$search}%"]);
                    $q->orWhereRaw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) LIKE ?", ["%{$search}%"]);
                });
            }

            // Sorting
            $columns = ['songwriter', 'sort', 'title', 'fullname', 'created_at'];
            $orderColumn = $columns[$request->input('order.0.column')];
            $orderDir = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered = $query->get()->count();

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $data = $query
                ->skip($start)
                ->take($length)
                ->get();

            $data->transform(function ($item) {
                $contract = Contract::with(['owner', 'file'])->find($item->id);
                $item->file = (bool) $contract->file;
                $item->title = $contract->owner instanceof Publishing ? $contract->owner->title : $contract->owner?->publishing?->title;
                $item->requestid = $contract->owner instanceof Publishing ? $contract->owner->id : $contract->owner?->publishing?->id;
                $item->songwriter = $contract->owner instanceof Publishing ? $contract->owner->requestor?->full_name : $contract->owner?->full_name;

                return $item;
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }
}
