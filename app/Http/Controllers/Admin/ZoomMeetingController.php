<?php

namespace App\Http\Controllers\admin;

use App\BusinessService;
use App\Http\Controllers\AdminBaseController;
use App\Http\Controllers\Controller;
use App\Package;
use Illuminate\Http\Request;

class ZoomMeetingController extends AdminBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        abort_if(!$this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('read_business_service'), 403);

        $total_business_services = BusinessService::count();
        $package = Package::find($this->settings->package_id);

        if(\request()->ajax())
        {
            $services = BusinessService::with('location')->get();

            return \datatables()->of($services)
                ->addColumn('action', function ($row) use ($total_business_services, $package) {
                    $action = '<div class="text-right">';

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('update_business_service')) {
                        $action .= '<a href="' . route('admin.business-services.edit', [$row->id]) . '" class="btn btn-primary btn-circle"
                          data-toggle="tooltip" data-original-title="'.__('app.edit').'"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                    }

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('create_business_service') && $total_business_services < $package->max_services && $package->max_services > 0) {
                        $action .= ' <a href="javascript:;" class="btn btn-warning btn-circle duplicate-row"
                        data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="'.__('app.duplicate').'"><i class="fa fa-clone" aria-hidden="true"></i></a>';
                    }

                    $action .= ' <a href="javascript:;" data-row-id="' . $row->id . '" class="btn btn-info btn-circle view-business_service"
                    data-toggle="tooltip" data-original-title="'.__('app.view').'"><i class="fa fa-eye" aria-hidden="true"></i></a> ';

                    if ($this->user->roles()->withoutGlobalScopes()->latest()->first()->hasPermission('delete_business_service')) {
                        $action .= ' <a href="javascript:;" class="btn btn-danger btn-circle delete-row"
                          data-toggle="tooltip" data-row-id="' . $row->id . '" data-original-title="'.__('app.delete').'"><i class="fa fa-times" aria-hidden="true"></i></a>';
                    }

                    $action .= '</div>';

                    return $action;
                })
                ->addColumn('image', function ($row) {
                    return '<img src="'.$row->service_image_url.'" class="img" width="120em" /> ';
                })
                ->editColumn('name', function ($row) {
                    return ucfirst($row->name);
                })
                ->editColumn('status', function ($row) {
                    if($row->status == 'active'){
                        return '<label class="badge badge-success">'.__('app.active').'</label>';
                    }
                    elseif($row->status == 'deactive'){
                        return '<label class="badge badge-danger">'.__('app.deactive').'</label>';
                    }
                })
                ->editColumn('location_id', function ($row) {
                    return ucfirst($row->location->name);
                })
                ->editColumn('category_id', function ($row) {
                    return ucfirst($row->category->name);
                })
                ->editColumn('price', function ($row) {
                    return currencyFormatter($row->price, myCurrencySymbol());
                })
                ->editColumn('discount_price', function ($row) {
                    return currencyFormatter($row->discounted_price, myCurrencySymbol());
                })
                ->editColumn('users', function ($row) {
                    $user_list = '';

                    foreach ($row->users as $key => $user) {
                        $user_list .= '<span class="badge badge-primary username-badge">'.$user->name.'</span>';
                    }

                    return $user_list == '' ? '--' : $user_list;
                })
                ->addIndexColumn()
                ->rawColumns(['action', 'image', 'status', 'users'])
                ->toJson();
        }

        return view('admin.business_service.index', compact('total_business_services', 'package'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /* public function create()
    {
        //
    } */

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


}
