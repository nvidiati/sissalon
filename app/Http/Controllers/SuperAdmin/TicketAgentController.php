<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Role;
use App\User;
use App\Notifications\NewUser;
use Froiden\Envato\Helpers\Reply;
use App\Http\Requests\TicketAgent\Store;
use App\Http\Requests\TicketAgent\Update;
use App\Http\Controllers\SuperAdminBaseController;
use App\Permission;
use Carbon\Carbon;

class TicketAgentController extends SuperAdminBaseController
{

    public function index()
    {
        if (request()->ajax()) {

            $agent = User::withoutGlobalScopes()->allAgents()->get();

            return \datatables()->of($agent)
                ->addColumn('action', function ($row) {
                    $action = '<div class="text-right">';
                    $action .= '<a href="javascript:;" class="btn btn-primary btn-circle edit-ticket-agent" data-row-id="' . $row->id . '"
                        data-toggle="tooltip" data-original-title="'.__('app.edit').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>';

                    $action .= ' <a href="javascript:;" class="btn btn-danger btn-circle delete-ticket-agent"
                        data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="'.__('app.delete').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                        $action .= '</div>';

                    return $action;
                })
                ->editColumn('name', function ($row) {
                    return ucfirst($row->name);
                })
                ->editColumn('email', function ($row) {
                    return $row->email;
                })
                ->editColumn('phone', function ($row) {
                    return !is_null($row->formatted_mobile) ? $row->formatted_mobile : '--';
                })
                ->editColumn('joiningDate', function ($row) {
                    return $row->created_at->timezone($this->settings->timezone)->format($this->settings->date_format.' '.$this->settings->time_format);
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'status'])
                ->toJson();
        }
    }

    public function create()
    {
        return view('superadmin.ticket-settings.ticket-agent.create');
    }

    public function store(Store $request)
    {
        
        $time = Carbon::now()->setTimezone($this->settings->timezone);
        $dateTime = $request->joining_date .' '. $time->toTimeString();
        $joiningDate = Carbon::parse($dateTime, $this->settings->timezone)->setTimezone('UTC');

        $user = new User();

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->calling_code = $request->calling_code;
        $user->mobile = $request->mobile;
        $user->created_at = $joiningDate;
        $user->save();

        $role = Role::where('name', 'agent')->first();
        $user->attachRole($role->id);
        
        $readPermission = Permission::where('name', 'read_ticket')->first()->id;
        $UpdatePermission = Permission::where('name', 'update_ticket')->first()->id;
        $role->syncPermissions([$readPermission, $UpdatePermission]);

        $user->notify(new NewUser($request->password));
        return Reply::success( __('messages.createdSuccessfully'));
    }

    public function edit($id)
    {
        $this->agentUser = User::findOrFail($id);

        return view('superadmin.ticket-settings.ticket-agent.edit', $this->data);
    }

    public function update(Update $request, $id)
    {
        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->calling_code = $request->calling_code;
        $user->mobile = $request->mobile;
        $user->created_at = $request->joining_date;

        if ($request->password) {
            $user->password = $request->password;
        }

        $user->update();

        return Reply::success( __('messages.updatedSuccessfully'));
    }

    public function destroy($id)
    {
        User::destroy($id);

        return Reply::success(__('messages.recordDeleted'));
    }

}
