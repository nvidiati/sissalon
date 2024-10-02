<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon icon -->
    <link rel="icon" href="{{$frontThemeSettings->favicon_url}}" type="image/x-icon" />

    <link rel="manifest" href="{{ asset('favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    <meta name="theme-color" content="#ffffff">

    <title>{{ $pageTitle . ' | ' . $settings->company_name}}</title>

    <!-- SEO Keywords And Description-->
    <meta name='description' content='{{ $frontThemeSettings->seo_description}}' />
    <meta name='keywords' content='{{$frontThemeSettings->seo_keywords}}' />

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    <style>
        :root {
            --main-color: {{ Auth::user()->hasRole('superadmin') ? $superAdminThemeSetting->primary_color : (Auth::user()->hasRole(['administrator', 'employee']) ? $adminThemeSetting->primary_color : $customerThemeSetting->primary_color) }};
            --active-color: {{ Auth::user()->hasRole('superadmin') ? $superAdminThemeSetting->secondary_color : (Auth::user()->hasRole(['administrator', 'employee']) ? $adminThemeSetting->secondary_color : $customerThemeSetting->secondary_color) }};
            --sidebar-bg: {{ Auth::user()->hasRole('superadmin') ? $superAdminThemeSetting->sidebar_bg_color : (Auth::user()->hasRole(['administrator', 'employee']) ? $adminThemeSetting->sidebar_bg_color : $customerThemeSetting->sidebar_bg_color) }};
            --sidebar-color: {{ Auth::user()->hasRole('superadmin') ? $superAdminThemeSetting->sidebar_text_color : (Auth::user()->hasRole(['administrator', 'employee']) ? $adminThemeSetting->sidebar_text_color : $customerThemeSetting->sidebar_text_color) }};
            --topbar-color: {{ Auth::user()->hasRole('superadmin') ? $superAdminThemeSetting->topbar_text_color : (Auth::user()->hasRole(['administrator', 'employee']) ? $adminThemeSetting->topbar_text_color : $customerThemeSetting->topbar_text_color) }};
        }
        .d-none {
            display: none;
        }
        /* Blink for Webkit and others
        (Chrome, Safari, Firefox, IE, ...)
        */

        @-webkit-keyframes blinker {
            from {opacity: 1.0;}
            to {opacity: 0.0;}
        }

    </style>
    @stack('head-css')
</head>

<body class="hold-transition sidebar-mini @if ($user->rtl=='enabled') rtl @endif">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light border-bottom fixed-top align-items-start">
        <!-- Left navbar links -->
        <ul class="navbar-nav d-lg-none d-xl-none mt-1">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#"><i class="fa fa-bars"></i></a>
            </li>
        </ul>
        <div class="d-lg-flex d-block w-100 justify-content-between align-items-center w-100 mt-1">

            <div class="d-flex align-items-center visit_store">
                <a class="hidden-sm hidden-xs d-flex" href="{{ route('front.index') }}" target="_blank">
                <i class="fa fa-desktop"></i>
                <span class="d-none d-lg-block">@lang('app.visitStore')</span>
                </a>
            </div>

            @if (Auth::user()->is_admin || Auth::user()->is_superadmin)

                @php
                    $searchRoute = Auth::user()->is_superadmin ? route('superadmin.search.store') : route('admin.search.store');
                @endphp

                <form id="search" class="form-inline h-100 mx-3" action="{{ $searchRoute }}" method="POST">
                    @csrf
                    <div class="input-group input-group-custom">
                        <input name="search_key" id="search_key" class="form-control form-control-navbar" type="search" placeholder="{{ $user->is_superadmin ? __('front.searchSuperadmin') : __('front.searchBy') }}" aria-label="Search" autocomplete="off" required title="@lang('front.searchBy')" />
                        <div class="input-group-append">
                            <button id="search-button" class="btn btn-navbar" type="submit">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>

            @endif

            <ul class="navbar-nav lang-user-drop">
                @if ((Auth::user()->is_employee || Auth::user()->is_admin))
                    @if (\Session::get('loginRole') && $current_emp_role ? $current_emp_role->name == 'customer' : '' && !Auth::user()->is_superadmin)
                        <li class="dropdown d-flex justify-content-center align-items-center">
                            <a href="javascript:;" data-role-id = "{{$current_emp_role->id}}" class="badge-pill btn btn-dark btn-outline-light employee-role">
                                <i class="fa fa-sign-in"></i> @lang('app.loginAs') {{$original_emp_role->display_name}}
                            </a>
                        </li>
                    @endif

                    @if (!\Session::get('loginRole') && $current_emp_role->name != 'customer'  && !Auth::user()->is_superadmin)
                        <li class="dropdown d-flex justify-content-center align-items-center mr-2">
                            <a href="javascript:;" data-role-id = "{{$customer_role->id}}" class="badge-pill btn btn-dark btn-outline-light login-role">
                                <i class="fa fa-sign-in"></i> @lang('app.loginAsCustomer')
                            </a>
                        </li>
                    @endif
                @endif

                <li class="dropdown d-flex justify-content-center align-items-center">
                    <select class="form-control language-switcher">
                        @forelse($languages as $language)
                            <option value="{{ $language->language_code }}" @if($settings->locale == $language->language_code) selected @endif>
                                {{ ucfirst($language->language_name) }}
                            </option>
                        @empty
                            <option value="en" @if($settings->locale == "en") selected @endif>
                                English
                            </option>
                        @endforelse
                    </select>
                </li>

                <li class="profile-dropdown">
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        {{ csrf_field() }}
                    </form>
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <img src="{{ Auth::user()->user_image_url }}" class="img img-circle" height="28em" width="28em" alt="User Image"> <i class="fa fa-chevron-down ml-1"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="@if(Auth::user()->is_superadmin || Auth::user()->is_agent){{ route('superadmin.settings.index') }}#profile_page @else {{ route('admin.settings.index') }}#profile_page @endif" class="dropdown-item">
                            <i class="fa fa-user mr-2"></i> @lang('menu.profile')
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:;" title="Logout" id="logout-btn">
                            <i class="fa fa-power-off"></i>&nbsp; @lang('app.logout')
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-danger">
        <!-- Brand Logo -->
        <a href="{{ route('login') }}" class="brand-link">
            <img src="{{ $settings->logo_url }}" alt=" Logo" class="img brand-image">
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            @include('layouts.sidebar')
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <div class="content pt-2">
            <div class="container-fluid">
                @yield('content')
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
        <div class="p-3">
            <h5>Title</h5>
            <p>Sidebar content</p>
        </div>
    </aside>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <!-- To the right -->
        <div class="float-right d-none d-sm-inline">
            <strong> &copy; {{ \Carbon\Carbon::today()->year }} {{ ucwords($settings->company_name) }}. </strong>
        </div>
        <!-- Default to the left -->
    </footer>
</div>
<!-- ./wrapper -->


{{--Ajax Medium Modal--}}
    <div class="modal fade bs-modal-md in" id="application-modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" id="modal-data-application">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    <span class="caption-subject font-red-sunglo bold uppercase" id="modelHeading"></span>
                </div>
                <div class="modal-body">
                    @lang('modules.payments.loading')...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> @lang('app.cancel')</button>
                    <button type="button" class="btn btn-success"><i class="fa fa-check"></i> @lang('app.save')</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
{{--Ajax Medium Modal Ends--}}

{{--Ajax Large Modal--}}
    <div class="modal fade bs-modal-lg in" id="application-lg-modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" id="modal-lg-data-application">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    <span class="caption-subject font-red-sunglo bold uppercase" id="modalLgHeading"></span>
                </div>
                <div class="modal-body">
                    @lang('modules.payments.loading')...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i> @lang('app.cancel')</button>
                    <button type="button" class="btn btn-success"><i class="fa fa-check"></i> @lang('app.save')</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
{{--Ajax Large Modal Ends--}}

 <!-- also the modal itself -->
 <div id="myModalDefault" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog d-flex justify-content-center align-items-center">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading">Modal title</h5>
                <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal"
                    aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                Some content
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel rounded mr-3" data-dismiss="modal">Close</button>
                <button type="button" class="btn-primary rounded">Save changes</button>
            </div>
        </div>
    </div>
</div>
<!-- also the modal itself -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog d-flex justify-content-center align-items-center modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading">Modal title</h5>
                <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal"
                    aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                Some content
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel rounded mr-3" data-dismiss="modal">Close</button>
                <button type="button" class="btn-primary rounded">Save changes</button>
            </div>
        </div>
    </div>
</div>
<!-- also the modal itself -->
<div id="myModalXl" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog d-flex justify-content-center align-items-center modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modelHeading">Modal title</h5>
                <button type="button" onclick="removeOpenModal()" class="close" data-dismiss="modal"
                    aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body bg-grey">
                Some content
            </div>
        </div>
    </div>
</div>

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="{{ asset('js/sweetalert2/dist/sweetalert2.all.min.js')}}"></script>
<script src="{{ asset('js/app.js') }}"></script>

<script>
    const modal_default = '#myModalDefault';
    const modal_lg = '#myModal';
    const modal_xl = '#myModalXl';
    const modal_heading = '#modelHeading';


    "use strict";

    var redirecting = "{{ ' '.__('app.redirecting') }}"

    $('.select2').select2();
    $('.mytooltip').tooltip();

    $(window).resize(function () {
        $('.content').css('margin-top', $('nav.main-header').css('height'));
    }).resize();

    $('body').on('change', '.language-switcher', function () {
        const code = $(this).val();
        let url = "{{ Auth::user()->hasRole('superadmin') ? route('superadmin.changeLanguage', ':code') : route('admin.changeLanguage', ':code') }}";
        url = url.replace(':code', code);

        $.easyAjax({
            url: url,
            type: 'POST',
            container: 'body',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.status == 'success') {
                    location.reload();
                }
            }
        })
    })

    $('#application-modal, #application-lg-modal').on('shown.bs.modal', function () {
        let firstTextInput = $(this).find('.form-group>input[type="text"]').first();

        if (firstTextInput.length > 0) {
            if (firstTextInput.val() !== '') {
                $(this).find('.form-group>input[type="text"]').first().trigger('select');
            }
            else {
                $(this).find('.form-group>input[type="text"]').first().trigger('focus');
            }
        }
    })

    function languageOptions() {
        return {
            processing:     "@lang('modules.datatables.processing')",
            search:         "@lang('modules.datatables.search')",
            lengthMenu:    "@lang('modules.datatables.lengthMenu')",
            info:           "@lang('modules.datatables.info')",
            infoEmpty:      "@lang('modules.datatables.infoEmpty')",
            infoFiltered:   "@lang('modules.datatables.infoFiltered')",
            infoPostFix:    "@lang('modules.datatables.infoPostFix')",
            loadingRecords: "@lang('modules.datatables.loadingRecords')",
            zeroRecords:    "@lang('modules.datatables.zeroRecords')",
            emptyTable:     "@lang('modules.datatables.emptyTable')",
            paginate: {
                first:      "@lang('modules.datatables.paginate.first')",
                previous:   "@lang('modules.datatables.paginate.previous')",
                next:       "@lang('modules.datatables.paginate.next')",
                last:       "@lang('modules.datatables.paginate.last')",
            },
            aria: {
                sortAscending:  "@lang('modules.datatables.aria.sortAscending')",
                sortDescending: "@lang('modules.datatables.aria.sortDescending')",
            },
        }
    }

    $('body').on('click', '#logout-btn', function(e) {
        e.preventDefault()
        document.getElementById('logout-form').submit();
    });

    $('.login-role').click(function(){
        var roleId = $(this).data('role-id');

        $.easyAjax({
            url: '{{ route('admin.dashboard.role-login') }}',
            type: 'GET',
            redirect:true,
            data: {
                _token: '{{ csrf_token() }}',
                'roleId':roleId
            },
            success: function (response) {
                //
            }
        })
    })

    $('.employee-role').click(function(){
        var roleId = $(this).data('role-id');

        $.easyAjax({
            url: '{{ route('admin.dashboard.employee-login') }}',
            type: 'GET',
            redirect:true,
            data: {
                _token: '{{ csrf_token() }}',
                'roleId':roleId
            },
            success: function (response) {
                //
            }
        })
    })

</script>

@stack('footer-js')

</body>
</html>
