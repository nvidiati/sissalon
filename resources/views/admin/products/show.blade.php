<div class="modal-header">
    <h4 class="modal-title">@lang('app.product') @lang('app.detail')</h4>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>

</div>
<div class="modal-body">
    <div class="portlet-body">
        <div class="row">
            <div class="col-md-6 deal-detail-img-thumb">
                <img src="{{ $product->product_image_url }}" class="img img-responsive img-thumbnail" width="100%">
            </div>

            <div class="col-md-6">
                <div class="row">
                    <div class="col-md-6">
                        <h6>@lang('app.product') @lang('app.name')</h6>
                        <p>{{ ucwords($product->name) }}</p>
                    </div>

                    <div class="col-md-6">
                        <h6>@lang('app.discount') @lang('app.type')</h6>
                        <p> {{ ucwords($product->discount_type) }} </p>
                    </div>
                    @if ($product->discount_type == 'percent')
                        <div class="col-md-6">
                            <h6>@lang('app.percentage')</h6>
                            <p> {{ ucwords($product->discount) }}% </p>
                        </div>
                    @else
                        <div class="col-md-6">
                            <h6>@lang('app.amount')</h6>
                            <p> {{ $product->price - $product->discount }} </p>
                        </div>
                    @endif

                    <div class="col-md-6">
                        <h6>@lang('app.location')</h6>
                        <p> {{ ucwords($product->location->name) }} </p>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>@lang('app.tax')</h6>
                        @foreach ($taxes as $item)
                            <span>{{ $item->name }}-{{ $item->percent }}% @if (!$loop->last),@endif </span>
                        @endforeach
                    </div>

                    @if (!is_null($product->description))
                        <div class="col-md-12 mt-4">
                            <h6>@lang('app.description')</h6>
                            <p>{!! $product->description !!} </p>
                        </div>
                    @endif


                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer mt-3">
        <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times"></i>
            @lang('app.cancel')</button>
    </div>
