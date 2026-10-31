<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Models\Access\Role;
use App\Models\Access\Permission;
use App\Models\User\User;

class UserRolesController extends Controller
{
    public function index()
    {
        $roles      = Role::all();
        $admins = \App\Models\User\Admin::with('roles')->whereHas('roles')->get();
        $users = \App\Models\User\Admin::whereDoesntHave('roles')->get();

        return view('admin.user-roles.index', compact('roles','admins','users'));
    }

    
    public function create()
    {
        $permissions = Permission::all();
        return view('admin.user-roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        try {
            DB::transaction(function ()  use ($request) {
                $data = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'required|string|max:255',
                    'permissions' => 'required|array',
                ]);
    
                $role = Role::create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]);

                $role->permissions()->sync($data['permissions']);
            });

            return redirect()->route('admin.user-roles.index')->with('success', 'Role created successfully');
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function edit($id)
    {
        try  {
            $role           = Role::findOrFail($id);
            $permissions    = Permission::all();
            return view('admin.user-roles.edit', compact('role', 'permissions'));
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try  {
            $role = Role::findOrFail($id);
            DB::transaction(function () use ($role,$request) {
                $role->name         = $request->name;
                $role->description  = $request->description;

                $role->save();
                $role->permissions()->sync($request->permissions);
            });

            return redirect()->back()->with('success', 'Role has been updated.');
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function destroy($id)
    {
        try  {
            $role = Role::findOrFail($id);
            if( $role->users()->count() > 0 ) {
                throw new \Exception("Please remove all users from this role before deleting.");
            }
            $role->delete();
            return redirect()->route('admin.user-roles.index')->with('success', 'Role has been deleted.');
        }
        catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
            Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
            return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
