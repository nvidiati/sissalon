<?php

namespace App\Http\Controllers\Admin;

use App\EmployeeSchedule;
use App\User;
use Illuminate\Http\Request;
use App\Helper\Reply;
use Carbon\Carbon;
use App\Http\Controllers\AdminBaseController;
use App\Role;

class EmployeeScheduleSettingController extends AdminBaseController
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_employee_schedule'), 403);

        if (request()->ajax()) {

            $employees = User::AllEmployees()->get();

            return datatables()->of($employees)
                ->addColumn('action', function ($row) {
                    $action = '<div class="text-right"><a href="javascript:;" data-row-id="' . $row->id . '" class="btn btn-info btn-circle view-employee-detail" onclick="this.blur()" data-toggle="tooltip" data-original-title="'.__('app.view').'"><i class="fa fa-eye" aria-hidden="true"></i></a></div>';
                    return $action;
                })
                ->editColumn('name', function ($row) {
                    return ucfirst($row->name);
                })
                ->addIndexColumn()
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('admin.employee-schedule.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_employee_schedule'), 403);

        $schedules = EmployeeSchedule::with('employee')->where('employee_id', $id)->get();
        $serviceLocations = User::with('location')->where('id', $id)->first();
        $serviceLocations = $serviceLocations->location;
        return view('admin.employee-schedule.show', compact('schedules', 'serviceLocations'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee_schedule')){
            return Reply::error( __('messages.accessDenied'));
        }

        $updateSchedule = EmployeeSchedule::with('location')->findOrFail($id);

        /* @phpstan-ignore-next-line */
        $startTime = Carbon::createFromFormat('H:i a', $request->updateStartTime, $updateSchedule->location->timezone->zone_name)->setTimezone($this->settings->timezone)->format('H:i:s');

        /* @phpstan-ignore-next-line */
        $updateSchedule->start_time = $startTime;

        /* @phpstan-ignore-next-line */
        $endTime = Carbon::createFromFormat('H:i a', $request->updateEndTime, $updateSchedule->location->timezone->zone_name)->setTimezone($this->settings->timezone)->format('H:i:s');

        $updateSchedule->end_time = $endTime;

        $updateSchedule->save();

        $schedules = EmployeeSchedule::with('employee')->where('employee_id', $request->empid)->get();
        $serviceLocations = User::with('location')->where('id', $request->empid)->first();
        $serviceLocations = $serviceLocations->location;
        $tableView = view('admin.employee-schedule.tableview', compact('schedules', 'serviceLocations'))->render();

        return Reply::dataOnly(['html' => $tableView]);

    }

    public function updateWorking(Request $request, $id)
    {
        if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_employee_schedule')){
            return Reply::error( __('messages.accessDenied'));
        }

        $updateworking = EmployeeSchedule::findOrFail($id);

        $updateworking->is_working = $request->isWorking;
        $updateworking->save();

        $schedules = EmployeeSchedule::with('employee')->where('employee_id', $request->empid)->get();
        $serviceLocations = User::with('location')->where('id', $request->empid)->first();
        $serviceLocations = $serviceLocations->location;
        $tableView = view('admin.employee-schedule.tableview', compact('schedules', 'serviceLocations'))->render();

        return Reply::dataOnly(['html' => $tableView]);

    }

}

