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
use App\Models\User\Admin as User;

class UserAdminController extends Controller
{
    public function store(CreateAdminRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                if($request->type === 'existing') {
                    $user = User::findOrFail($request->userid);
                    $user->roles()->syncWithoutDetaching([$request->role]);
                }
                else {
                    $user = User::create([
                        'name'      => $request->name,
                        'email'     => $request->email,
                        'password'  => bcrypt($request->password),
                    ]);
                    $user->roles()->attach($request->role);
                }

                Log::info('User ID ' . $user->id . ' with email ' . $user->email . ' has been created as Admin by Admin ID ' . auth()->user()->id . ' with role ID ' . $request->role . '.');
            });

            return redirect()->back()->with('success', 'Admin has been created.');
        }
        catch (\Throwable $th) {
            Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
            return redirect()->back()->withErrors($th->getMessage());
        }
    }

    public function revoke($id)
    {
        try  {
            if (auth()->user()->id == $id) {
                throw new \Exception('You cannot revoke your own admin access.');
            }

            DB::transaction(function () use ($id) {
                $user = User::findOrFail($id);
                $user->roles()->detach();
                $user->save();
            });

            return redirect()->back()->with('success', 'Admin access has been revoked.');
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
    

    // public function edit($id)
    // {
    //     try  {
    //         $role           = Role::findOrFail($id);
    //         $permissions    = Permission::all();
    //         return view('admin.user-roles.edit', compact('role', 'permissions'));
    //     }
    //     catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
    //         Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
    //         return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
    //     }
    //     catch (\Throwable $th) {
    //         Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
    //         return redirect()->back()->withErrors($th->getMessage());
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     try  {
    //         $role = Role::findOrFail($id);
    //         DB::transaction(function () use ($role,$request) {
    //             $role->name         = $request->name;
    //             $role->description  = $request->description;

    //             $role->save();
    //             $role->permissions()->sync($request->permissions);
    //         });

    //         return redirect()->back()->with('success', 'Role has been updated.');
    //     }
    //     catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
    //         Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
    //         return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
    //     }
    //     catch (\Throwable $th) {
    //         Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
    //         return redirect()->back()->withErrors($th->getMessage());
    //     }
    // }

    // public function destroy($id)
    // {
    //     try  {
    //         $role = Role::findOrFail($id);
    //         if( $role->users()->count() > 0 ) {
    //             throw new \Exception("Please remove all users from this role before deleting.");
    //         }
    //         $role->delete();
    //         return redirect()->route('admin.user-roles.index')->with('success', 'Role has been deleted.');
    //     }
    //     catch( \Illuminate\Database\Eloquent\ModelNotFoundException $e ) {
    //         Log::warning($e->getMessage(), ['stack' => $e->getTraceAsString()]);
    //         return redirect()->route('admin.user-roles.index')->withErrors($e->getMessage());
    //     }
    //     catch (\Throwable $th) {
    //         Log::error($th->getMessage(), ['stack' => $th->getTraceAsString()]);
    //         return redirect()->back()->withErrors($th->getMessage());
    //     }
    // }
}
