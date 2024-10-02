<?php

namespace App\Http\Controllers\Admin;

use App\BookingTime;
use App\BusinessService;
use App\EmployeeGroup;
use App\EmployeeGroupService;
use App\EmployeeSchedule;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Controllers\AdminBaseController;
use App\Http\Requests\Employee\StoreRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\User;
use Illuminate\Http\Request;
use App\Http\Requests\Employee\ChangeRoleRequest;
use App\Location;
use App\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeController extends AdminBaseController
{

    public function __construct()
    {
        parent::__construct();
        view()->share('pageTitle', __('menu.employee'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function index()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_employee'), 403);

        if(\request()->ajax()){
            $employees = User::with('location')->otherThanCustomers()->get();
            $roles = Role::whereNotIn('name', ['superadmin', 'agent'])->where('company_id', $this->user->company_id)->get();

            return \datatables()->of($employees)
                ->addColumn('action', function ($row) {
                    $action = '<div class="text-right">';

                    if ($this->user->isAbleTo('update_employee') && $row->id !== $this->user->id) {
                        if ($row->role->display_name == 'Administrator') {
                            $action .= '';
                        }
                        else {
                            $action .= '<a href="' . route('admin.employee.edit', [$row->id]) . '" class="btn btn-primary btn-circle"
                                data-toggle="tooltip" data-original-title="'.__('app.edit').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                        }
                    }

                    if (($this->user->is_admin || $this->user->isAbleTo('delete_employee')) && $row->id !== $this->user->id) {

                        if ($row->role->display_name == 'Administrator') {
                            $action .= '';
                        }
                        else {
                            $action .= ' <a href="javascript:;" class="btn btn-danger btn-circle delete-row"
                            data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="'.__('app.delete').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                        }
                    }

                    $action .= '</div>';

                    return $action;
                })
                ->addColumn('image', function ($row) {
                    return '<img src="'.$row->user_image_url.'" class="img" width="120em"/> ';
                })
                ->editColumn('name', function ($row) {
                    return ucfirst($row->name);
                })
                ->editColumn('location', function ($row) {
                    $location_list = '';

                    foreach ($row->location as $location) {
                        $location_list .= '<span class="badge badge-primary username-badge">'.$location->name.'</span>';
                    }

                    return $location_list == '' ? '--' : $location_list;
                })
                ->editColumn('group_id', function ($row) {
                    return !is_null($row->group_id) ? ucfirst($row->employeeGroup->name) : '--';
                })
                ->editColumn('assignedServices', function ($row) {
                    $service_list = '';

                    foreach ($row->services as $key => $service) {
                        $service_list .= '<span class="badge badge-primary username-badge">'.$service->name.'</span>';
                    }

                    return $service_list == '' ? '--' : $service_list;
                })
                ->addColumn('role_name', function ($row) use ($roles){

                    if (($row->id === $this->user->id) || !$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee')) {
                        return $row->role->display_name;
                    }

                    $roleOption = '<select name="role_id" class="form-control role_id" data-user-id="'.$row->id.'">';

                    foreach ($roles as $role){
                        $roleOption .= '<option ';

                        if($row->roles->first()->id == $role->id){
                            $roleOption .= ' selected ';
                        }

                        $roleOption .= 'value="'.$role->id.'">'.ucwords($role->display_name).'</option>';
                    }

                    $roleOption .= '</select>';

                    return $roleOption;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'image', 'role_name', 'location', 'assignedServices'])
                ->toJson();
        }

        return view('admin.employees.index');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_employee'), 403);

        $groups = EmployeeGroup::all();
        $roles = Role::whereNotIn('name', ['superadmin', 'agent'])->where('company_id', $this->user->company_id)->get();
        $business_services = BusinessService::with('location')->get();
        $serviceLocations = $business_services->unique('location');
        $locations = [];

        foreach($serviceLocations as $serviceLocation)
        {
            $locations[] = $serviceLocation->location;
        }

        return view('admin.employees.create', compact('groups', 'roles', 'business_services', 'locations'));

    }

    /**
     * @param StoreRequest $request
     * @return array
     */
    public function store(StoreRequest $request)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_employee'), 403);

        if($this->total_employees >= $this->package->max_employees){ return Reply::error( __('messages.maxEmpLimit.'));
        }

        $company = User::with('company')->where('id', auth()->user()->id)->first();

        $user = new User();

        $user->company_id  = $company->company->id;
        $user->name     = $request->name;
        $user->email    = $request->email;

        $service_array = array();

        if ($request->group_id !== '0') {
            $user->group_id = $request->group_id;

            $services_lists  = EmployeeGroupService::where('employee_groups_id', $request->group_id)->get();

            foreach ($services_lists as $key => $services_list) {
                $service_array [] = $services_list->business_service_id;
            }

        }

        $user->calling_code = $request->calling_code;
        $user->mobile = $request->mobile;

        if($request->password != ''){
            $user->password = $request->password;
        }

        if ($request->hasFile('image')) {
            $user->image = Files::upload($request->image, 'avatar');
        }

        $user->save();

        if ($request->group_id !== '0')
        {
            $user->services()->sync($service_array);
        }

        /* Assign services to users */
        $business_service_id = $request->business_service_id;

        if($business_service_id)
        {
            $assignedSerives   = array();

            foreach ($business_service_id as $key => $service_id)
            {
                if($business_service_id[$key] != 0) {
                    $assignedSerives[] = $business_service_id[$key];
                }
            }

            $user->services()->sync($assignedSerives);
            $user->location()->sync($request->location);
        }

        // add default employee role
        $user->attachRole($request->role_id);

        // add employee schedule
        if($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_employee_schedule')) {
            $this->addOrEditSchedule($user);
        }

        return Reply::redirect(route('admin.employee.index'), __('messages.createdSuccessfully'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $employee = User::where('id', $id)->first();
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee') || $employee->id === $this->user->id || $employee->is_customer || $this->user->id === $employee->id, 403);

        $groups = EmployeeGroup::all();
        $roles = Role::whereNotIn('name', ['superadmin', 'agent'])->where('company_id', $this->user->company_id)->get();

        /* push all previous assigned services to an array */
        $selectedServices = array();
        $assignedServices = User::with(['services'])->find($id);

        foreach ($assignedServices->services as $key => $services)
        {
            array_push($selectedServices, $services->id);
        }

        /* Locations of employee */
        $selectedLocations = [];

        $userLocations = User::with(['location'])->find($id);

        /* @phpstan-ignore-next-line */
        $userLocation = $userLocations->location;

        foreach ($userLocation as $location)
        {
            $selectedLocations[] = $location->id;
        }

        $businessServices = BusinessService::with('location')->active()->get();
        $serviceLocations = $businessServices->unique('location');
        $locations = [];

        foreach($serviceLocations as $serviceLocation)
        {
            $locations[] = $serviceLocation->location;
        }

        return view('admin.employees.edit', compact('employee', 'groups', 'roles', 'selectedServices', 'selectedLocations', 'businessServices', 'locations'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateRequest $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, $id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee'), 403);

        $user = User::findOrFail($id);
        /* save new edited services */
        $services = $request->service_id;

        if($services){
            $assignedSerives = array();

            foreach ($services as $key => $service){
                $assignedSerives[] = $services[$key];
            }

            $user->services()->sync($assignedSerives);
        }
        else{
            $user->services()->detach();
        }

        $user->name         = $request->name;
        $user->email        = $request->email;

        if ($request->group_id !== '0')
        {
            $user->group_id = $request->group_id;

            DB::table('business_service_user')->where(['user_id' => $user->id])->delete();

            $service_array = array();
            $services_lists  = EmployeeGroupService::where('employee_groups_id', $request->group_id)->get();

            foreach ($services_lists as $key => $services_list) {
                $service_array [] = $services_list->business_service_id;
            }

            $user->services()->attach($service_array);
        }

        if($request->password != ''){
            $user->password = $request->password;
        }

        if (($request->mobile != $user->mobile || $request->calling_code != $user->calling_code) && $user->mobile_verified == 1) {
            $user->mobile_verified = 0;
        }

        $user->mobile       = $request->mobile;
        $user->calling_code = $request->calling_code;

        if ($request->hasFile('image')) {
            $user->image = Files::upload($request->image, 'avatar');
        }

        $user->save();


        if($request->location)
        {
            $schedules = EmployeeSchedule::where('employee_id', $user->id)->where('company_id', $user->company_id)->get()->unique('location_id');

            $employeeLocation = [];

            foreach ($schedules as $schedule)
            {
                $employeeLocation [] = $schedule->location_id;
            }

            $diff = array_diff($employeeLocation, $request->location);
            $count = count($diff);

            if($count > 0)
            {
                for($i = 0; $i < $count; $i++)
                {
                    $user->location()->detach($diff[$i]);
                    EmployeeSchedule::where('location_id', $diff[$i])->where('employee_id', $user->id)->delete();
                }
            }

            foreach ($request->location as $location)
            {
                if(!in_array($location, $employeeLocation))
                {
                    $user->location()->attach($location);
                    $this->addOrEditSchedule($user);
                }
            }

        }
        else
        {
            $user->services()->detach();
        }

        $user->syncRoles([$request->role_id]);

        if($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee_schedule')) {

            $this->addOrEditSchedule($user);
        }

        return Reply::redirect(route('admin.employee.index'), __('messages.updatedSuccessfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_employee') || $user->id === $this->user->id, 403);

        $employeeSchedules = EmployeeSchedule::where('employee_id', $user->id)->get();

        if($employeeSchedules->count() > 0)
        {
            foreach($employeeSchedules as $employeeSchedule)
            {
                $employeeSchedule->delete();
            }
        }

        $user->delete();
        return Reply::success(__('messages.recordDeleted'));
    }

    public function changeRole(ChangeRoleRequest $request)
    {
        $user = User::findOrFail($request->user_id);
        $empRole = Role::where('name', 'employee')->where('company_id', Auth::user()->company_id)->first();

        $user->syncRoles([$request->role_id, $empRole->id]);

        Artisan::call('cache:clear');

        if($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_employee_schedule')) {

            $this->addOrEditSchedule($user);
        }

        return Reply::success(__('messages.roleChangedSuccessfully'));
    }

    public function addOrEditSchedule(User $user)
    {
        /* @phpstan-ignore-next-line */
        $locations = $user->location;

        foreach($locations as $location)
        {
            $employee = EmployeeSchedule::where('employee_id', $user->id)->where('location_id', $location->id)->count();

            if($user->hasRole('employee') && $employee == 0){

                $bookingTime = BookingTime::where('company_id', $user->company_id)->where('location_id', $location->id)->get();

                foreach($bookingTime as $bookingTimes){

                    $employeeSchedule = new EmployeeSchedule();
                    $employeeSchedule->employee_id = $user->id;
                    $employeeSchedule->location_id = $location->id;
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
            else {

                if(!($user->hasRole('employee')) && $employee != 0){
                    $employeeSchedules = EmployeeSchedule::where('employee_id', $user->id)->get();

                    foreach($employeeSchedules as $employeeSchedule){
                        $employeeSchedule->delete();
                    }
                }

            }
        }

    }

}
