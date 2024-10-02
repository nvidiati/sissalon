<div class="mt-4 d-flex flex-wrap">
    @foreach ($deals as $deal)
        @if ($deal->utc_open_time->setTimezone($deal->company->timezone)->toTimeString() <= \Carbon\Carbon::now('UTC')->setTimezone($deal->company->timezone)->toTimeString() && $deal->utc_close_time->setTimezone($deal->company->timezone)->toTimeString() >= \Carbon\Carbon::now('UTC')->setTimezone($deal->company->timezone)->toTimeString())
            <div class="col-md-6 mobile-no-padding">
                <div class="card single_deal_box border-0">
                    <div class="card-image position-relative">
                        <a class="m-auto" href="{{$deal->deal_detail_url}}">
                        <img class="card-img-top" src="{{asset('front/images/pixel.gif')}}" data-src="{{ $deal->deal_image_url }}" alt="Card image"></a>
                        @if($deal->percentage > 0)
                        <span>
                            @if($deal->discount_type == 'percentage')
                                {{$deal->percentage}}%
                            @else
                            {{currencyFormatter($deal->converted_original_amount - $deal->converted_deal_amount)}}
                            @endif
                            @lang('app.off')
                        </span>
                        @endif
                    </div>
                    <div class="card-body all_deals_services">
                        <h4 class="card-title">{{ $deal->title }}</h4>
                        <p class="card-text">
                            <span class="deal_card_current_price">{{ $deal->formated_deal_amount }}</span>
                            @if($deal->percentage > 0)
                            <span class="deal_card_old_price">{{ $deal->formated_original_amount }}</span>|
                            @else &nbsp;&nbsp;|
                            @endif
                            <span class="deal_card_name"><a href="{{route('front.vendorPage',['slug' => $deal->company->slug, 'location_id' => $deal->location_id])}}" class="companyDetailsAnchor" style="background: transparent; box-shadow: none; color: #2d2d2d">{{ $deal->company->company_name }}</a></span>|
                            <span class="deal_card_location">{{ $deal->location->name }}</span>
                        </p>

                        <a href="javascript:;"
                            id="deal{{ $deal->id }}"
                            class="btn w-100 add-to-cart"
                            data-type="deal"
                            data-unique-id="deal{{ $deal->id }}"
                            data-id="{{ $deal->id }}"
                            data-deal-service-type="{{$deal->deal_service_type}}"
                            data-price="{{$deal->converted_deal_amount}}"
                            data-name="{{ ucwords($deal->title) }}"
                            data-company-id="{{ $deal->company->id }}"
                            data-max-order="{{ $deal->max_order_per_customer }}"
                            aria-expanded="false">
                            @lang('front.addToCart')
                        </a>
                    </div>
                </div>
            </div>
        @elseif ($deal->company->display_deal == 'active')
            <div class="col-md-6 mobile-no-padding">
                <div class="card single_deal_box border-0">
                    <div class="card-image position-relative">
                        <a class="m-auto" href="{{$deal->deal_detail_url}}">
                        <img class="card-img-top" src="{{asset('front/images/pixel.gif')}}" data-src="{{ $deal->deal_image_url }}" alt="Card image"></a>
                        @if($deal->percentage > 0)
                        <span>
                            @if($deal->discount_type == 'percentage')
                                {{$deal->percentage}}%
                            @else
                            {{currencyFormatter($deal->converted_original_amount - $deal->converted_deal_amount)}}
                            @endif
                            @lang('app.off')
                        </span>
                        @endif
                    </div>
                    <div class="card-body all_deals_services">
                        <h4 class="card-title">{{ $deal->title }}</h4>
                        <p class="card-text">
                            <span class="deal_card_current_price">{{ $deal->formated_deal_amount }}</span>
                            @if($deal->percentage > 0)
                            <span class="deal_card_old_price">{{ $deal->formated_original_amount }}</span>|
                            @else &nbsp;&nbsp;|
                            @endif
                            <span class="deal_card_name"><a href="{{route('front.vendorPage',['slug' => $deal->company->slug, 'location_id' => $deal->location_id])}}" class="companyDetailsAnchor" style="background: transparent; box-shadow: none; color: #2d2d2d">{{ $deal->company->company_name }}</a></span>|
                            <span class="deal_card_location">{{ $deal->location->name }}</span>
                        </p>
                        @php
                            $current = \Carbon\Carbon::now()->setTimezone($deal->company->timezone);
                            $totalDuration = $current->diffInSeconds($deal->open_time);
                            $timeLeft = gmdate('H:i:s', $totalDuration);

                        @endphp
                        <p class='timer mt-4'>@lang('front.dealStartsIn') <span class='time-left'>{{ $timeLeft }}</span></p>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

<div class="deals_pagination mt-4 d-flex justify-content-center" id="pagination">
    {{ $deals->links() }}
</div>
