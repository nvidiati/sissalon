<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Role;
use App\User;
use App\Module;
use App\Permission;
use App\BookingTime;

use App\Helper\Reply;
use App\EmployeeSchedule;
use App\Helper\Permissions;
use Illuminate\Http\Request;
use App\Http\Requests\RolePermission\StoreRole;
use App\Http\Requests\RolePermission\AddMembers;
use App\Http\Controllers\SuperAdminBaseController;

class RolePermissionSettingController extends SuperAdminBaseController
{

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('superadmin.role-permission.create');
    }

    public function data()
    {
        $roles = Role::whereNotIn('name', ['superadmin', 'administrator', 'employee', 'customer'])->whereNull('company_id')->get();

        return datatables()->of($roles)
            ->addColumn('action', function ($row) {
                $action = '<div class="text-right">';

                if (!in_array($row->name, config('laratrust_seeder.default_roles'))) {
                    $action .= '<a href="javascript:;" data-role-id="' . $row->id . '"
                        class="btn btn-primary btn-circle edit-role" onclick="this.blur()" data-toggle="tooltip" data-original-title="'.__('app.edit').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                    <a href="javascript:;" data-role-id="' . $row->id . '"
                        class="btn btn-danger btn-circle delete-role" data-toggle="tooltip" data-original-title="'.__('app.delete').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                }
                else {
                    $action .= '<span class="text-danger">' . __('messages.defaultRoleCantDelete') . '</span>';

                }

                $action .= '</div>';

                return $action;
            })
            ->editColumn('display_name', function ($row) {
                return ucfirst($row->display_name);
            })
            ->editColumn('description', function ($row) {
                return $row->description ?? '--';
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getMembers($role_id)
    {
        $members = User::whereHas('roles', function ($query) use ($role_id) {
            $query->where('id', $role_id);
        })->with('roles');

        return datatables()->of($members)
            ->addColumn('action', function ($row) use ($role_id){
                $action = '<div class="text-right">';

                $action .= '<a href="javascript:;" data-user-id="' . $row->id . '" data-role-id="' . $role_id . '"
                    class="btn btn-sm btn-danger btn-rounded delete-member">' . __('app.remove') . '</a>';

                $action .= '</div>';

                return $action;
            })
            ->editColumn('name', function ($row) {
                return ucwords($row->name);
            })
            ->editColumn('roles.display_name', function ($row) {
                return $row->role->display_name;
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make(true);
    }

    public function addRole(StoreRole $request)
    {

        $role = new Role();

        $role->display_name = $request->display_name;
        $role->name = $request->name;

        if (!is_null($request->description)) {
            $role->description = $request->description;
        }

        $role->save();

        return Reply::success(__('messages.roleCreatedSuccessfully'));
    }

    public function addMembers(AddMembers $request, $role_id)
    {
        $users = User::whereIn('id', $request->user_ids)->get();

        foreach ($users as $user) {
            $user->syncRoles([$role_id]);
        }

        return Reply::success(__('messages.membersAddedSuccessfully'));
    }

    public function removeMember(Request $request)
    {

        $user = User::findOrFail($request->user_id);
        $user->detachRoles([$request->role_id]);
        
        return Reply::success(__('messages.memberRemovedSuccessfully'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $role = Role::where('id', $request->roleId)->first();

        if ($request->assignPermission === 'yes') {
            $role->attachPermissions([$request->permissionId]);
        }
        else {
            $role->detachPermissions([$request->permissionId]);
        }

        return Reply::dataOnly(['status' => 'success']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // @codingStandardsIgnoreLine
    public function show($id)
    {
        $usersToAdd = User::doesnthave('roles')->get();
        return view('superadmin.role-permission.show', compact('usersToAdd', 'id'));
    }

    // @codingStandardsIgnoreLine
    public function getMembersToAdd($id)
    {
        $usersToAdd = User::doesnthave('roles')->get();
        return Reply::dataOnly(['usersToAdd' => $usersToAdd]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);

        $view = view('superadmin.role-permission.edit_form', compact('role'))->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  StoreRole $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRole $request, $id)
    {
        $role = Role::findOrFail($id);

        $role->display_name = $request->display_name;
        $role->name = $request->name;

        if (!is_null($request->description)) {
            $role->description = $request->description;
        }

        $role->save();

        return Reply::success(__('messages.roleUpdatedSuccessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $employees = $role->users()->pluck('id')->toArray();

        foreach ($role->users as $user) {
            $role = ROle::whereNull('company_id')->where('name', 'agent')->first();
            $user->syncRoles([$role->id]);
        }

        $this->addScheduleOnRoleDelete($employees);

        $role->delete();

        return Reply::success(__('messages.roleDeletedSuccessfully'));
    }

    public function toggleAllPermissions(Request $request)
    {
        $role = Role::where('id', $request->roleId)->first();

        if ($request->assignPermission === 'no') {
            $permissions = [];
        }
        else {
            $permissions = Permission::whereIn('module_id', Module::whereIn('name', Permissions::getModules($this->user->role))->pluck('id'))->get();
        }

        $role->syncPermissions($permissions);

        return Reply::dataOnly(['status' => 'success']);
    }

    public function addOrEditSchedule(AddMembers $request, $role_id)
    {
        $role = Role::findOrFail($role_id)->name;

        foreach($request->user_ids as $users){

            $employee = EmployeeSchedule::where('employee_id', $users)->count();

            if($role == 'agent' && $employee == 0){

                $bookingTime = BookingTime::all();
                $this->schedule($users);
            }
            else {

                if(!($role == 'agent') && $employee != 0){

                    $emp = EmployeeSchedule::where('employee_id', $users)->get();

                    foreach($emp as $employee){
                        $employee->delete();
                    }
                }

            }
        }
    }

    public function addScheduleOnRemove(Request $request)
    {
        $employee = EmployeeSchedule::where('employee_id', $request->user_id)->count();

        if(!($request->user_id == 'agent') && $employee == 0){
            $this->schedule($request->user_id);
        }
    }

    public function addScheduleOnRoleDelete($employees)
    {

        foreach($employees as $employee){

            $employeeSchedule = EmployeeSchedule::where('employee_id', $employee)->count();

            if(!($employee == 'agent') && $employeeSchedule == 0){

                $this->schedule($employee);
            }
        }

    }

    public function schedule($userId)
    {

        $bookingTime = BookingTime::all();

        foreach($bookingTime as $bookingTimes){

                $employeeSchedule = new EmployeeSchedule();
                $employeeSchedule->employee_id = $userId;
                $employeeSchedule->start_time = $bookingTimes->start_time;
                $employeeSchedule->end_time = $bookingTimes->end_time;
                $employeeSchedule->days = $bookingTimes->day;

            if($bookingTimes->status == 'enabled'){
                $employeeSchedule->is_working = 'yes';
            }
            else {
                $employeeSchedule->is_working = 'no';
            }

            $employeeSchedule->save();
        }
    }

}
