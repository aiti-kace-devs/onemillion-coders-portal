<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Admin;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// class RegisteredUserController extends Controller
// {
//     /**
//      * Display the registration view.
//      *
//      * @return \Illuminate\View\View
//      */
//     public function create()
//     {
//         return view('auth.register');
//     }

//     /**
//      * Handle an incoming registration request.
//      *
//      * @param  \Illuminate\Http\Request  $request
//      * @return \Illuminate\Http\RedirectResponse
//      *
//      * @throws \Illuminate\Validation\ValidationException
//      */
//     public function store(Request $request)
//     {
//         $request->validate([
//             'name' => 'required|string|max:255',
//             'email' => 'required|string|email|max:255|unique:users',
//             'password' => 'required|string|confirmed|min:8',
//         ]);

//         Auth::login($admin = Admin::create([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->password),
//         ]));

//         event(new Registered($admin));

//         // return redirect('admin/dashboard');
//         return redirect(RouteServiceProvider::ADMIN_HOME);
//     }
// }

class RegisteredUserController extends Controller
{
    /**
     * Display a listing of admins.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admins = $admins = Admin::all();
        $courses = Course::all();
        return view('admin.index', compact('admins', 'courses'));
    }

    /**
     * Show the form for creating a new admin.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.create');
    }

    /**
     * Store a newly created admin in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $admin = new Admin();
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->password = Hash::make($request->password);
        $admin->is_super = $request->has('is_super') ? true : false;
        $admin->save();

        // $redirectPath = $admin->is_super ? 'admin.dashboard' : 'admin.dashboard';

        // return redirect()->route($redirectPath)->with([
        //     'flash' => 'Admin created successfully!',
        //     'key' => 'success'
        // ]);
        echo json_encode(['status' => 'true', 'message' => 'Admin created successfully!', 'reload' => url('admin/manage_admins')]);
    }

    /**
     * Show the form for editing the specified admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //dd($id, Admin::find($id));
        $roles = Role::all();
        $permissions = Permission::all();
        $courses = Course::all();

        $admin = Admin::findOrFail($id);
        return view('admin.edit', compact('admin', 'roles', 'permissions', 'courses'));
        // return view('admin.edit', compact('admin'));
    }

    /**
     * Update the specified admin in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = 'string|min:8|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        //$admin->is_super = $request->has('is_super') ? true : false;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        if (auth()->user()->hasRole('super-admin', 'admin')) {
            if ($request->has('roles')) {
                $admin->syncRoles($request->roles);
            } else {
                $admin->syncRoles([]);
            }

            // Sync direct permissions (optional)
            if ($request->has('permissions')) {
                $admin->syncPermissions($request->permissions);
            } else {
                $admin->syncPermissions([]);
            }
        }

        if ($request->has('courses')) {
            $admin->assignedCourses()->sync($request->courses);
        } else {
            $admin->assignedCourses()->detach();
        }
        // Sync roles
        // echo json_encode(['status' => 'true', 'message' => 'Admin updated successfully!', 'reload' => url('admin/manage_admins')]);

        $redirectPath = $admin->is_super ? 'admin.manage_admins' : 'admin.manage_admins';

        return redirect()
            ->route($redirectPath)
            ->with([
                'flash' => 'Admin updated successfully!',
                'key' => 'success',
            ]);
    }

    /**
     * Remove the specified admin from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();

        return redirect()
            ->route('admin.manage_admins')
            ->with([
                'flash' => 'Admin deleted successfully!',
                'key' => 'success',
            ]);
    }

    //Editing is_super admin status
    public function is_super_admin_status($id)
    {
        $admin = Admin::where('id', $id)->get()->first();

        if ($admin->is_super == 1) {
            $is_super = 0;
        } else {
            $is_super = 1;
        }

        $admin1 = Admin::where('id', $id)->get()->first();
        $admin1->is_super = $is_super;
        $admin1->update();
    }

    public function getAdminCourses(Admin $admin)
    {
        return response()->json($admin->assignedCourses->pluck('id'));
    }

    public function updateAdminCourses(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);

        $admin = Admin::find($request->admin_id);
        $admin->assignedCourses()->sync($request->courses);

        return response()->json(['success' => true]);
    }
}
