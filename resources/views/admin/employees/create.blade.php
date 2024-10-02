@extends('layouts.master')

@push('head-css')
    <style>
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #999;
        }
        .select2-dropdown .select2-search__field:focus, .select2-search--inline .select2-search__field:focus {
            border: 0px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            margin: 0 13px;
        }
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #cfd1da;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            cursor: pointer;
            float: right;
            font-weight: bold;
            margin-top: 8px;
            margin-right: 15px;
        }
        .select2 {
            width: 100%;
        }
        .required-span {
            color:red;
        }
        #add-group {
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">@lang('app.add') @lang('menu.employee')</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form role="form" id="createForm"  class="ajax-form" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-12">
                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.name')<span class="required-span">*</span></label>
                                    <input type="text" class="form-control form-control-lg" name="name" value="" autocomplete="off">
                                </div>

                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.email')<span class="required-span">*</span></label>
                                    <input type="email" class="form-control form-control-lg" name="email" value="" autocomplete="off">
                                </div>

                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.password')<span class="required-span">*</span></label>
                                    <input type="password" class="form-control form-control-lg" name="password">
                                </div>

                                <div class="form-group">
                                    <label>@lang('app.location')<span class="required-span">*</span></label>
                                    <select name="location[]" id="location" class="form-control form-control-lg select2" multiple="multiple">
                                        <option disabled >@lang('app.selectEmployee') @lang('app.location')</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }} </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.mobile')<span class="required-span">*</span></label>
                                    <div class="form-row">
                                        <div class="col-md-2 mb-2">
                                            <select name="calling_code" id="calling_code" class="form-control select2">
                                                @foreach ($calling_codes as $code => $value)
                                                    <option value="{{ $value['dial_code'] }}">
                                                        {{ $value['dial_code'] . ' - ' . $value['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-10">
                                            <input type="text" class="form-control" name="mobile">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>@lang('app.employeeGroup')</label>
                                    <div class="form-row">
                                        <div class="col-md-11 mr-0">
                                            <div class="input-group">
                                                <select name="group_id" id="group_id" class="form-control form-control-lg select2">
                                                    <option value="0">@lang('app.selectEmployeeGroup')</option>
                                                    @foreach($groups as $group)
                                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1 ml-0">
                                            <div class="input-group-append ml-0">
                                                <button class="btn btn-success" id="add-group" type="button"><i class="fa fa-plus"></i> @lang('app.add')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>@lang('app.assignRole')<span class="required-span">*</span></label>
                                    <select name="role_id" id="role_id" class="form-control form-control-lg select2">
                                        <option value="0">@lang('app.selectEmployeeRole')</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>@lang('app.assignServices')</label>
                                    <select name="business_service_id[]" id="business_service_id" class="form-control form-control-lg select2" multiple="multiple">
                                        <option value="0">@lang('app.selectServices')</option>
                                        @foreach($business_services as $business_service)
                                            <option value="{{ $business_service->id }}">{{ $business_service->name }} ( {{ $business_service->location->name }} ) </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="exampleInputPassword1">@lang('app.image')</label>
                                    <div class="card">
                                        <div class="card-body">
                                            <input type="file" id="input-file-now" name="image" accept=".png,.jpg,.jpeg" data-default-file="{{ asset('img/default-avatar-user.png')  }}" class="dropify"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="button" id="save-form" class="btn btn-success btn-light-round"><i
                                                class="fa fa-check"></i> @lang('app.save')</button>
                                </div>

                            </div>
                        </div>

                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection

@push('footer-js')
    <script>
        $('.dropify').dropify({
            messages: {
                default: '@lang("app.dragDrop")',
                replace: '@lang("app.dragDropReplace")',
                remove: '@lang("app.remove")',
                error: '@lang('app.largeFile')'
            }
        });

        $('body').on('click', '#add-group', function() {
            window.location = '{{ route("admin.employee-group.create") }}';
        })

        $('body').on('click', '#save-form', function() {
            $.easyAjax({
                url: '{{route('admin.employee.store')}}',
                container: '#createForm',
                type: "POST",
                redirect: true,
                file:true
            })
        });
    </script>
@endpush
