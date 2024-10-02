<style>
    #update-app {
        text-decoration: none;
    }
    .alert-danger{
        color: rgb(99, 8, 8) !important;
        background-color: rgb(224, 162, 162) !important;
    }
    .alert-info{
        color: rgb(30, 101, 207) !important;
        background-color: rgb(214, 235, 232) !important;
    }
</style>

@php($envatoUpdateCompanySetting = \Froiden\Envato\Functions\EnvatoUpdate::companySetting())

@if(!is_null($envatoUpdateCompanySetting->supported_until))
    <div class="" id="support-div">
        @if(\Carbon\Carbon::parse($envatoUpdateCompanySetting->supported_until)->isPast())
            <div class="col-md-12 alert alert-danger ">
                <div class="col-md-6">
                    @lang('app.supportExpiredNote')
                    <b><span id="support-date">{{\Carbon\Carbon::parse($envatoUpdateCompanySetting->supported_until)->format('d M, Y')}}</span></b>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ config('froiden_envato.envato_product_url') }}" target="_blank"
                       class="btn btn-inverse btn-small">@lang('app.renewSupport')<i class="fa fa-shopping-cart"></i></a>
                    <a href="javascript:;" onclick="getPurchaseData();" class="btn btn-inverse btn-small">@lang('app.refresh')
                        <i class="fa fa-refresh"></i></a>
                </div>
            </div>

        @else
            <div class="col-md-12 alert alert-info">
                @lang('app.supportExpiredNote')<b><span
                            id="support-date">{{\Carbon\Carbon::parse($envatoUpdateCompanySetting->supported_until)->format('d M, Y')}}</span></b>
            </div>
        @endif
    </div>
@endif

@php($updateVersionInfo = \Froiden\Envato\Functions\EnvatoUpdate::updateVersionInfo())
@if(isset($updateVersionInfo['lastVersion']))
    <div class="alert alert-danger col-md-12">
        <p> @lang('messages.updateAlert')</p>
        <p>@lang('messages.updateBackupNotice')</p>
    </div>

    <div class="alert alert-info col-md-12">
        <div class="row">
            <div class="col-md-9">
                <h6><i class="fa fa-gift"></i> @lang('modules.update.newUpdate') <label
                    class="badge badge-success">{{ $updateVersionInfo['lastVersion'] }}</label></h6>
            </div>
            <br><br>
            <div class="col-md-3 text-right mb-1">
                <a id="update-app" href="javascript:;"
                class="btn btn-success btn-small">@lang('modules.update.updateNow') <i
                    class="fa fa-download"></i></a>
            </div>
            <br><br>
            <div class="col-md-12">
                <p>@lang('app.alert'): @lang('app.updateNote')</p>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-12">
                <h6><i class="fa fa-clock-o"></i> @lang('modules.update.updateSummary')</h6>
                <hr>
                <p>{!! $updateVersionInfo['updateInfo'] !!}</p>
            </div>
        </div>
    </div>

    <div id="update-area" class="m-t-20 m-b-20 col-md-12 white-box hide">
        @lang('modules.payments.loading')...
    </div>
@else
    <div class="alert alert-success col-md-12">
        <div class="col-md-12">@lang('app.youHaveLatestVersion').</div>
    </div>
@endif
