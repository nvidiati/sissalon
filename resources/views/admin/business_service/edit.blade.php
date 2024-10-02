@extends('layouts.master')

@section('content')
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
    #discounted-price {
        font-size: 1.5rem;
    }
    #employee_ids {
        width: 100%;
    }
    #select-image-button {
        margin-bottom: 10px;
        display: none
    }
    .dropzone .dz-preview .dz-image img {
        height: 100%;
        width: 100%;
        object-fit: cover;
    }
    .required-span {
        color:red;
    }
</style>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title">@lang('app.edit') @lang('app.service')</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form role="form" id="createForm"  class="ajax-form" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" id="id" value="{{ $businessService->id }}">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang("app.serviceType")</label>
                                    <div>
                                        <label class="radio-inline">
                                            <input type="radio" name="service_type" class="checkbox" value="offline" id="service_type" {{ $businessService->service_type == 'offline' ? 'checked' : ''}}>
                                            @lang('app.serviceOffline')
                                        </label>
                                        @if (in_array('Zoom Meeting', $package_modules) && $zoomStatus === 'active')
                                            <label class="radio-inline pl-lg-2">
                                                <input type="radio" name="service_type" class="checkbox" value="online" id="service_type" {{ $businessService->service_type == 'online' ? 'checked' : ''}}>
                                                @lang('app.serviceOnline')
                                            </label>
                                        @endif

                                    </div>
                                </div>
                            </div>
                            <div class="col-md">
                                <!-- text input -->
                                <div class="form-group">
                                    <label>@lang('app.service') @lang('app.name')<span class="required-span">*</span></label>
                                    <input type="text" name="name" id="name" value="{{ $businessService->name }}" class="form-control form-control-lg" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="form-group">
                                    <label>@lang('app.service') @lang('app.slug')<span class="required-span">*</span></label>
                                    <input type="text" name="slug" id="slug" value="{{ $businessService->slug }}" class="form-control form-control-lg" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('app.service') @lang('app.description')<span class="required-span">*</span></label>
                                    <textarea name="description" id="description" cols="30" class="form-control-lg form-control" rows="4">{{ $businessService->description }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('app.price')<span class="required-span">*</span> (@lang('app.commissionIncluded'))</label>
                                    <input onkeypress="return isNumberKey(event)" type="number" step="0.01" min="0" name="price" id="price" class="form-control form-control-lg" value="{{ $businessService->price }}"  />
                                </div>
                            </div>
                            <div class="col-md-4">

                                <div class="form-group">
                                    <label>@lang('modules.services.discount')<span class="required-span">*</span></label>
                                    <div class="input-group">
                                        <input onkeypress="return isNumberKey(event)" type="number" class="form-control form-control-lg" id="discount" name="discount" min="0" value="{{ $businessService->discount }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary dropdown-toggle" id="discount-type-select" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('modules.services.'.$businessService->discount_type)</button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item discount_type" data-type="percent" href="javascript:;">@lang('modules.services.percent')</a>
                                                <a class="dropdown-item discount_type" data-type="fixed" href="javascript:;">@lang('modules.services.fixed')</a>
                                            </div>
                                        </div>
                                        <input type="hidden" id="discount-type" name="discount_type" value="{{ $businessService->discount_type }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 offset-md-1">
                                <div class="form-group">
                                    <label>@lang('modules.services.discountedPrice')</label>
                                    <p class="form-control-static" id="discounted-price">--</p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('app.location')</label>
                                    <div class="input-group">
                                        <select name="location_id" id="location_id" class="form-control form-control-lg">
                                            @foreach($locations as $location)
                                                <option
                                                        @if($location->id == $businessService->location_id) selected @endif
                                                        value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('app.category')</label>
                                    <div class="input-group">
                                        <select name="category_id" id="category_id" class="form-control form-control-lg">
                                            @foreach($categories as $category)
                                                <option
                                                    @if($category->id == $businessService->category_id) selected @endif
                                                    value="{{ $category->id }}">{{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>@lang('modules.services.time')<span class="required-span">*</span></label>
                                    <div class="input-group">
                                        <input onkeypress="return isNumberKey(event)" type="number" step="0.01" min="0" class="form-control form-control-lg" name="time" value="{{ $businessService->time }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary dropdown-toggle" id="time-type-select" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@lang('app.'.$businessService->time_type)</button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item time_type" data-type="minutes" href="javascript:;">@lang('app.minutes')</a>
                                                <a class="dropdown-item time_type" data-type="hours" href="javascript:;">@lang('app.hours')</a>
                                                <a class="dropdown-item time_type" data-type="days" href="javascript:;">@lang('app.days')</a>
                                            </div>
                                        </div>
                                        <input type="hidden" id="time-type" name="time_type" value="{{ $businessService->time_type }}">
                                    </div>
                                </div>
                            </div>

                            @if (!is_null($taxes) || !is_null($selectedTax))
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>@lang('app.select') @lang('app.tax')</label>
                                        <select name="tax_ids[]" id="tax_ids" class="form-control form-control-lg select2" multiple="multiple">
                                            @foreach($taxes as $tax)
                                                <option @if(in_array($tax->id, $selectedTax)) selected @endif value="{{ $tax->id }}">{{ $tax->name }} {{ $tax->percent }}%</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('app.assign') @lang('app.employee')</label>
                                    <select name="employee_ids[]" id="employee_ids" class="form-control form-control-lg select2" multiple="multiple">
                                        @foreach($employees as $employee)
                                            <option @if(in_array($employee->id, $selectedUsers)) selected @endif value="{{ $employee->id }}">{{ $employee->name }} </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <button type="button" class="btn btn-block btn-outline-info btn-sm col-md-2 select-image-button" id="select-image-button"><i class="fa fa-upload"></i> @lang('app.selectFile')</button>
                                <div id="file-upload-box" >
                                    <div class="row" id="file-dropzone">
                                        <div class="col-md-12">
                                            <div class="dropzone"
                                                    id="file-upload-dropzone">
                                                {{ csrf_field() }}
                                                <div class="fallback">
                                                    <input name="file" type="file" multiple/>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-danger">@lang('modules.theme.recommendedResolutionNote')</h6>

                                <input type="hidden" name="serviceID" id="serviceID">

                                <div class="form-group">
                                    <label for="">@lang('app.status')</label>
                                    <select name="status" id="" class="form-control form-control-lg">
                                        <option
                                                @if($businessService->status == 'active') selected @endif
                                        value="active">@lang('app.active')</option>
                                        <option
                                                @if($businessService->status == 'deactive') selected @endif
                                        value="deactive">@lang('app.inactive')</option>
                                    </select>
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
    $(function () {
        $('#description').summernote({
            dialogsInBody: true,
            height: 300
        })

        $('#discount-type').val('{{ $businessService->discount_type }}');
        $('#time-type').val('{{ $businessService->time_type }}');
    })
    var mockFile = {!! $images !!};
    var defaultImage = '';
    var lastIndex = 0;

    Dropzone.autoDiscover = false;
    //Dropzone class
    myDropzone = new Dropzone("#file-upload-dropzone", {
        url: "{{ route('admin.business-services.updateImages') }}",
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        paramName: "file",
        maxFilesize: 10,
        maxFiles: 10,
        acceptedFiles: "image/*",
        autoProcessQueue: false,
        uploadMultiple: true,
        addRemoveLinks:true,
        parallelUploads:10,
        init: function () {
            myDropzone = this;
        },
        dictDefaultMessage: "@lang('app.dropzone.defaultMessage')",
        dictRemoveFile: "@lang('app.dropzone.removeFile')"
    });

    myDropzone.on('sending', function(file, xhr, formData) {
        var id = $('#serviceID').val();

        formData.append('service_id', id);
        if (mockFile.length > 0) {
            formData.append('uploaded_files', JSON.stringify(mockFile));
        }
        formData.append('default_image', defaultImage);
    });

    myDropzone.on('addedfile', function (file) {
        var index = mockFile.findIndex(x => x.name == file.name);

        if (index === -1) {
            index = lastIndex + 1;
        }
        lastIndex = index;

        var div = document.createElement('div');
        div.className = 'form-check form-check-inline';
        var input = document.createElement('input');
        input.className = 'form-check-input';
        input.type = 'radio';
        input.name = 'default_image';
        input.id = 'default-image-'+index;
        input.value = file.name;
        if ('{{ $businessService->default_image }}' == file.name) {
            input.checked = true;
        }
        div.appendChild(input);
        var label = document.createElement('label');
        label.className = 'form-check-label';
        label.innerHTML = "@lang('app.dropzone.makeDefaultImage')";
        label.htmlFor = 'default-image-'+index;
        div.appendChild(label);
        file.previewTemplate.appendChild(div);
    })

    myDropzone.on('removedfile', function (file) {
        var index = mockFile.findIndex(x => x.name == file.name);
        mockFile.splice(index, 1);

        var token = "{{ csrf_token() }}";
        var serviceId = "{{ $businessService->id }}";

        defaultImage = $('input[name=default_image]:checked').val();

        $.easyAjax({
            url: '{{route('admin.business-services.deleteImage', $businessService->id)}}',
            type: "POST",
            data: {'_token': token, '_method': 'POST', 'serviceId' : serviceId, 'file' : file.name, 'default_image' : defaultImage},
        })
    })

    // Create the mock file:
    mockFile.forEach(file => {
        var path = "{{ asset_url('service/'.$businessService->id.'/:file_name') }}";
        path = path.replace(':file_name', file.name);

        myDropzone.emit('addedfile', file);

        myDropzone.emit('thumbnail', file, path);

        // myDropzone.createThumbnailFromUrl(file, path);

        myDropzone.files.push(file);
        myDropzone.emit("complete", file);
    });

    myDropzone.options.maxFiles = myDropzone.options.maxFiles - mockFile.length;

    myDropzone.on("maxfilesexceeded", function(file) { this.removeFile(file); });

    function createSlug(value) {
        value = value.replace(/[^a-z0-9\s]/gi, '-');
        let slug = value.split(' ').join('-').toLowerCase();
        slug = slug.replace(/[_\s]/g, '-');

        $('#slug').val(slug);
    }

    $('#name').keyup(function(e) {
        createSlug($(this).val());
    });

    $('#slug').keyup(function(e) {
        createSlug($(this).val());
    });

    $('.discount_type').click(function () {
        var type = $(this).data('type');

        $('#discount-type-select').html(type);
        $('#discount-type').val(type);
        calculateDiscountedPrice();
    });

    $('.time_type').click(function () {
        var type = $(this).data('type');

        $('#time-type-select').html(type);
        $('#time-type').val(type);
    });

    $('#save-form').click(function () {

        $.easyAjax({
            url: '{{route('admin.business-services.update', $businessService->id)}}',
            container: '#createForm',
            type: "POST",
            redirect: true,
            file:true,
            data: $('#createForm').serialize(),
            success: function (response) {
                serviceID = response.serviceID;
                $('#serviceID').val(response.serviceID);
                defaultImage = response.defaultImage;

                if (myDropzone.getQueuedFiles().length > 0) {
                    myDropzone.processQueue();
                }
                var msgs = "@lang('messages.updatedSuccessfully')";
                $.showToastr(msgs, 'success');
                window.location.href = '{{ route('admin.business-services.index') }}'
            }
        })
    });

    $('#discount, #price').keyup(function () {
        calculateDiscountedPrice();
    });

    $('#discount, #price').change(function () {
        calculateDiscountedPrice();
    });

    $('#discount, #price').on('wheel', function () {
        calculateDiscountedPrice();
    });

    function calculateDiscountedPrice() {
        var price = $('#price').val();
        var discount = $('#discount').val();
        var discountType = $('#discount-type').val();

        if (discountType == 'percent') {
            if(discount > 100){
                $('#discount').val(100);
                discount = 100;
            }
        }
        else {
            if (parseInt(discount) > parseInt(price)) {
                $('#discount').val(price);
                discount = price;
            }
        }

        var discountedPrice = price;

        if(discount >= 0 && discount >= '' && price != '' && price > 0){
            if (discountType == 'percent') {
                discountedPrice = parseFloat(price) - (parseFloat(price) * (parseFloat(discount) / 100));
            }
            else {
                discountedPrice = parseFloat(price) - parseFloat(discount);
            }
        }

        if(discount != '' && price != '' && price > 0){
            $('#discounted-price').html(discountedPrice.toFixed(2));
        }
        else {
            $('#discounted-price').html('--');
        }
    }

    calculateDiscountedPrice();
</script>

@endpush
