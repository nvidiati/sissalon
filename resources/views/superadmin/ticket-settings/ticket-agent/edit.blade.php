<div class="modal-header">
    <h4 class="modal-title">@lang('app.edit') @lang('app.agent')</h4>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <form class="form-horizontal ajax-form" id="update-ticket-agent-form" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <!-- text input -->
            <input type="hidden" name="id" value="{{$agentUser->id}}">
            <div class="col-md-12">
                <div class="form-group">
                    <label>@lang('app.name')<span class="required-span">*</span></label>
                    <input type="text" class="form-control form-control-lg" name="name" value="{{$agentUser->name}}" autocomplete="off">
                </div>
            </div>

            <!-- text input -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>@lang('app.email')<span class="required-span">*</span></label>
                    <input type="email" class="form-control form-control-lg" name="email" value="{{$agentUser->email}}" autocomplete="off">
                </div>
            </div>

            <!-- text input -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>@lang('app.password')</label>
                    <input type="password" class="form-control form-control-lg" name="password">
                    <span class="help-block">@lang('messages.leaveBlank')</span>
                </div>
            </div>

            <div class="col-md-12">
                <h6 class="text-primary">@lang('app.dateOfJoining')</h6>
                <div class="form-group">
                    <input type="text" class="form-control form-control-lg" name="joining_date" id="joining_date" value="{{  \Carbon\Carbon::parse($agentUser->created_at)->format($settings->date_format.' '.$settings->time_format) }}" autocomplete="off">
                </div>
            </div>

            <!-- text input -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>@lang('app.mobile')<span class="required-span">*</span></label>
                    <div class="form-row">
                        <div class="col-md-4 mb-2">
                            <select name="calling_code" id="calling_code" class="form-control select2">
                                @foreach ($calling_codes as $code => $value)
                                    <option value="{{ $value['dial_code'] }}">
                                        {{ $value['dial_code'] . ' - ' . $value['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control form-control-lg" name="mobile">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i>
        @lang('app.cancel')</button>
    <button type="button" onclick="updateTicketAgent()" class="btn btn-success"><i class="fa fa-check"></i>
        @lang('app.submit')</button>
</div>


<script>
    $('#joining_date').datetimepicker({
        format: '{{ $date_picker_format }}',
        locale: '{{ $settings->locale }}',
        allowInputToggle: true,
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-arrow-up",
            down: "fa fa-arrow-down",
            previous: "fa fa-angle-double-left",
            next: "fa fa-angle-double-right"
        }
    }).on('dp.change', function(e) {
        $('#joining_date').val(moment(e.date).format('YYYY-MM-DD'));
    });
    
    $('.select2').select2();
    function updateTicketAgent() {
        $.easyAjax({
            url: '{{ route('superadmin.ticket-agents.update', $agentUser->id) }}',
            container: '#update-ticket-agent-form',
            type: "POST",
            data: $('#update-ticket-agent-form').serialize(),
            success: function(response) {
                if (response.status == "success") {
                    $.unblockUI();
                    $(modal_default).modal('hide');
                    ticketAgentTable._fnDraw();
                }
            }
        })
    }
</script>
