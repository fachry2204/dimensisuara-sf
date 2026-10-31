<?php

namespace App\Http\Controllers\Admin\Aggregator;

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
        $profiles = Profile::all();
        $eligibleProfiles = Profile::where('feature_aggregator', 1);
        $generatedContractUsers = (clone $eligibleProfiles)
            ->whereHas('contracts', fn ($query) => $query->where('type', 'aggregator'))
            ->count();
        $usersWithoutContract = (clone $eligibleProfiles)
            ->whereDoesntHave('contracts', fn ($query) => $query->where('type', 'aggregator'))
            ->count();

        return view('admin.aggregator.contract.index', compact('profiles', 'generatedContractUsers', 'usersWithoutContract'));
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
                'aggregator_rate',
                'system_rate',
                'contract_date',
            ]);

            // Create contract
            $result = DB::transaction(function () use ($data, $request) {
                $contract = $this->service->store($data);
                if ($request->user()->can('upload-contracts') && $request->hasFile('pdf')) {
                    $this->service->upload($contract, ['file' => $request->file('pdf')]);
                }
                return $contract;
            });

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Contract created by admin.', $context);

            return redirect()->back()->with('success', 'Contract created by admin.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to create contract by admin.', $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }

    public function pdf_upload(int $id, Request $request)
    {
        // Get contract
        $contract = Contract::findOrFail($id);
        $context = [];
        
        try {
            $data = $request->only([
                'file',
            ]);

            // Upload contract
            $result = $this->service->upload($contract , $data);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Contract uploaded by admin.', $context);

            return redirect()->back()->with('success', 'Contract uploaded by admin.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

            // Log error
            Log::error('Failed to upload contract by admin.', $context);
            
            return redirect()->back()->withInput()->withErrors($th->getMessage());
        }
    }
    
    public function stamp_upload(int $id, Request $request)
    {
        $contract = Contract::where('type', 'aggregator')->findOrFail($id);
        $request->validate(['stamped_pdf' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240']]);
        try {
            $this->service->uploadDigitalStamp($contract, $request->file('stamped_pdf'));
            return redirect()->back()->with('contract_stamp_id', $contract->id)->with('success', 'PDF kontrak bermaterai digital berhasil diupload.');
        } catch (\Throwable $exception) {
            Log::error('Failed to upload digitally stamped contract.', ['contract_id' => $id, 'message' => $exception->getMessage()]);
            return redirect()->back()->with('contract_stamp_id', $contract->id)->withErrors(['stamped_pdf' => 'Upload materai digital gagal. Silakan coba kembali.']);
        }
    }

    public function stamp_view(int $id)
    {
        $contract = Contract::where('type', 'aggregator')->findOrFail($id);
        $file = $contract->digitalStamp;
        abort_unless($file && Storage::disk($file->disk)->exists($file->identifier), 404);
        return Storage::disk($file->disk)->response($file->identifier, 'kontrak-bermaterai.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function view_raw(int $id)
    {
        // Get contract
        $contract = Contract::findOrFail($id);
        
        try {
            // Generate PDF and return
            return ContractService::generateAggregatorPdf($contract);
        }
        catch (\Throwable $th) {
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
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            abort(500, $th->getMessage());
        }
    }
    
    public function destroy(int $id)
    {
        // Get contract
        $contract = Contract::findOrFail($id);
        $context = [
            'model_id'      => $id,
            'model_type'    => get_class($contract)
        ];
        
        try {

            // Delete contract
            $result = $this->service->destroy($contract);

            // if the callback returns an Eloquent model, add its id
            if ($result instanceof \Illuminate\Database\Eloquent\Model) {
                $context['model_id']   = $result->getKey();
                $context['model_type'] = get_class($result);
            }
                
            // Log success
            Log::info('Contract deleted by admin.', $context);

            return redirect()->back()->with('success', 'Contract deleted by admin.');
        }
        catch (\Throwable $th) {

            // Add error message and stack trace
            $context['message'] = $th->getMessage();
            $context['stack']   = $th->getTraceAsString();

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
            // Get records with certain status
            $query = Contract::where('type', 'aggregator');
            $recordsTotal = $query->get()->count();

            // Searching
            $query = $query
                ->select(
                    'contracts.id as id',
                    'contracts.created_at as created_at',
                    'contracts.type as type',
                    'contracts.number as number',
                    'contracts.month as month',
                    'contracts.year as year',
                    'contracts.sort as sort',
                    'contracts.docnum as docnum',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname")
                )
                ->leftJoin('user_profiles', 'user_profiles.id', '=', 'contracts.profileid');

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                    $q->orWhere('month', 'like', "%{$search}%");
                    $q->orWhere('year', 'like', "%{$search}%");
                    $q->orWhere('docnum', 'like', "%{$search}%");
                    $q->orWhereRaw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) LIKE ?", ["%{$search}%"]);
                });
            }
            
            // Sorting
            $columns        = ['fullname','sort','created_at'];
            $orderColumn    = $columns[$request->input('order.0.column')];
            $orderDir       = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered    = $query->get()->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get();

            $data->transform(function($item) {
                $contract = Contract::find($item->id);
                $item->file   = $contract->file ? true : false;
                return $item;
            });
            
            return response()->json([
                'draw'              => intval($request->input('draw')),
                'recordsTotal'      => $recordsTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data,
            ], 200);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success'   => false ,
                'errors'    => $th->getMessage()
            ], 500);
        }
    }

    public function get_data_per_user(Request $request)
    {
        try {
            // Get records with certain status
            $query = Profile::where('feature_aggregator', 1)
                ->leftJoin('contracts', function ($join) {
                    $join->on('contracts.profileid', '=', 'user_profiles.id')
                        ->where('contracts.type', '=', 'aggregator');
                });

            $recordsTotal = $query->get()->count();

            // Searching
            $query = $query
                ->select(
                    'contracts.id as id',
                    'contracts.created_at as created_at',
                    'contracts.type as type',
                    'contracts.number as number',
                    'contracts.month as month',
                    'contracts.year as year',
                    'contracts.sort as sort',
                    'contracts.docnum as docnum',
                    'user_profiles.firstname as firstname',
                    'user_profiles.lastname as lastname',
                    DB::raw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) AS fullname")
                );

            // Searching
            if ($search = $request->input('search.value')) {
                $query->where(function($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                    $q->orWhere('month', 'like', "%{$search}%");
                    $q->orWhere('year', 'like', "%{$search}%");
                    $q->orWhere('docnum', 'like', "%{$search}%");
                    $q->orWhereRaw("CONCAT_WS(' ', user_profiles.firstname, user_profiles.lastname) LIKE ?", ["%{$search}%"]);
                });
            }
            
            // Sorting
            $columns        = ['fullname','sort','created_at'];
            $orderColumn    = $columns[$request->input('order.0.column')];
            $orderDir       = $request->input('order.0.dir');
            $query->orderBy($orderColumn, $orderDir);

            // Pagination
            $recordsFiltered    = $query->get()->count();

            $data = $query
                ->skip($request->input('start'))
                ->take($request->input('length'))
                ->get();

            $data->transform(function($item) {
                $contract = Contract::find($item->id);
                if($contract) {
                    $item->file   = $contract->file ? true : false;
                }
                
                return $item;
            });
            
            return response()->json([
                'draw'              => intval($request->input('draw')),
                'recordsTotal'      => $recordsTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data,
            ], 200);
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);

            return response()->json([
                'success'   => false ,
                'errors'    => $th->getMessage()
            ], 500);
        }
    }
}
