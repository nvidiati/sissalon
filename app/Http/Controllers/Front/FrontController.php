<?php

namespace App\Http\Controllers\Front;

use App\Tax;
use App\Deal;
use App\Page;
use App\Role;
use App\User;
use App\Leave;
use App\Media;
use App\Coupon;
use App\Booking;
use App\Company;
use App\ItemTax;
use App\Package;
use App\Category;
use App\Currency;
use App\FrontFaq;
use App\Language;
use App\Location;
use App\Spotlight;
use Carbon\Carbon;
use App\VendorPage;
use App\Commission;
use App\BookingItem;
use App\BookingTime;
use App\OfficeLeave;
use App\Helper\Reply;
use App\GlobalSetting;
use App\BusinessService;
use App\Country;
use App\UniversalSearch;
use App\EmployeeSchedule;
use App\Facades\Razorpay;
use App\GatewayAccountDetail;
use Illuminate\Support\Arr;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use App\Notifications\NewUser;
use App\Notifications\ContactUs;
use App\Notifications\NewBooking;
use App\PaymentGatewayCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Notifications\CompanyWelcome;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\StoreFrontBooking;
use App\Notifications\BookingConfirmation;
use App\Http\Requests\Front\ContactRequest;
use App\Http\Requests\Front\CartPageRequest;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\FrontBaseController;
use App\Http\Requests\Company\RegisterCompany;
use App\Http\Requests\ApplyCoupon\ApplyRequest;
use App\Notifications\NewCompany;
use App\Notifications\SuperadminNotificationAboutNewAddedCompany;
use App\Payment;
use Google\Service\MyBusinessLodging\Business;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;

class FrontController extends FrontBaseController
{

    public function index()
    {
        $couponData = json_decode(request()->cookie('couponData'), true);

        if ($couponData) {
            setcookie('couponData', '', time() - 3600);
        }

        if (request()->ajax())
        {
            /* LOCATION */
            $location_id = request()->location_id;
            /* NEARBY LOCATION */

            if(request()->lat != null && request()->long != null)
            {
                $services = BusinessService::withoutGlobalScope(CompanyScope::class)
                    ->activeCompany()
                    ->with([
                        'category' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        }
                    ])->active();

                $record = $this->filterLocations(request()->lat, request()->long);

                $nearByLocations = $services->whereIn('location_id', $record)->orderByRaw('FIELD(location_id , ' .implode(',', $record->toArray()) .' ) ASC');

                $total_nearByLocations_count = BusinessService::count();

                $nearByLocations = $nearByLocations->take(10)->get();

            }
            else
            {
                $location_id = request()->location_id;

                $services = BusinessService::withoutGlobalScope(CompanyScope::class)
                    ->activeCompany()
                    ->with([
                        'category' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        }
                    ])->active();

                $loc = Location::where('id', $location_id)->first();

                $record = $this->filterLocations($loc->lat, $loc->lng);

                $nearByLocations = $services->where('location_id', $location_id)->orderByRaw('FIELD(location_id , ' .implode(',', $record->toArray()) .' ) ASC');

                $total_nearByLocations_count = BusinessService::count();

                $nearByLocations = $nearByLocations->take(10)->get();
            }


            /* CATRGORIES */
            $categories = Category::active()->withoutGlobalScope(CompanyScope::class)
                ->activeCompanyService()
                ->with(['services' => function ($query)  use($location_id) {
                    $query->active()->withoutGlobalScope(CompanyScope::class)->where('location_id', $location_id);
                }])
            ->withCount(['services' => function ($query) use($location_id) {
                $query->withoutGlobalScope(CompanyScope::class)->where('location_id', $location_id);
            }]);

            $total_categories_count = $categories->count();
            $categories = $categories->take(8)->get();


            /* DEALS */
            $all_deals = Deal::withoutGlobalScope(CompanyScope::class)
                ->active()
                ->activeCompany()
                ->with(['location', 'services', 'company' => function($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                } ])
            ->where('start_date_time', '<=', Carbon::now('UTC')->setTimezone($this->settings->timezone))
            ->where('end_date_time', '>=', Carbon::now('UTC')->setTimezone($this->settings->timezone))
            ->whereRaw('json_contains(days, \'["' . Carbon::now('UTC')->setTimezone($this->settings->timezone)->isoFormat('dddd') . '"]\')')
            ->where('location_id', $location_id);

            $all_deals = $all_deals->get();

            $selected_deals = [];

            foreach ($all_deals as $deal)
            {
                if(isset($deal->company->display_deal) && $deal->company->display_deal == 'active')
                {
                    $selected_deals[] = $deal->id;
                }
                else
                {
                    if ($deal->utc_open_time->setTimezone($this->settings->timezone)->toTimeString() <= Carbon::now('UTC')->setTimezone($this->settings->timezone)->toTimeString() && $deal->utc_close_time->setTimezone($this->settings->timezone)->toTimeString() >= Carbon::now('UTC')->setTimezone($this->settings->timezone)->toTimeString())
                    {
                        $selected_deals[] = $deal->id;
                    }
                }
            }

            $deals = Deal::withoutGlobalScope(CompanyScope::class)
                ->active()
                ->activeCompany()
                ->with(['location', 'location.timezone', 'services', 'company' => function($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                } ])
                ->whereIn('id', $selected_deals)->get();
                $total_deals_count = $deals->count();

            foreach($deals as $deal)
            {
                $format = $deal->company->time_format;
                $companyTz = $deal->company->timezone;
                $locationTz = $this->settings->timezone;

                $location_open_time = Carbon::createFromFormat($format, $deal->open_time, $companyTz)->setTimezone($locationTz)->format($format);

                $location_close_time = Carbon::createFromFormat($format, $deal->close_time, $companyTz)->setTimezone($locationTz)->format($format);

                /* @phpstan-ignore-next-line */
                $deal->location_open_time = $location_open_time;
                /* @phpstan-ignore-next-line */
                $deal->location_close_time = $location_close_time;

            }

            /* SPOTLIGHT */
            $all_spotlight = Spotlight::with(['deal', 'company' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
            } ])
            ->activeCompany()
            ->whereHas('deal', function($q) use($location_id){
                $q->whereHas('location', function ($q) use($location_id) {
                    $q->where('location_id', $location_id);
                });
            })
            ->where('from_date', '<=', Carbon::now()->setTimezone($this->settings->timezone)->toDateString())
            ->where('to_date', '>=', Carbon::now()->setTimezone($this->settings->timezone)->toDateString())
            ->get();

            $selected_spotlight = [];

            foreach($all_spotlight as $spotlight)
            {
                if(isset($spotlight->company->display_deal) && $spotlight->company->display_deal == 'active')
                {
                    $selected_spotlight[] = $spotlight->id;
                }
                else
                {
                    if ($spotlight->deal->utc_open_time->setTimezone($this->settings->timezone)->toTimeString() <= Carbon::now('UTC')->setTimezone($this->settings->timezone)->toTimeString() && $spotlight->deal->utc_close_time->setTimezone($this->settings->timezone)->toTimeString() >= Carbon::now('UTC')->setTimezone($this->settings->timezone)->toTimeString())
                    {
                        $selected_spotlight[] = $spotlight->id;
                    }
                }
            }


            $spotlight = Spotlight::with(['deal', 'deal.location', 'deal.location.timezone', 'company' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
            } ])
            ->activeCompany()
            ->whereHas('deal', function($q) use($location_id){
                $q->whereHas('location', function ($q) use($location_id) {
                    $q->where('location_id', $location_id);
                });
            })
            ->whereIn('id', $selected_spotlight)
            ->orderBy('sequence', 'asc')
            ->get();


            return Reply::dataOnly(['categories' => $categories, 'total_categories_count' => $total_categories_count, 'deals' => $deals, 'total_deals_count' => $total_deals_count, 'spotlight' => $spotlight, 'nearByLocations' => $nearByLocations, 'total_nearByLocations_count' => $total_nearByLocations_count]);

        }

        $this->sliderContents = Media::all();

        /* COUPON */
        $coupons = Coupon::active()->where('status', '!=', 'expire')->take('12')->get();
        $day = Carbon::parse(Carbon::now())->format('l');

        $data = [];

        foreach ($coupons as $coupon)
        {
            if ($coupon->days) {
                $days = json_decode($coupon->days);

                if(in_array($day, $days))
                {
                    $data[] = $coupon;
                }
            }
        }

        $this->coupons = $data;
        $this->googleMapAPIKey = $this->settings;

        return view('front.index', $this->data);
    }

    public function matchLocations(Request $request)
    {
        $record = Location::select('id', 'name', DB::raw('6371 * acos(cos(radians(' . $request->latitude . ')) * cos(radians(lat)) * cos(radians(lng) - radians(' . $request->longitude . ')) + sin(radians(' .$request->latitude. ')) * sin(radians(lat))) AS distance'))
            ->where('name', 'LIKE', '%' . $request->city . '%')
            ->orderBy('distance')
            ->get();
        return Reply::dataOnly(['locations' => $record]);
    }

    public function filterLocations($requestLat, $requestLong)
    {

        $compareRecords = Location::select('id', 'name', DB::raw('6371 * acos(cos(radians(' . $requestLat . ')) * cos(radians(lat)) * cos(radians(lng) - radians(' . $requestLong . ')) + sin(radians(' .$requestLat. ')) * sin(radians(lat))) AS distance'))->orderBy('distance')->pluck('id');

        return $compareRecords;

    }

    public function addOrUpdateProduct(Request $request)
    {
        $newProduct = [
            'type' => $request->type,
            'unique_id' => $request->unique_id,
            'companyId' => $request->companyId,
            'price' => $request->price,
            'name' => $request->name,
            'id' => $request->id,
            'service_type' => $request->serviceType,
            'deal_service_type' => $request->deal_service_type,
        ];

        $tax = [];
        $products = [];
        $quantity = $request->quantity ?? 1;

        if($request->type == 'deal')
        {
            $deals = Deal::withoutGlobalScope(CompanyScope::class)->where('id', $request->id)
                ->with(['dealTaxes' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                }
            ])->first();

            if (($deals->uses_limit != null) && ($deals->uses_limit <= $deals->used_time)) {
                return Reply::error(__('app.maxDealUses'));
            }

            $tax = [];

            if ($deals->dealTaxes) {
                foreach ($deals->dealTaxes as $key => $deal) {
                    $taxDetail = Tax::select('id', 'name', 'percent')->active()->where('id', $deal->tax_id)->first();
                    $tax[] = $taxDetail;
                }
            }

            $newProduct = Arr::add($newProduct, 'tax', json_encode($tax));
            $newProduct = Arr::add($newProduct, 'max_order', $request->max_order);

            $dealId = $request->id;

            if (Auth::check() == 'true') {

                $dealCount = DB::table('bookings')
                    ->join('booking_items', function ($join) use ($dealId) {
                        $join->on('bookings.id', '=', 'booking_items.booking_id')
                            ->where('booking_items.deal_id', '=', $dealId);
                    })->where('bookings.user_id', '=', Auth::user()->id)->count();

                /* if type is deal and max_order_per_customer is exceeded then block increasing quantity */
                if($request->max_order <= $dealCount) {
                    return Reply::error(__('app.maxDealMessage', ['quantity' => $this->checkDealQuantity($request->id)]));
                }
            }
        }

        if ($request->type == 'service')
        {
            $services = BusinessService::withoutGlobalScope(CompanyScope::class)->where('id', $request->id)
                ->with([
                'taxServices' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                }
            ])->first();

            $tax = [];

            if ($services->taxServices) {

                foreach ($services->taxServices as $key => $service) {
                    $taxDetail = Tax::select('id', 'name', 'percent')->active()->where('id', $service->tax_id)->first();
                    $tax[] = $taxDetail;
                }

            }

            $newProduct = Arr::add($newProduct, 'tax', json_encode($tax));
            $serviceId = $request->id;

            if (Auth::check() == 'true' && !is_null($this->user->company)) {

                $service = BusinessService::where('id', $request->id)->first();

                $serviceCount = DB::table('bookings')
                    ->join('booking_items', function ($join) use ($serviceId) {
                        $join->on('bookings.id', '=', 'booking_items.booking_id')
                            ->where('booking_items.business_service_id', '=', $serviceId);
                    })->where('bookings.user_id', '=', Auth::user()->id)->count();

                /* if type is deal and max_order_per_customer is exceeded then block increasing quantity */
                $company = Company::where('id', request()->companyId)->first();

                if($company->booking_per_day <= $serviceCount) {
                    return Reply::error(__('app.maxServiceMessage', ['quantity' => $company->booking_per_day]));
                }
            }
        }

        if($request->serviceType == 'online')
        {
            if ($request->hasCookie('products'))
            {
                return Reply::error(__('app.oneOnlineService'));
            }
        }
        else
        {
            if($request->hasCookie('products'))
            {
                $products = json_decode($request->cookie('products'), true);

                if(count($products) == 1)
                {
                    $unique_key = array_keys($products);
                    $unique_key = $unique_key[0];
                    $service_type = isset($products[$unique_key]['service_type']) ? $products[$unique_key]['service_type'] : '';

                    if($service_type != '' && $service_type == 'online')
                    {
                        return Reply::error(__('app.oneOnlineService'));
                    }

                }

            }
        }

        if (!$request->hasCookie('products'))
        {
            $newProduct = Arr::add($newProduct, 'quantity', $quantity);
            $newProduct = Arr::add($newProduct, 'quantity', $quantity);
            $products = Arr::add($products, $request->unique_id, $newProduct);

            return response([
                'status' => 'success',
                'message' => __('messages.front.success.productAddedToCart'),
                'productsCount' => count($products)
            ])->cookie('products', json_encode($products));
        }

        $products = json_decode($request->cookie('products'), true);

        /* if type is deal and max_order_per_customer is exceeded then block increasing quantity */
        if($request->type == 'deal' && array_key_exists($request->unique_id, $products) && $this->checkDealQuantity($request->id) !== 0 && $this->checkDealQuantity($request->id) <= $products[$request->unique_id]['quantity']) {
            return Reply::error(__('app.maxDealMessage', ['quantity' => $this->checkDealQuantity($request->id)]));
        }

        /* Checking if item belongs to some other company */
        $companyIds = [];
        $types = [];

        foreach ($products as $key => $product)
        {
            $companyIds[] = $product['companyId'];
            $types[] = $product['type'];
        }

        /* check if incoming service belong to same company as cart has */
        if (!in_array($request->companyId, $companyIds))
        {
            return response(['result' => 'fail', 'message' => __('messages.front.errors.differentItemFound')])->cookie('products', json_encode($products));
        }

        /* Checking if item has different type then cart item */
        if (!in_array($request->type, $types))
        {
            return response(['result' => 'fail', 'message' => __('messages.front.errors.addOneItemAtATime')])->cookie('products', json_encode($products));
        }

        if (!array_key_exists($request->unique_id, $products))
        {
            $newProduct = Arr::add($newProduct, 'quantity', $quantity);
            $newProduct = Arr::add($newProduct, 'tax', json_encode($tax));
            $products = Arr::add($products, $request->unique_id, $newProduct);

            return response([
                'status' => 'success',
                'message' => __('messages.front.success.productAddedToCart'),
                'productsCount' => count($products)
            ])->cookie('products', json_encode($products));
        }
        else
        {
            if ($request->quantity) {
                $products[$request->unique_id]['quantity'] = $request->quantity;
            }
            else {
                $products[$request->unique_id]['quantity'] += 1;
            }
        }

        return response([
            'status' => 'success',
            'message' => __('messages.front.success.cartUpdated'),
            'productsCount' => count($products)
        ])->cookie('products', json_encode($products));
    }

    public function bookingPage(Request $request)
    {
        $bookingDetails = [];

        if ($request->hasCookie('bookingDetails')) {
            $bookingDetails = json_decode($request->cookie('bookingDetails'), true);
        }

        if ($request->ajax()) {
            return Reply::dataOnly(['status' => 'success', 'productsCount' => $this->productsCount]);
        }

        $locale = App::getLocale();

        return view('front.booking_page', compact('bookingDetails', 'locale'));
    }

    public function addBookingDetails(CartPageRequest $request)
    {
        $expireTime = Carbon::parse($request->bookingDate . ' ' . $request->bookingTime, $this->settings->timezone);
        $cookieTime = Carbon::now()->setTimezone($this->settings->timezone)->diffInMinutes($expireTime);

        $emp_name = '';

        if (!empty($request->selected_user)) {
            $emp_name = User::find($request->selected_user)->name;
        }

        return response(Reply::dataOnly(['status' => 'success']))->cookie('bookingDetails', json_encode(['bookingDate' => $request->bookingDate, 'bookingTime' => $request->bookingTime, 'selected_user' => $request->selected_user, 'emp_name' => $emp_name]), $cookieTime);
    }

    public function cartPage(Request $request)
    {
        $products       = json_decode($request->cookie('products'), true);
        $bookingDetails = json_decode($request->cookie('bookingDetails'), true);
        $couponData     = json_decode($request->cookie('couponData'), true);
        $taxes          = Tax::active()->get();
        $commission     = PaymentGatewayCredentials::first();
        $type = '';

        if(!is_null(json_decode($request->cookie('products'), true)))
        {
            $product = (array)json_decode(request()->cookie('products', true));
            $keys = array_keys($product);
            $type = $product[$keys[0]]->type == 'deal' ? 'deal' : 'booking';
        }

        return view('front.cart', compact('commission', 'products', 'taxes', 'bookingDetails', 'couponData', 'type'));
    }

    public function deleteProduct(Request $request, $id)
    {
        $products = json_decode($request->cookie('products'), true);

        if ($id != 'all') {
            Arr::forget($products, $id);
        }
        else {

            $productsCount = is_null($products) ? 0 : count($products);

            return response(Reply::successWithData(__('messages.front.success.cartCleared'), ['action' => 'redirect', 'url' => route('front.cartPage'), 'productsCount' => $productsCount]))
                ->withCookie(Cookie::forget('bookingDetails'))
                ->withCookie(Cookie::forget('products'))
                ->withCookie(Cookie::forget('couponData'));
        }

        if (count($products) > 0) {
            setcookie('products', '', time() - 3600);
            return response(Reply::successWithData(__('messages.front.success.productDeleted'), ['productsCount' => count($products), 'products' => $products]))->cookie('products', json_encode($products));
        }
        
        return response(Reply::successWithData(__('messages.front.success.cartCleared'), ['action' => 'redirect', 'url' => route('front.cartPage'), 'productsCount' => count($products)]))->withCookie(Cookie::forget('bookingDetails'))->withCookie(Cookie::forget('products'))->withCookie(Cookie::forget('couponData'));
    }

    public function clearCartProduct(Request $request, $id = 'all')
    {
        $products = json_decode($request->cookie('products'), true);

        if ($id != 'all') {
            Arr::forget($products, $id);
        }
        else {
            $productsCount = is_null($products) ? 0 : count($products);
            return response(Reply::successWithData(__('messages.front.success.cartCleared'), ['action' => 'redirect', 'url' => route('front.index'), 'productsCount' => $productsCount]))
                ->withCookie(Cookie::forget('bookingDetails'))
                ->withCookie(Cookie::forget('products'))
                ->withCookie(Cookie::forget('couponData'));

        }

        if (count($products) > 0) {
            setcookie('products', '', time() - 3600);
            return response(Reply::successWithData(__('messages.front.success.productDeleted'), ['productsCount' => count($products), 'products' => $products]))->cookie('products', json_encode($products));
        }

        return response(Reply::successWithData(__('messages.front.success.cartCleared'), ['action' => 'redirect', 'url' => route('front.cartPage'), 'index' => count($products)]))->withCookie(Cookie::forget('bookingDetails'))->withCookie(Cookie::forget('products'))->withCookie(Cookie::forget('couponData'));
    }

    public function updateCart(Request $request)
    {
        $product = $request->products;

        if($request->type == 'deal' && $request->currentValue > $request->max_order)
        {
            $product[$request->unique_id]['quantity'] = $request->max_order;

            return response(Reply::error(__('app.maxDealMessage', ['quantity' => $request->max_order])));
        }

        return response(Reply::success(__('messages.front.success.cartUpdated')))->cookie('products', json_encode($product));
    }

    public function checkoutPage()
    {
        $products = (array)json_decode(request()->cookie('products', true));
        $keys = array_keys($products);

        $request_type = $products[$keys[0]]->type == 'deal' ? 'deal' : 'booking';

        $emp_name = '';

        if (!empty(json_decode(request()->cookie('bookingDetails'))->selected_user)) {
            $emp_name = User::find(json_decode(request()->cookie('bookingDetails'))->selected_user)->name;
        }

        $bookingDetails = request()->hasCookie('bookingDetails') ? json_decode(request()->cookie('bookingDetails'), true) : [];
        $couponData     = request()->hasCookie('couponData') ? json_decode(request()->cookie('couponData'), true) : [];

        $Amt = 0;
        $tax = 0;
        $totalAmount = 0;
        $taxAmount = 0;

        if ($request_type !== 'deal') {

            foreach ($products as $key => $service) {
                $taxes = ItemTax::with('tax')->where('service_id', $service->id)->get();
                $tax = 0;

                foreach ($taxes as $key => $value) {
                    $tax += $value->tax->percent;
                }

                $Amt = $service->price * $service->quantity;
                $taxAmount += ($Amt * $tax) / 100;
                $totalAmount += $service->price * $service->quantity;
            }

        }
        else {

            foreach ($products as $key => $deal) {
                $taxes = ItemTax::with('tax')->where('deal_id', $deal->id)->get();
                $tax = 0;


                foreach ($taxes as $key => $value) {
                    $tax += $value->tax->percent;
                }

                $Amt = $deal->price * $deal->quantity;
                $taxAmount += ($Amt * $tax) / 100;
                $totalAmount += $deal->price * $deal->quantity;
            }
        }

        if ($taxAmount > 0) {
            $totalAmount = $taxAmount + $totalAmount;
        }

        if ($couponData) {
            if ($totalAmount <= $couponData['applyAmount']) {
                $totalAmount = 0;
            }
            else {
                $totalAmount -= $couponData['applyAmount'];
            }
        }

        $totalAmount = round($totalAmount, 2);

        $countries = Country::all();
        return view('front.checkout_page', compact('totalAmount', 'bookingDetails', 'request_type', 'emp_name', 'countries'));
    }

    public function paymentFail(Request $request, $bookingId = null)
    {
        $credentials = PaymentGatewayCredentials::withoutGlobalScopes()->first();

        if ($bookingId == null) {
            $booking = Booking::with('company.gatewayAccountDetails')->where([
                'user_id' => $this->user->id
            ])
                ->latest()
                ->first();
        }
        else {
            $booking = Booking::with('company.gatewayAccountDetails')->where(['id' => $bookingId, 'user_id' => $this->user->id])->first();
        }

        $setting = Company::with('currency')->first();
        $user = $this->user;

        $activeGatewayAccountDetail = $booking->company->activeGatewayAccountDetail ? $booking->company->activeGatewayAccountDetail->account_id : null;
        $activePaypalAccountDetail = $booking->company->activePaypalAccountDetail ? $booking->company->activePaypalAccountDetail->account_id : null;

        return view('front.payment', compact('credentials', 'booking', 'user', 'setting', 'activeGatewayAccountDetail', 'activePaypalAccountDetail'));
    }

    public function paymentSuccess(Request $request, $bookingId = null)
    {
        $credentials = PaymentGatewayCredentials::withoutGlobalScopes()->first();

        if ($bookingId == null) {
            $booking = Booking::with('company.gatewayAccountDetails')->where([
                'user_id' => $this->user->id
            ])->latest()->first();
        }
        else {
            $booking = Booking::with('company.gatewayAccountDetails')->where(['id' => $bookingId, 'user_id' => $this->user->id])->first();
        }

        $setting = Company::with('currency')->first();
        $user = $this->user;

        if ($booking->payment_status !== 'completed') {
            $booking->payment_status = 'completed';
            $booking->save();
        }

        $activeGatewayAccountDetail = $booking->company->activeGatewayAccountDetail ? $booking->company->activeGatewayAccountDetail->account_id : null;
        $activePaypalAccountDetail = $booking->company->activePaypalAccountDetail ? $booking->company->activePaypalAccountDetail->account_id : null;

        return view('front.payment', compact('credentials', 'booking', 'user', 'setting', 'activeGatewayAccountDetail', 'activePaypalAccountDetail'));
    }

    public function paymentGateway(Request $request)
    {
        if(!Auth::user()){
            return $this->logout();
        }

        $credentials = PaymentGatewayCredentials::withoutGlobalScopes()->first();

        $booking = Booking::with('deal', 'users', 'company.activeGatewayAccountDetail')->where([
            'user_id' => $this->user->id
        ])->latest()->first();

        $emp_name = '';

        if (array_key_exists(0, $booking->users->toArray())) {
            $emp_name = $booking->users->toArray()[0]['name'];
        }

        $setting = Company::with('currency')->first();
        $globalSetting = GlobalSetting::with('currency')->first();
        $frontThemeSetting = $this->frontThemeSettings;
        $user = $this->user;

        if ($booking->payment_status == 'completed') {
            return redirect(route('front.index'));
        }

        $activeGatewayAccountDetail = $booking->company->activeGatewayAccountDetail ? $booking->company->activeGatewayAccountDetail->account_id : null;
        $activePaypalAccountDetail = $booking->company->activePaypalAccountDetail ? $booking->company->activePaypalAccountDetail->account_id : null;

        return view('front.payment-gateway', compact('credentials', 'booking', 'user', 'setting', 'globalSetting', 'frontThemeSetting', 'emp_name', 'activeGatewayAccountDetail', 'activePaypalAccountDetail'));
    }

    public function offlinePayment($bookingId = null, $return_url = null)
    {
        if ($bookingId == null) {
            $booking = Booking::where([ 'user_id' => $this->user->id ])->latest()->first();
        }
        else {
            $booking = Booking::where(['id' => $bookingId, 'user_id' => $this->user->id])->first();
        }

        if (!$booking || $booking->payment_status == 'completed') {

            return redirect()->route('front.index');
        }

        $booking->payment_status = 'pending';
        $booking->save();
        $paymentCredentials = PaymentGatewayCredentials::withoutGlobalScope(CompanyScope::class)->first();
        $companyCommission = Commission::where('company_id', $this->settings->id)->where('gateway', 'cash')->orWhere('gateway', 'card')->first();
        $commissionAmt = $paymentCredentials->offline_commission != null ? round(($booking->amount_to_pay / 100) * $paymentCredentials->offline_commission, 2) : 0;

        $totalAmount = $companyCommission ? $booking->amount_to_pay + $companyCommission->total_amount : $booking->amount_to_pay;
        $commissionAmount = $companyCommission ? $commissionAmt + $companyCommission->commission_amount : $commissionAmt;
        $pendingAmount = $companyCommission ? $commissionAmt + $companyCommission->pending_amount : $commissionAmt;

        if ($commissionAmt != null && $commissionAmt !== 0 && $commissionAmt != '') {

            if (!is_null($companyCommission)) {
                $commission = Commission::findOrFail($companyCommission->id);
            }
            else{
                $commission = new Commission();
            }

            $commission->company_id      = $this->settings->id;
            $commission->currency_id     = $this->settings->currency_id;
            $commission->total_amount      = $totalAmount;
            $commission->commission_amount = $commissionAmount;
            $commission->pending_amount    = $pendingAmount;
            $commission->gateway           = $booking->payment_gateway;
            $commission->status            = 'pending';
            $commission->save();
        }

        $admins = User::allAdministrators()->where('company_id', $booking->company_id)->first();
        Notification::send($admins, new NewBooking($booking));
        $booking->user->notify(new BookingConfirmation($booking));

        if ($return_url != null && $return_url = 'calendarPage') {

            Session::put('success', __('messages.updatedSuccessfully'));
            return redirect()->route('admin.bookings.index');
        }

        return view('front.booking_success');
    }

    public function bookingSlots(Request $request)
    {
        $company = $this->getCartCompanyDetail();

        if (!is_null($this->user) && $company->booking_per_day != (0 || '') && $company->booking_per_day <= $this->user->userBookingCount(Carbon::createFromFormat('Y-m-d', $request->bookingDate)))
        {
            $msg = __('messages.reachMaxBooking') . Carbon::createFromFormat('Y-m-d', $request->bookingDate)->format('Y-m-d');
            return Reply::dataOnly(['status' => 'fail', 'msg' => $msg]);
        }

        $bookingDate = Carbon::createFromFormat('Y-m-d', $request->bookingDate);
        $day = $bookingDate->format('l');
        $bookingTime = BookingTime::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->where('location_id', $request->location_id)->where('day', strtolower($day))->first();

        // Check if multiple booking allowed
        $bookings = Booking::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)->select('id', 'date_time')->where(DB::raw('DATE(date_time)'), $bookingDate->format('Y-m-d'));
        $officeLeaves = OfficeLeave::where('start_date', '<=', $bookingDate )
            ->where('end_date', '>=', $bookingDate)
            ->get();

        if($officeLeaves->count() > 0){
            $msg = __('messages.ShopClosed');
            return Reply::dataOnly(['status' => 'shopclosed', 'msg' => $msg]);
        }

        if ($bookingTime->per_day_max_booking != (0 || '') && $bookingTime->per_day_max_booking <= $bookings->count())
        {
            $msg = __('messages.reachMaxBookingPerDay') . Carbon::createFromFormat('Y-m-d', $request->bookingDate)->format('Y-m-d');
            return Reply::dataOnly(['status' => 'fail', 'msg' => $msg]);
        }

        if ($bookingTime->multiple_booking == 'no') {
            $bookings = $bookings->get();
        }
        else {
            $bookings = $bookings->whereRaw('DAYOFWEEK(date_time) = ' . ($bookingDate->dayOfWeek + 1))->get();
        }

        $variables = compact('bookingTime', 'bookings');

        if ($bookingTime->status == 'enabled') {

            if ($company->time_format == 'H:i') {
                $bookingStartTime = carbon::createFromFormat('Y-m-d H:i:s', $bookingTime->utc_start_time)->format('H:i:s');
                $time = Carbon::parse($request->bookingDate. ' ' .$bookingStartTime)->format('Y-m-d H:i');
                $startTime = Carbon::createFromFormat('Y-m-d ' .$company->time_format, $time);
            }
            else {
                $startTime = Carbon::createFromFormat('Y-m-d ' .$company->time_format, $request->bookingDate. ' ' .$bookingTime->utc_start_time->format($company->time_format));
            }

            if ($bookingDate->day === Carbon::today()->day) {

                while ($startTime->lessThan(Carbon::now())) {
                    $startTime = $startTime->addMinutes($bookingTime->slot_duration);
                }
            }

            if ($company->time_format == 'H:i') {
                $bookingEndTime = carbon::createFromFormat('Y-m-d H:i:s', $bookingTime->utc_end_time)->format('H:i:s');
                $times = Carbon::parse($request->bookingDate. ' ' .$bookingEndTime)->format('Y-m-d H:i');
                $endTime = Carbon::createFromFormat('Y-m-d ' .$company->time_format, $times);
            }
            else {
                $endTime = Carbon::createFromFormat('Y-m-d ' .$company->time_format, $request->bookingDate. ' ' .$bookingTime->utc_end_time->format($company->time_format));
            }


            $location = Location::where('id', $request->location_id)->first();
            $startTime->setTimezone($location->timezone ? $location->timezone->zone_name : '');
            $endTime->setTimezone($location->timezone ? $location->timezone->zone_name : '');
            $timings = [];

            if($startTime->toDateString() != $endTime->toDateString())
            {
                $timings = [
                    [
                        'start_time' => $startTime,
                        'end_time' => Carbon::parse($startTime->toDateString().' 23:59:59', $location->timezone ? $location->timezone->zone_name : ''),
                    ],
                    [
                        'start_time' => Carbon::parse($endTime->toDateString().' 00:00:00', $location->timezone ? $location->timezone->zone_name : ''),
                        'end_time' => $endTime,
                    ]
                ];
            }
            else
            {
                $timings = [
                    [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                ];
            }

            $variables = compact('startTime', 'endTime', 'bookingTime', 'bookings', 'company', 'timings');
        }
        elseif ($bookingTime->status == 'disabled') {
            $msg = __('messages.bookingNotAllowed');
            return Reply::dataOnly(['status' => 'bookingNotAllowed', 'msg' => $msg]);
        }

        $view = view('front.booking_slots', $variables)->render();
        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function validateGoogleReCaptcha($googleReCaptchaResponse)
    {
        $client = new Client();
        $response = $client->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'form_params' => [
                    'secret' => $this->googleCaptchaSettings->v2_secret_key,
                    'response' => $googleReCaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ]
            ]
        );

        $body = json_decode((string)$response->getBody());

        return $body->success;
    }

    public function googleRecaptchaMessage()
    {
        throw ValidationException::withMessages([
            'g-recaptcha-response' => [trans('app.recaptchaFailed')],
        ]);
    }

    public function saveBooking(StoreFrontBooking $request)
    {
        /* if user is registered then login else do register */
        if ($this->user) {
            $user = $this->user;
        }
        else
        {
            // User type from email/username
            if($this->user){
                $user = User::where($this->user, $request->{$this->user})->first();

                // Check google recaptcha if setting is enabled
                if ($this->googleCaptchaSettings->status == 'active' && $this->googleCaptchaSettings->v2_status == 'active' && (is_null($user) || ($user && !$user->hasRole('admin'))))
                {
                    // Checking is google recaptcha is valid
                    $gReCaptchaResponseInput = 'g-recaptcha-response';
                    $gReCaptchaResponse = $request->{$gReCaptchaResponseInput};
                    $validateRecaptcha = $this->validateGoogleReCaptcha($gReCaptchaResponse);

                    if (!$validateRecaptcha)
                    {
                        return $this->googleRecaptchaMessage();
                    }
                }

                $user = User::firstOrNew(['email' => $request->email]);
            }
            else{
                $user = new User();
            }

            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->email = $request->email;
            $user->mobile = $request->phone;
            $user->calling_code = $request->calling_code;
            $user->password = '123456';
            $user->save();

            $user->attachRole(Role::where('name', 'customer')->first()->id);

            Auth::loginUsingId($user->id);
            $this->user = $user;

            if ($this->smsSettings->nexmo_status == 'active' && !$user->mobile_verified) {
                // verify user mobile number
                return response(Reply::redirect(route('front.checkoutPage'), __('messages.front.success.userCreated')));
            }

            $user->notify(new NewUser('123456'));
        }

        $products = (array)json_decode(request()->cookie('products', true));
        $keys = array_keys($products);
        $type = $products[$keys[0]]->type == 'deal' ? 'deal' : 'booking';

        // get products and bookingDetails
        $products       = json_decode($request->cookie('products'), true);

        // Get Applied Coupon Details
        $couponData     = request()->hasCookie('couponData') ? json_decode(request()->cookie('couponData'), true) : [];

        /* booking details having bookingDate, bookingTime, selected_user, emp_name */
        $bookingDetails = json_decode($request->cookie('bookingDetails'), true);
        $location = Location::with('timezone')->where('id', $request->location)->first();

        if(count($products) == 1)
        {
            $unique_key = array_keys($products);
            $service_type = $products[$unique_key[0]]['service_type'];

            if($service_type == 'online')
            {
                $bookingDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $bookingDetails['bookingDate'].' '.$bookingDetails['bookingTime'], $location->timezone->zone_name)->setTimezone('UTC')->format('Y-m-d H:i:s');

                $booking = Booking::where('user_id', $this->user->id)->where('date_time', $bookingDateTime)->where('status', '!=', 'canceled')->first();

                if($booking)
                {
                    return Reply::error(__('app.bookingAlreadyExist'));
                }
            }
        }

        if (is_null($products) && ($type != 'deal' || is_null($bookingDetails))) {
            return response(Reply::redirect(route('front.index')));
        }

        if($type == 'booking')
        {
            // get bookings and bookingTime as per bookingDetails date
            $bookingDate = Carbon::createFromFormat('Y-m-d', $bookingDetails['bookingDate']);
            $day = $bookingDate->format('l');
            $bookingTime = BookingTime::where('day', strtolower($day))->first();

            $bookings = Booking::select('id', 'date_time')->where(DB::raw('DATE(date_time)'), $bookingDate->format('Y-m-d'))->whereRaw('DAYOFWEEK(date_time) = ' . ($bookingDate->dayOfWeek + 1))->get();

            if ($bookingTime->max_booking != 0 && $bookings->count() > $bookingTime->max_booking) {
                return response(Reply::redirect(route('front.bookingPage')))->withCookie(Cookie::forget('bookingDetails'));
            }
        }

        $originalAmount = $taxAmount = $amountToPay = $discountAmount = $couponDiscountAmount = 0;

        $bookingItems = array();
        $companyId = 0;

        $tax = 0;
        $Amt = 0;
        $taxName = [];
        $taxAmount = 0;
        $taxPercent = 0;

        foreach ($products as $key => $product) {
            $companyId = $product['companyId'];

            $amount = convertedOriginalPrice($companyId, ($product['quantity'] * $product['price']));

            $deal_id = ($product['type'] == 'deal') ? $product['id'] : null;

            $business_service_id = ($product['type'] == 'service') ? $product['id'] : null;

            $bookingItems[] = [
                'business_service_id' => $business_service_id,
                'quantity' => $product['quantity'],
                'unit_price' => convertedOriginalPrice($companyId, $product['price']),
                'amount' => $amount,
                'deal_id' => $deal_id,
            ];


            $originalAmount = ($originalAmount + $amount);

            if ($type !== 'deal'){
                $taxes = ItemTax::with('tax')->where('service_id', $product['id'])->get();
            }
            else {
                $taxes = ItemTax::with('tax')->where('deal_id', $product['id'])->get();
            }

            $tax = 0;

            foreach ($taxes as $key => $value) {
                $tax += $value->tax->percent;
                $taxName[] = $value->tax->name;
                $taxPercent += $value->tax->percent;
            }

            $Amt = $product['price'] * $product['quantity'];
            $taxAmount += ($Amt * $tax) / 100;
        }

        $amountToPay = ($originalAmount + $taxAmount);

        if ($couponData) {
            if ($amountToPay <= $couponData['applyAmount'])
            {
                $amountToPay = 0;
            }
            else {
                $amountToPay -= $couponData['applyAmount'];
            }

            $couponDiscountAmount = $couponData['applyAmount'];
        }

        $amountToPay = round($amountToPay, 2);

        $dateTime = $type !== 'deal' ? Carbon::createFromFormat('Y-m-d', $bookingDetails['bookingDate'])->format('Y-m-d') . ' ' . Carbon::createFromFormat('H:i:s', $bookingDetails['bookingTime'])->format('H:i:s') : null;
        $currencyId = Company::withoutGlobalScope(CompanyScope::class)->find($companyId)->currency_id;

        $booking = new Booking();
        $booking->company_id = $companyId;
        $booking->user_id = $user->id;
        $booking->currency_id = $currencyId;


        if ($type !== 'deal') {
            if($location->timezone->zone_name !== $this->settings->timezone)
            {
                $dateTime = Carbon::parse($dateTime, $location->timezone->zone_name)->setTimezone($this->settings->timezone);
            }

            $booking->date_time = $dateTime;
        }

        $unique_key = array_keys($products);
        $service_type = $products[$unique_key[0]]['service_type'];

        $settings = Company::findOrFail($companyId);

        if(($service_type === 'online' && $settings->approve_online_booking === 'active') || ($service_type === 'offline' && $settings->approve_offline_booking === 'active'))
        {
            $booking->status = 'approved';
        }
        else
        {
            $booking->status = 'pending';
        }

        if ($type == 'deal') {
            $deal_service_type = '';

            foreach ($products as $product) {

                $deal_service_type = $product['type'];
            }

            $booking->booking_type = $deal_service_type;
        }
        else
        {
            $booking->booking_type = $service_type;
        }

        $booking->payment_gateway = 'cash';
        $booking->original_amount = $originalAmount;
        $booking->discount = $discountAmount;
        $booking->discount_percent = '0';
        $booking->payment_status = 'pending';
        $booking->additional_notes = $request->additional_notes;
        $booking->location_id = $request->location;
        $booking->source = 'online';


        if (!is_null($tax)) {
            $booking->tax_name = json_encode($taxName);
            $booking->tax_percent = $taxPercent;
            $booking->tax_amount = $taxAmount;
        }

        if (count($couponData) > 0 && !is_null($couponData)) {
            $booking->coupon_id = $couponData[0]['id'];
            $booking->coupon_discount = $couponDiscountAmount;
            $coupon = Coupon::findOrFail($couponData[0]['id']);
            $coupon->used_time = ($coupon->used_time + 1);
            $coupon->save();
        }

        foreach ($bookingItems as $key => $bookingItem) {
            if ($bookingItem['deal_id']) {
                $deal = Deal::findOrFail($bookingItem['deal_id']);
                $deal->used_time = ((int)$deal->used_time + 1);
                $deal->update();
            }
        }

        $booking->amount_to_pay = $amountToPay;
        $booking->save();

        // create and save order for razorpay
        $data = [
            'amount' => $booking->converted_amount_to_pay * 100,
            'currency' => $this->settings->currency->currency_code,
        ];

        $credentials = PaymentGatewayCredentials::withoutGlobalScopes()->first();

        if ($credentials->razorpay_status === 'active') {
            $booking->order_id = Razorpay::createOrder($data)->id;
        }

        $booking->save();

        if($type !== 'deal')
        {
            /* Assign Suggested User To Booking */
            if (!empty(json_decode($request->cookie('bookingDetails'))->selected_user)) {
                $booking->users()->attach(json_decode($request->cookie('bookingDetails'))->selected_user);
                setcookie('selected_user', '', time() - 3600);
            }
            else {
                if ($this->suggestEmployee($booking->date_time)) {
                    $booking->users()->attach($this->suggestEmployee($booking->date_time));
                    setcookie('user_id', '', time() - 3600);
                }
            }
        }

        foreach ($bookingItems as $key => $bookingItem) {
            $bookingItems[$key]['booking_id'] = $booking->id;
            $bookingItems[$key]['company_id'] = $companyId;
        }

        foreach($bookingItems as $bookingItem) {
            $item = new BookingItem();
            $item->business_service_id = $bookingItem['business_service_id'];
            $item->quantity = $bookingItem['quantity'];
            $item->unit_price = $bookingItem['unit_price'];
            $item->amount = $bookingItem['amount'];
            $item->deal_id = $bookingItem['deal_id'];
            $item->booking_id = $bookingItem['booking_id'];
            $item->company_id = $bookingItem['company_id'];

            $item->save();
        }

        return response(Reply::redirect(route('front.payment-gateway'), __('messages.front.success.bookingCreated')))->withCookie(Cookie::forget('bookingDetails'))->withCookie(Cookie::forget('couponData'))->withCookie(Cookie::forget('products'));

    }

    public function searchServices(Request $request)
    {
        $route = Route::currentRouteName();
        $search = strtolower($request->term);
        $location = '';

        if (!is_null($request->globalSearchLoc)) {
            $location = $request->globalSearchLoc;
        }
        elseif (!is_null($request->globalSearchMobileLoc)) {
            $location = $request->globalSearchMobileLoc;
        }

        $service = BusinessService::withoutGlobalScope(CompanyScope::class)
            ->activeCompany()
            ->where('name', 'LIKE', '%'.$search.'%')
            ->orderBy('id', 'DESC')
            ->first();

        $company_id = Company::withoutGlobalScope(CompanyScope::class)
            ->active()
            ->where('company_name', 'LIKE', '%'.$search.'%')
            ->orderBy('id', 'DESC')->first();

        $company_id = $company_id ? $company_id->id : '';

        $category_id = Category::where('name', 'LIKE', '%'.$search.'%')->orderBy('id', 'DESC')->first();

        $category_id = $category_id ? $category_id->id : '';

        if ($search != '' && $service && $service->name != '')
        {
            $universalSearches = UniversalSearch::withoutGlobalScope(CompanyScope::class)->where('searchable_type', 'service')->where('type', 'frontend')->where('title', $service->name)->first();

            if($universalSearches != null) {
                $universalSearch = UniversalSearch::withoutGlobalScope(CompanyScope::class)->findOrFail($universalSearches->id);
                $universalSearch->count += 1;
                $universalSearch->save();
            }
            elseif($universalSearches == null) {
                $universalSearch = new UniversalSearch();
                $universalSearch->location_id = $request->l ? $request->l : null;
                $universalSearch->searchable_id = 'keywords';
                $universalSearch->searchable_type = 'service';
                $universalSearch->title = $service->name;
                $universalSearch->route_name = $route;
                $universalSearch->count = 1;
                $universalSearch->type = 'frontend';
                $universalSearch->save();
            }
        }

        $categories = Category::get();

        return view('front.all_services', compact('categories', 'category_id', 'company_id'));
    }

    public function contact(ContactRequest $request)
    {
        $globalSetting = GlobalSetting::select('id', 'contact_email', 'company_name')->first();

        Notification::route('mail', $globalSetting->contact_email)
        ->notify(new ContactUs());

        return Reply::success(__('messages.front.success.emailSent'));
    }

    public function serviceDetail(Request $request, $categorySlug, $serviceSlug)
    {
        $service = BusinessService::where('slug', $serviceSlug)
            ->activeCompany()
            ->withoutGlobalScope(CompanyScope::class)
            ->with([
                'company' => function($q){
                    $q->withoutGlobalScope(CompanyScope::class);
                },
                'location' => function($q){
                    $q->withoutGlobalScope(CompanyScope::class);
                },
                'ratings' => function($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
                    $q->active();
                },
            ])
        ->whereHas('category', function ($q) use($categorySlug) {
            $q->whereSlug($categorySlug);
        })->first();

        $products = json_decode($request->cookie('products'), true) ?: [];

        $reqProduct = 0;

        if(array_key_exists($service->id, $products))
        {
            $reqProduct = $products[$service->id]['quantity'];
        }

        if($service){
            return view('front.service_detail', compact('service', 'reqProduct'));
        }

        abort(404);
    }

    public function dealDetail(Request $request, $dealSlug)
    {
        $deal = Deal::withoutGlobalScope(CompanyScope::class)
            ->activeCompany()
            ->with([
            'company' => function($q){
                $q->withoutGlobalScope(CompanyScope::class);
            },
            'location' => function($q){
                $q->withoutGlobalScope(CompanyScope::class);
            }, 'location.timezone',
        ])->where('slug', $dealSlug)->first();

        /* to show update cart and delete item */
        $products = json_decode($request->cookie('products'), true) ?: [];
        $reqProduct = array_filter($products, function ($product) use ($deal) {
            return $product['unique_id'] == 'deal'.$deal->id;
        });

        if($deal){
            return view('front.deal_detail', compact('deal', 'reqProduct'));
        }

        abort(404);
    }

    public function allLocations()
    {
        $locations = Location::active()->get();
        return Reply::dataOnly(['locations' => $locations]);
    }

    public function page($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return view('front.page', compact('page'));
    }

    public function changeLanguage($code)
    {
        $language = Language::where('language_code', $code)->first();

        if (!$language) {
            return Reply::error(__('messages.coupon.invalidCode'));
        }

        return response(Reply::dataOnly(['message' => __('messages.languageChangedSuccessfully')]))->withCookie(cookie('appointo_multi_vendor_language_code', $code));
    }

    public function applyCoupon(ApplyRequest $request)
    {
        $couponCode         = strtolower($request->coupon);
        $products           = json_decode($request->cookie('products'), true);
        $tax                = Tax::active()->first();
        $couponCompanyIds   = [];
        $productAmount      = 0;

        if (!$products) {
            return Reply::error(__('messages.coupon.addProduct'));
        }

        foreach ($products as $product) {
            $productAmount += $product['price'] * $product['quantity'];
            $couponCompanyIds[] = $product['companyId'];
        }

        /* check if coupon code exist. */
        if(is_null($couponCompanyIds) && $couponCompanyIds == null) {
            return Reply::error(__('messages.coupon.invalidCode'));
        }

        if ($tax == null) {
            $percentAmount = 0;
        }
        else {
            $percentAmount = ($tax->percent / 100) * $productAmount;
        }

        $totalAmount   = ($productAmount + $percentAmount);

        $currentDate = Carbon::now()->format('Y-m-d H:i:s');

        $couponData = Coupon::where('coupons.start_date_time', '<=', $currentDate)
            ->where(function ($query) use ($currentDate) {
                $query->whereNull('coupons.end_date_time')
                    ->orWhere('coupons.end_date_time', '>=', $currentDate);
            })
            ->where('coupons.status', 'active')
            ->where('coupons.code', $couponCode)
            ->first();

        if (!is_null($couponData) && $couponData->minimum_purchase_amount != 0 && $couponData->minimum_purchase_amount != null && $productAmount < $couponData->minimum_purchase_amount) {
            return Reply::error(__('messages.coupon.minimumAmount') . ' ' . currencyFormatter($couponData->minimum_purchase_amount));
        }

        if (!is_null($couponData) && $couponData->used_time >= $couponData->uses_limit && $couponData->uses_limit != null && $couponData->uses_limit != 0) {
            return Reply::error(__('messages.coupon.usedMaximun'));
        }

        if (!is_null($couponData)) {
            $days = json_decode($couponData->days);
            $currentDay = Carbon::now()->format('l');

            if (in_array($currentDay, $days)) {

                if (!is_null($couponData->amount) && $couponData->amount !== 0 && $couponData->discount_type === 'percentage') {
                    $percentAmnt = round(($couponData->amount / 100) * $totalAmount, 2);

                    if (!is_null($couponData->amount) && $percentAmnt >= $totalAmount) {
                        $percentAmnt = $couponData->amount;
                    }

                    return response(Reply::dataOnly(['amount' => $percentAmnt, 'couponData' => $couponData]))->cookie('couponData', json_encode([$couponData, 'applyAmount' => $percentAmnt]));
                }
                elseif (!is_null($couponData->amount) && $couponData->amount !== 0 && $couponData->discount_type === 'amount') {
                    return response(Reply::dataOnly(['amount' => $couponData->amount, 'couponData' => $couponData]))->cookie('couponData', json_encode([$couponData, 'applyAmount' => $couponData->amount]));
                }
            }
            else {
                return response(
                    Reply::error(__(
                        'messages.coupon.notValidToday',
                        ['day' => __('app.' . strtolower($currentDay))]
                    ))
                );
            }
        }

        return Reply::error(__('messages.coupon.notMatched'));
    }

    public function updateCoupon(Request $request)
    {
        $couponTitle = strtolower($request->coupon);
        $products    = json_decode($request->cookie('products'), true);
        $tax         = Tax::active()->first();

        $productAmount = 0;

        foreach ($products as $product) {
            $productAmount += $product['price'] * $product['quantity'];
        }

        $percentAmount = ($tax->percent / 100) * $productAmount;
        $totalAmount   = ($productAmount + $percentAmount);

        $currentDate = Carbon::now()->format('Y-m-d H:i:s');

        $couponData = Coupon::where('coupons.start_date_time', '<=', $currentDate)
            ->where(function ($query) use ($currentDate) {
                $query->whereNull('coupons.end_date_time')
                    ->orWhere('coupons.end_date_time', '>=', $currentDate);
            })
            ->where('coupons.status', 'active')
            ->where('coupons.code', $couponTitle)
            ->first();

        if (!is_null($couponData) && $couponData->minimum_purchase_amount != 0 && $couponData->minimum_purchase_amount != null && $productAmount < $couponData->minimum_purchase_amount) {
            return Reply::errorWithoutMessage();
        }

        if (!is_null($couponData) && $couponData->used_time >= $couponData->uses_limit && $couponData->uses_limit != null && $couponData->uses_limit != 0) {
            return Reply::errorWithoutMessage();
        }

        if (!is_null($couponData) && $productAmount > 0) {
            $days = json_decode($couponData->days);
            $currentDay = Carbon::now()->format('l');

            if (in_array($currentDay, $days)) {

                if (!is_null($couponData->percent) && $couponData->percent != 0) {
                    $percentAmnt = round(($couponData->percent / 100) * $totalAmount, 2);

                    if (!is_null($couponData->amount) && $percentAmnt >= $couponData->amount) {
                        $percentAmnt = $couponData->amount;
                    }

                    return response(Reply::dataOnly(['amount' => $percentAmnt, 'couponData' => $couponData]))->cookie('couponData', json_encode([$couponData, 'applyAmount' => $percentAmnt]));
                }
                elseif (!is_null($couponData->amount) && (is_null($couponData->percent) || $couponData->percent == 0)) {
                    return response(Reply::dataOnly(['amount' => $couponData->amount, 'couponData' => $couponData]))->cookie('couponData', json_encode([$couponData, 'applyAmount' => $couponData->amount]));
                }
            }
            else {
                return Reply::errorWithoutMessage();
            }
        }

        return Reply::errorWithoutMessage();
    }

    public function removeCoupon(Request $request)
    {
        return response(Reply::dataOnly([]))->withCookie(Cookie::forget('couponData'));
    }

    public function checkUserAvailability(Request $request)
    {
        $company = $this->getCartCompanyDetail();

        /* check for all employee of that service, of that particular location  */

        $location = Location::where('id', $request->location_id)->first();
        $dateTime = Carbon::createFromFormat('Y-m-d H:i:s', $request->date, $location->timezone->zone_name)->setTimezone('UTC');

        [$service_ids, $service_names] = Arr::divide(json_decode($request->cookie('products'), true));

        $user_lists = BusinessService::with('users')->where('company_id', $company->id)->where('location_id', $request->location_id)->whereIn('id', $service_ids)->get();

        $all_users_of_particular_services = array();

        foreach($user_lists as $user_list) {
            foreach($user_list->users as $user) {
                $all_users_of_particular_services[] = $user->id;
            }
        }

        /* Employee schedule: */
        $day = $dateTime->format('l');
        $time = $dateTime->format('H:i:s');
        $date = $dateTime->format('Y-m-d');
        $bookingTime = BookingTime::where('day', strtolower($day))->first();
        $slot_select = $date.' '.$time;

        $booking_slot = DB::table('bookings')->whereBetween('date_time', [$slot_select,$dateTime->addMinutes($bookingTime->slot_duration)])
            ->get();

        /* Maximum Number of Booking Allowed Per Slot check */
        if ($bookingTime->per_slot_max_booking != (0 || '') && $bookingTime->per_slot_max_booking <= $booking_slot->count() )
        {
            return response(Reply::dataOnly(['status' => 'fail']));
        }

        /* if no employee for that particular service is found then allow booking with null employee assignment  */
        if(empty($all_users_of_particular_services) || ($this->getCartCompanyDetail()->employee_selection == '' || $this->getCartCompanyDetail()->employee_selection == 'disabled')) {
            return response(Reply::dataOnly(['continue_booking' => 'yes']));
        }

        /* Check for employees working on that day: */
        $employeeWorking = EmployeeSchedule::with('employee')->where('company_id', $company->id)->where('location_id', $request->location_id)->where('days', $day)
            ->whereTime('start_time', '<=', $time)->whereTime('end_time', '>=', $time)
            ->where('is_working', 'yes')->whereIn('employee_id', $all_users_of_particular_services)->get();

        $working_employee = array();

        foreach($employeeWorking as $employeeWorkings) {
            $working_employee[] = $employeeWorkings->employee->id;
        }

        $assigned_user_list_array = array();
        $assigned_users_list = Booking::with('users')->where('company_id', $company->id)
            ->where('date_time', $dateTime)->get();

        foreach ($assigned_users_list as $key => $value) {
            foreach ($value->users as $key1 => $value1) {
                $assigned_user_list_array[] = $value1->id;
            }
        }

        $free_employee_list = array_diff($working_employee, array_intersect($working_employee, $assigned_user_list_array));

        $select_user = '<select name="" id="selected_user" name="selected_user" class="form-control mt-3"><option value="">--Select Employee--</option>';

        /* Leave: */
        /* check for half day */
        $halfday_leave = Leave::with('employee')->where('company_id', $company->id)->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>=', $time)->where('leave_type', 'Half day')->where('status', 'approved')->get();

        $users_on_halfday_leave = array();

        foreach($halfday_leave as $halfday_leaves) {
                $users_on_halfday_leave[] = $halfday_leaves->employee->id;
        }

        /* check for full day */
        $fullday_leave = Leave::with('employee')->where('company_id', $company->id)->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)->where('leave_type', 'Full day')->where('status', 'approved')->get();

        $users_on_fullday_leave = array();

        foreach($fullday_leave as $fullday_leaves) {
                $users_on_fullday_leave[] = $fullday_leaves->employee->id;
        }

        $employees_not_on_halfday_leave = array_diff($free_employee_list, array_intersect($free_employee_list, $users_on_halfday_leave));

        $employees_not_on_fullday_leave = array_diff($free_employee_list, array_intersect($free_employee_list, $users_on_fullday_leave));

        /* if any employee is on leave on that day */
        $employee_lists = User::allEmployees()->where('company_id', $company->id)->select('id', 'name')->whereIn('id', $free_employee_list)->get();

        $employee = User::allEmployees()->where('company_id', $company->id)->select('id', 'name')->whereIn('id', $employees_not_on_fullday_leave)->whereIn('id', $employees_not_on_halfday_leave)->get();

        if($this->getCartCompanyDetail()->employee_selection == 'enabled')
        {
            $i = 0;

            foreach($employee_lists as $employee_list)
            {
                $user_schedule = $this->checkUserSchedule($employee_list->id, $request->date);

                if($this->getCartCompanyDetail()->disable_slot == 'enabled')
                {
                    foreach ($employee as $key => $employees) {

                        if($user_schedule == true) {
                            $select_user .= '<option value="'.$employees->id.'">'.$employees->name.'</option>';
                            $i++;
                            $select_user .= '</select>';
                        }

                        if($i > 0) {
                            return response(Reply::dataOnly(['continue_booking' => 'yes', 'select_user' => $select_user]));
                        }

                        return response(Reply::dataOnly(['continue_booking' => 'no']));
                    }
                }
                else
                {
                    foreach ($employee as $key => $employees) {
                        $select_user .= '<option value="'.$employees->id.'">'.$employees->name.'</option>';
                    }

                    $select_user .= '</select>';
                    return response(Reply::dataOnly(['continue_booking' => 'yes', 'select_user' => $select_user]));
                }
            }
        }

        /* if no employee found of that particular service */
        if(empty($free_employee_list)) {

            if($this->getCartCompanyDetail()->multi_task_user == 'enabled') {
                /* give dropdown of all users */

                if($this->getCartCompanyDetail()->employee_selection == 'enabled') {
                    $employee_lists = User::allEmployees()->select('id', 'name')->whereIn('id', $all_users_of_particular_services)->get();

                    foreach ($employee_lists as $key => $employee_list) {
                        $select_user .= '<option value="'.$employee_list->id.'">'.$employee_list->name.'</option>';
                    }

                    $select_user .= '</select>';
                    return response(Reply::dataOnly(['continue_booking' => 'yes', 'select_user' => $select_user]));
                }
            }
            else {
                /* block booking here  */
                return response(Reply::dataOnly(['continue_booking' => 'no']));
            }
        }

        /* if multitasking and allow employee selection is enabled */
        if($this->getCartCompanyDetail()->multi_task_user == 'enabled') {
            /* give dropdown of all users */
            if($this->getCartCompanyDetail()->employee_selection == 'enabled') {
                $employee_lists = User::allEmployees()->select('id', 'name')->whereIn('id', $all_users_of_particular_services)->get();

                foreach ($employee_lists as $key => $employee_list) {
                    $select_user .= '<option value="'.$employee_list->id.'">'.$employee_list->name.'</option>';
                }

                $select_user .= '</select>';
                return response(Reply::dataOnly(['continue_booking' => 'yes', 'select_user' => $select_user]));
            }
        }

        /* select of all remaining employees */
        $employee_lists = User::allEmployees()->select('id', 'name')->whereIn('id', $free_employee_list)->get();

        if($this->getCartCompanyDetail()->employee_selection == 'enabled') {
            $i = 0;

            foreach ($employee_lists as $key => $employee_list) {
                $user_schedule = $this->checkUserSchedule($employee_list->id, $request->date);

                if($this->getCartCompanyDetail()->disable_slot == 'enabled') {
                    // call function which will see employee schedules
                    if($user_schedule == true) {
                        $select_user .= '<option value="'.$employee_list->id.'">'.$employee_list->name.'</option>';
                        $i++;
                    }
                }
                else {
                    if($user_schedule == true) {
                        $select_user .= '<option value="'.$employee_list->id.'">'.$employee_list->name.'</option>';
                        $i++;
                    }
                }
            }

            $select_user .= '</select>';

            if($i > 0) {
                return response(Reply::dataOnly(['continue_booking' => 'yes', 'select_user' => $select_user]));
            }

            return response(Reply::dataOnly(['continue_booking' => 'no']));
        }

        $user_check_array = array();

        foreach ($employee_lists as $key => $employee_list) {
            // call function which will see employee schedules
            $user_schedule = $this->checkUserSchedule($employee_list->id, $request->date);

            if($user_schedule == true) {
                $user_check_array[] = $employee_list->id;
            }
        }

        if(empty($user_check_array)) {
            return response(Reply::dataOnly(['continue_booking' => 'no']));
        }
    }

    public function checkUserSchedule($userid, $dateTime)
    {
        $new_booking_start_time = Carbon::parse($dateTime)->format('Y-m-d H:i');
        $time = $this->calculateCartItemTime();
        $end_time1 = Carbon::parse($dateTime)->addMinutes($time - 1);

        $userBooking = Booking::whereIn('status', ['pending','in progress', 'approved'])->with('users')->whereHas('users', function($q)use($userid){
            $q->where('user_id', $userid);
        });
        $bookings = $userBooking->get();

        if($userBooking->count() > 0) {
            foreach ($bookings as $key => $booking) {
                /* previous booking start date and time */
                $start_time = Carbon::parse($booking->date_time)->format('Y-m-d H:i');
                $booking_time = $this->calculateBookingTime($booking->id);
                $end_time = $booking->date_time->addMinutes($booking_time - 1);

                if( Carbon::parse($new_booking_start_time)->between($start_time, Carbon::parse($end_time)->format('Y-m-d H:i'), true) || Carbon::parse($start_time)->between($new_booking_start_time, Carbon::parse($end_time1)->format('Y-m-d H:i'), true) ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function calculateBookingTime($booking_id)
    {
        $booking_time_type = $this->getCartCompanyDetail()->booking_time_type;
        $booking_items = BookingItem::with('businessService')->where('booking_id', $booking_id)->get();
        $time = 0;
        $total_time = 0;
        $max = 0;
        $min = 0;

        foreach ($booking_items as $key => $item)
        {
            if ($item->businessService->time_type == 'minutes') {
                $time = $item->businessService->time;
            }
            elseif ($item->businessService->time_type == 'hours') {
                $time = $item->businessService->time * 60;
            }
            elseif ($item->businessService->time_type == 'days') {
                $time = $item->businessService->time * 24 * 60;
            }

            $total_time += $time;

            if ($key == 0) { $min = $time; $max = $time;
            }

            if ($time < $min) { $min = $time;
            }

            if ($time > $max) { $max = $time;
            }
        }

        if ($booking_time_type == 'sum') { return $total_time;
        }
        elseif ($booking_time_type == 'max') { return $max;
        }
        elseif ($booking_time_type == 'min') { return $min;
        }
        elseif ($booking_time_type == 'avg') { return $total_time / $booking_items->count();
        }
    }

    public function calculateCartItemTime()
    {
        $booking_time_type = $this->getCartCompanyDetail()->booking_time_type;

        $products = json_decode(request()->cookie('products'), true);

        $bookingIds = [];

        foreach ($products as $key => $product) {
            $bookingIds[] = $key;
        }

        $booking_items = BusinessService::whereIn('id', $bookingIds)->get();

        $time = 0;
        $total_time = 0;
        $max = 0;
        $min = 0;

        foreach ($booking_items as $key => $booking_item) {

            if ($booking_item->time_type == 'minutes') {
                $time = $booking_item->time;
            }
            elseif ($booking_item->time_type == 'hours') {
                $time = $booking_item->time * 60;
            }
            elseif ($booking_item->time_type == 'days') {
                $time = $booking_item->time * 24 * 60;
            }

            $total_time += $time;

            if ($key == 0) { $min = $time; $max = $time;
            }

            if ($time < $min) {  $min = $time;
            }

            if ($time > $max) { $max = $time;
            }
        }

        if ($booking_time_type == 'sum') { return $total_time;
        }
        elseif ($booking_time_type == 'max') { return $max;
        }
        elseif ($booking_time_type == 'min') { return $min;
        }
        elseif ($booking_time_type == 'avg') { return $total_time / $booking_items->count();
        }
    }

    public function grabDeal(Request $request)
    {
        $deal = [
            'dealId' => $request->dealId,
            'dealPrice' => $request->dealPrice,
            'dealName' => $request->dealName,
            'dealQuantity' => $request->dealQuantity,
            'dealUnitPrice' => $request->dealUnitPrice,
            'dealCompanyName' => $request->dealCompanyName,
            'dealMaxQuantity' => $request->dealMaxQuantity,
            'dealCompanyId' => $request->dealCompanyId,
        ];

        return response([
            'status' => 'success',
            'message' => 'deal added successfully',
            ])->cookie('deal', json_encode($deal));
    }

    public function suggestEmployee($date)
    {
        /* check for all employee of that service, of that particular location  */
        $dateTime = $date;

        [$service_ids, $service_names] = Arr::divide(json_decode(request()->cookie('products'), true));

        $user_lists = BusinessService::with('users')->whereIn('id', $service_ids)->get();

        $all_users_of_particular_services = array();

        foreach ($user_lists as $user_list) {
            foreach ($user_list->users as $user) {
                $all_users_of_particular_services[] = $user->id;
            }
        }

        /* if no empolyee for that particular service is found then allow booking with null employee assignment  */
        if (empty($all_users_of_particular_services)) {
            return '';
        }

          /* Employee schedule: */
          $day = $dateTime->format('l');
          $time = $dateTime->format('H:i:s');
          $date = $dateTime->format('Y-m-d');

          /* Check for employees working on that day: */
          $employeeWorking = EmployeeSchedule::with('employee')->where('days', $day)
              ->whereTime('start_time', '<=', $time)->whereTime('end_time', '>=', $time)
              ->where('is_working', 'yes')->whereIn('employee_id', $all_users_of_particular_services)->get();

          $working_employee = array();

        foreach($employeeWorking as $employeeWorkings) {
                $working_employee[] = $employeeWorkings->employee->id;
        }

        $assigned_user_list_array = array();
        $assigned_users_list = Booking::with('users')
            ->where('date_time', $dateTime)
            ->get();

        foreach ($assigned_users_list as $key => $value) {
            foreach ($value->users as $key1 => $value1) {
                $assigned_user_list_array[] = $value1->id;
            }
        }

        $free_employee_list = array_diff($working_employee, array_intersect($working_employee, $assigned_user_list_array));

        /* Leave: */

        /* check for half day*/
        $halfday_leave = Leave::with('employee')->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)->whereTime('start_time', '<=', $time)
            ->whereTime('end_time', '>=', $time)->where('leave_type', 'Half day')->where('status', 'approved')->get();

        $users_on_halfday_leave = array();

        foreach($halfday_leave as $halfday_leaves) {
                $users_on_halfday_leave[] = $halfday_leaves->employee->id;
        }

        /* check for full day*/
        $fullday_leave = Leave::with('employee')->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)->where('leave_type', 'Full day')->where('status', 'approved')->get();

        $users_on_fullday_leave = array();

        foreach($fullday_leave as $fullday_leaves) {
                $users_on_fullday_leave[] = $fullday_leaves->employee->id;
        }

        $employees_not_on_halfday_leave = array_diff($free_employee_list, array_intersect($free_employee_list, $users_on_halfday_leave));

        $employees_not_on_fullday_leave = array_diff($free_employee_list, array_intersect($free_employee_list, $users_on_fullday_leave));

        $companyId = Role::select('company_id')->where('id', auth()->user()->role->id)->first()->company_id;
        $company = Company::where('id', $companyId)->first();

        /* if any employee is on leave on that day */
        if($this->getCartCompanyDetail()->employee_selection == 'enabled') {

            return User::allEmployees()->select('id', 'name')->whereIn('id', $employees_not_on_fullday_leave)->whereIn('id', $employees_not_on_halfday_leave)->get();

        }

        /* if no employee found then return allow booking with no employee assignment   */
        if (empty($free_employee_list)) {
            if ($this->getCartCompanyDetail()->multi_task_user == 'enabled') {
                /* give single users */
                return User::select('id', 'name')->whereIn('id', $all_users_of_particular_services)->first()->id;
            }
        }

        /* select of all remaining employees */
        $users = User::select('id', 'name')->whereIn('id', $free_employee_list);

        if ($this->settings->disable_slot == 'enabled') {

            foreach ($users->get() as $key => $employee_list) {
                // call function which will see employee schedules
                $user_schedule = $this->checkUserSchedule($employee_list->id, $date);

                if ($user_schedule == true) {
                    return $employee_list->id;
                }
            }
        }

        return $users->first()->id;
    }

    public function allDeals(Request $request)
    {
        if($request->ajax()){
            $deals = Deal::active()->withoutGlobalScope(CompanyScope::class)
                ->activeCompany()
                ->with([
                        'company' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                        'location' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                        'services' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                    ])
                ->where('start_date_time', '<=', Carbon::now()->setTimezone($this->settings->timezone))
                ->where('end_date_time', '>=', Carbon::now()->setTimezone($this->settings->timezone))
                ->whereRaw('json_contains(days, \'["' . Carbon::now()->setTimezone($this->settings->timezone)->isoFormat('dddd') . '"]\')');

            if(!is_null($request->locations)) {
                $locations = explode(',', $request->locations);
                $deals->WhereHas('location', function($query) use($locations) {
                    $query->WhereIn('id', $locations);
                });
            }

            if(!is_null($request->categories)) {
                $categories = explode(',', $request->categories);
                $deals->WhereHas('services.businessService.category', function($query) use($categories) {
                    $query->WhereIn('id', $categories);
                });
            }

            if(!is_null($request->companies)) {
                $companies = explode(',', $request->companies);
                $deals->WhereIn('company_id', $companies);
            }

            if(!is_null($request->price)) {
                $prices = $request->price;

                $firstPrice = explode('-', array_shift($prices));
                $low = $firstPrice[0];
                $high = $firstPrice[1];

                $priceArr = [];

                foreach ($prices as $price) {
                    $priceArr[] = [
                        explode('-', $price)[0],
                        explode('-', $price)[1],
                    ];
                }

                $deals = $deals->whereBetween('deal_amount', [$low,$high]);

                foreach ($priceArr as $price) {
                    $deals = $deals->orWhereBetween('deal_amount', [$price[0], $price[1]]);
                }
            }

            if(!is_null($request->discounts)) {
                $discounts = $request->discounts;

                $firstDiscount = explode('-', array_shift($discounts));
                $low = $firstDiscount[0];
                $high = $firstDiscount[1];

                $discountArr = [];

                foreach ($discounts as $discount) {
                    $discountArr[] = [
                        explode('-', $discount)[0],
                        explode('-', $discount)[1],
                    ];
                }

                $deals = $deals->where('discount_type', 'percentage')->where('percentage', '>=', $low);

                foreach ($discountArr as $discount) {

                    $deals = $deals->where('discount_type', 'percentage')->orWhere('percentage', '>=', $discount[0]);
                }
            }

            if(!is_null($request->sort_by)) {
                if($request->sort_by == 'newest') {
                    $deals->orderBy('id', 'DESC');
                }
                elseif($request->sort_by == 'low_to_high') {
                    $deals->orderBy('deal_amount');
                }
                elseif($request->sort_by == 'high_to_low') {
                    $deals->orderBy('deal_amount', 'DESC');
                }
            }

            $deals = $deals->paginate(10);

            $view = view('front.filtered_deals', compact('deals'))->render();
            return Reply::dataOnly(['view' => $view, 'deal_count' => $deals->count(), 'deal_total' => $deals->total()]);
        }

        $companies = Company::withoutGlobalScope(CompanyScope::class)->get();
        $categories = Category::withoutGlobalScope(CompanyScope::class)->has('services', '>', 0)->get();
        $locations = Location::withoutGlobalScope(CompanyScope::class)->active()->get();
        return view('front.all_deals', compact('locations', 'categories', 'companies'));
    }

    public function allServices(Request $request)
    {
        if($request->ajax())
        {
            $services = BusinessService::withoutGlobalScope(CompanyScope::class)
                ->activeCompany()
                ->with([
                    'location' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                    } ,
                    'category' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                    } ,
                    'company' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                    },
                    'ratings' => function($q) {
                        $q->withoutGlobalScope(CompanyScope::class);
                        $q->active();
                    },
                ])->active();

            if(!is_null($request->service_name)) {
                $services = $services->where('name', 'like', '%'.$request->service_name.'%');
            }

            if(is_null($request->company_id) && !is_null($request->term)) {
                $services = $services->where('name', 'like', '%'.$request->term.'%');
            }

            if(!is_null($request->company_id)) {
                $company_id = $request->company_id;
                $services = $services->whereHas('company', function($q) use($company_id){
                    $q->where('id', $company_id);
                });
            }

            if(!is_null($request->locations)) {
                $locations = explode(',', $request->locations);
                $services->whereIn('location_id', $locations);
            }

            if(!is_null($request->categories)) {
                $categories = explode(',', $request->categories);
                $services->whereIn('category_id', $categories);
            }

            if(!is_null($request->companies)) {
                $companies = explode(',', $request->companies);
                $services->whereIn('company_id', $companies);
            }

            if(!is_null($request->price)) {
                $prices = $request->price;

                $firstPrice = explode('-', array_shift($prices));
                $low = $firstPrice[0];
                $high = $firstPrice[1];

                $priceArr = [];

                foreach ($prices as $price) {
                    $priceArr[] = [
                        explode('-', $price)[0],
                        explode('-', $price)[1],
                    ];
                }

                $services = $services->whereBetween('net_price', [$low,$high]);

                foreach ($priceArr as $price) {
                    $services = $services->orWhereBetween('net_price', [$price[0], $price[1]]);
                }
            }

            if(!is_null($request->discounts)) {
                $discounts = $request->discounts;

                $firstDiscount = explode('-', array_shift($discounts));
                $low = $firstDiscount[0];
                $high = $firstDiscount[1];

                $discountArr = [];

                foreach ($discounts as $discount) {
                    $discountArr[] = [
                        explode('-', $discount)[0],
                        explode('-', $discount)[1],
                    ];
                }

                $services = $services->where('discount_type', 'percent')->whereBetween('discount', [$low,$high]);

                foreach ($discountArr as $discount) {
                    $services = $services->where('discount_type', 'percent')->orWhereBetween('discount', [$discount[0], $discount[1]]);
                }
            }

            if(!is_null($request->service_type))
            {
                $serviceType = $request->service_type;
                $services = $services->whereIn('service_type', $serviceType);
            }

            if(!is_null($request->sort_by)) {
                if($request->sort_by == 'newest') {
                    $services->orderBy('id', 'DESC');
                }
                elseif($request->sort_by == 'low_to_high') {
                    $services->orderBy('net_price');
                }
                elseif($request->sort_by == 'high_to_low') {
                    $services->orderBy('net_price', 'DESC');
                }
                elseif($request->sort_by == 'location') {
                    $record = $this->filterLocations(request()->lat, request()->long);

                    $services->whereIn('location_id', $record)->orderByRaw('FIELD(location_id , ' .implode(',', $record->toArray()) .' ) ASC');
                }
            }

            if($request->sort_by == 'location')
            {
                $services = $services->paginate($services->count());
            }
            else
            {
                $services = $services->paginate(10);
            }


            $service_count = 0;
            $service_total = $services->total();

            foreach ($services as $service)
            {
                if($service->service_type === 'offline' || ($service->service_type === 'online' && $service->company->employee_selection === 'enabled'))
                {
                    ++$service_count;
                }
                else
                {
                    --$service_total;
                }
            }

            $view = view('front.filtered_services', compact('services'))->render();
            return Reply::dataOnly(['view' => $view, 'service_count' => $service_count, 'service_total' => $service_total]);

        }

        /* end of ajax */
        $company_id = !is_null($request->company_id) ? $request->company_id : '';

        $category_id = '';

        if($request->category_id && $request->category_id != 'all'){
            $category_id = Category::where('slug', $request->category_id)->first();

            if(!$category_id) {
                abort(404);
            }

            $category_id = $category_id->id;
        }

        $categories = Category::withoutGlobalScope(CompanyScope::class)->withCount(['services' => function($q) {
            $q->withoutGlobalScope(CompanyScope::class);
        }])->has('services', '>', 0)
        ->get();

        return view('front.all_services', compact('categories', 'category_id', 'company_id'));
    }

    public function allCoupons(Request $request)
    {
        $coupons = Coupon::withoutGlobalScope(CompanyScope::class)
            ->with(['company' => function($q) {
                    $q->withoutGlobalScope(CompanyScope::class);
            }
            ]);

        if($request->ajax())
        {
            if(!is_null($request->companies)) {
                $companies = explode(',', $request->companies);
                $coupons->WhereIn('company_id', $companies);
            }

            if(!is_null($request->discounts)) {
                $price = explode('-', $request->discounts[0]);
                $low = $price[0];
                $high = $price[1];
                $coupons->whereBetween('percent', array($low,$high));
            }

            if(!is_null($request->sort_by)) {
                if($request->sort_by == 'newest') {
                    $coupons->orderBy('id', 'DESC');
                }
                elseif($request->sort_by == 'low_to_high') {
                    $coupons->orderBy('percent');
                }
                elseif($request->sort_by == 'high_to_low') {
                    $coupons->orderBy('percent', 'DESC');
                }
            }

            $coupons = $coupons->paginate(10);
            $view = view('front.filtered_coupons', compact('coupons'))->render();
            return Reply::dataOnly(['view' => $view, 'coupon_total' => $coupons->total() , 'coupon_count' => $coupons->count()]);
        }

        $companies = Company::withoutGlobalScope(CompanyScope::class)->get();
        $coupons = $coupons->paginate(10);
        return view('front.all_coupons', compact('coupons', 'companies'));
    }

    public function getCouponCompany($code)
    {
        $coupon = Coupon::where('code', $code)->first();
        return !is_null($coupon) ? $coupon->company_id : null;
    }

    /* return all the detail of company added to cart */
    public function getCartCompanyDetail()
    {
        $products = json_decode(request()->cookie('products'), true);

        $companyIds = [];

        foreach ($products as $key => $product) {
            $companyIds[] = $product['companyId'];
        }

        if(count($companyIds) > 0) {
            return Company::where('id', $companyIds[0])->first();
        }

        return null;
    }

    public function globalSearch(Request $request)
    {

        $search = $request->term;
        $location = !is_null($request->location) ? $request->location : '';
        $filterItem = [];

        $categories = Category::where('name', 'LIKE', '%'.$search.'%')->orderBy('id', 'DESC')->limit(2)->get();

        $services = BusinessService::withoutGlobalScope(CompanyScope::class)
            ->activeCompany()
            ->with([
            'location' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
            }
        ])
        ->Where('location_id', $location)
        ->where('name', 'LIKE', '%'.$search.'%')
        ->orderBy('id', 'DESC')
        ->limit(2)->get();

        $deals = Deal::withoutGlobalScope(CompanyScope::class)
            ->activeCompany()
            ->with([
            'location' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
            }
        ])
        ->WhereHas('location', function($query) use($location) {
            $query->Where('id', $location);
        })
        ->where('title', 'LIKE', '%'.$search.'%')
        ->orderBy('id', 'DESC')
        ->limit(2)->get();


        $companies = Company::withoutGlobalScope(CompanyScope::class)
            ->active()
            ->where('company_name', 'LIKE', '%'.$search.'%')
            ->orderBy('id', 'DESC')
            ->limit(2)->get();

        if(!$categories->isEmpty()) {
            foreach($categories as $category) {
                $filteredRes['title'] = $category->name;
                $filteredRes['image'] = $category->category_image_url;
                $filteredRes['url'] = url('services/'.$category->slug);
                $filteredRes['category'] = 'Category';
                $filterItem[] = $filteredRes;
            }
        }

        if(!$services->isEmpty()) {
            foreach($services as $service) {
                $filteredRes['title'] = $service->name;
                $filteredRes['image'] = $service->service_image_url;
                $filteredRes['url'] = $service->service_detail_url;
                $filteredRes['category'] = 'Service';
                $filterItem[] = $filteredRes;
            }
        }

        if(!$deals->isEmpty()) {
            foreach($deals as $deal) {
                $filteredRes['title'] = $deal->title;
                $filteredRes['image'] = $deal->deal_image_url;
                $filteredRes['url'] = $deal->deal_detail_url;
                $filteredRes['category'] = 'Deal';
                $filterItem[] = $filteredRes;
            }
        }

        if(!$companies->isEmpty()) {
            foreach($companies as $company) {
                $filteredRes['title'] = $company->company_name;
                $filteredRes['image'] = $company->logo_url;
                $filteredRes['url'] = route('front.search', ['c' => $company->id]);
                $filteredRes['category'] = 'Company';
                $filterItem[] = $filteredRes;
            }
        }

        return json_encode($filterItem);
    }

    public function register()
    {
        return view('front.register');
    }

    public function email()
    {
        return view('front.email_verification');
    }

    public function storeCompany(RegisterCompany $request)
    {
        if(request()->ajax())
        {
            // Check google recaptcha if setting is enabled
            if ($this->googleCaptchaSettings->status == 'active' && $this->googleCaptchaSettings->v2_status == 'active')
            {
                // Checking is google recaptcha is valid
                $gReCaptchaResponseInput = 'g-recaptcha-response';
                $gReCaptchaResponse = $request->{$gReCaptchaResponseInput};
                $validateRecaptcha = $this->validateGoogleReCaptcha($gReCaptchaResponse);

                if (!$validateRecaptcha)
                {
                    return $this->googleRecaptchaMessage();
                }
            }

            $data = [
                'company_name' => $request->business_name,
                'company_email' => $request->email,
                'company_phone' => $request->calling_code . '-' . $request->contact,
                'address' => $request->address,
                'website' => $request->website,
                'date_format' => 'Y-m-d',
                'time_format' => 'h:i A',
                'timezone' => 'Asia/Kolkata',
                'currency_id' => Currency::first()->id,
                'locale' => Language::first()->language_code,
            ];

            $company = Company::create($data);

            if (is_null($company->package_id)) {
                $package = Package::active()->trial()->first();

                if (!is_null($package)) {
                    $company->package_id = $package->id;
                    $company->trial_ends_at = $package->no_of_days ? Carbon::now()->addDays($package->no_of_days) : null;
                }
                else {
                    $package = Package::active()->defaultPackage()->first();
                    $company->package_id = $package->id;
                }
            }

            // create admin/employee
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'company_id' => $company->id
            ]);
            $user->attachRole(Role::withoutGlobalScope(CompanyScope::class)->select('id', 'name')->where(['name' => 'administrator', 'company_id' => $company->id])->first()->id);

            return Reply::success(__('email.verificationLinkSent'));
        }

    }

    public function confirmEmail(Request $request, $email)
    {
        $company = Company::where(['company_email' => Crypt::decryptString($email), 'verified' => 'no', 'status' => 'inactive'])->firstOrFail();

        $company->verified = 'yes';
        $company->status = 'active';
        $company->save();

        $company = User::with('company')->where('email', Crypt::decryptString($email))->first();

        $superadmin = User::with('company', 'roles')->whereHas('roles', function($q){
            $q->where('name', 'superadmin');
        })->first();

        // send welcome email to admin
        $company->notify(new CompanyWelcome());

        // send email to superadmin
        $superadmin->notify(new SuperadminNotificationAboutNewAddedCompany($company));

        return view('front/email_verified_success');
    }

    public function pricing()
    {
        $frontFaqsCount = FrontFaq::select('id', 'language_id')->where('language_id', $this->localeLanguage ? $this->localeLanguage->id : null)->count();

        $frontFaqs = FrontFaq::where('language_id', $frontFaqsCount > 0 ? ( $this->localeLanguage ? $this->localeLanguage->id : null ) : null)->get();

        $packages = Package::where('type', null)->get();
        return view('front.pricing', compact('packages', 'frontFaqs'));
    }

    public function checkDealQuantity($dealId)
    {
        $deal = Deal::find($dealId);
        $max_order_per_customer = !is_null($deal->max_order_per_customer) ? $deal->max_order_per_customer : 0;

        return $max_order_per_customer;
    }

    public function logout()
    {
        Auth::logout();
        return redirect('login');
    }

    public function vendorPage(Request $request, $slug, $location_id = null)
    {
        $this->company = Company::withoutGlobalScope(CompanyScope::class)->whereSlug($slug)
            ->active()->verified()->firstOrFail();
        $this->vendorPage = VendorPage::withoutGlobalScope(CompanyScope::class)->where('company_id', $this->company->id)->first();
        $this->categories = Category::withoutGlobalScope(CompanyScope::class)->has('services', '>', 0)->withCount(['services' => function($q) {
            $q->withoutGlobalScope(CompanyScope::class);
        }])
        ->get();
        $this->locationId = $location_id;

        $this->bookingTimes = BookingTime::where('company_id', $this->company->id)->where('location_id', $location_id)->get();

        return view('front.vendor', $this->data);
    }

    public function changeLocation(Request $request)
    {
        $this->bookingTimes = BookingTime::where('company_id', $request->company_id)->where('location_id', $request->location_id)->get();

        $view = view('front.vendor_booking_time', $this->data)->render();

        return Reply::dataOnly(['view' => $view]);
    }

    public function allCompanyDeals(Request $request, $slug)
    {
        $company = Company::withoutGlobalScope(CompanyScope::class)->whereSlug($slug)->firstOrFail();

        if($request->ajax()){
            $this->deals = Deal::withoutGlobalScope(CompanyScope::class)->where('company_id', $company->id)
                ->with([
                        'company' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                        'location' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                        'services' => function($q) { $q->withoutGlobalScope(CompanyScope::class);
                        },
                    ])->where('start_date_time', '<=', Carbon::now()->setTimezone($this->settings->timezone))
                    ->where('end_date_time', '>=', Carbon::now()->setTimezone($this->settings->timezone))
                    ->whereRaw('json_contains(days, \'["' . Carbon::now()->setTimezone($this->settings->timezone)->isoFormat('dddd') . '"]\')')->paginate(10);
            $view = view('front.vendor_deals', $this->data)->render();

            return Reply::dataOnly(['view' => $view]);
        }
    }

} /* End of class */
