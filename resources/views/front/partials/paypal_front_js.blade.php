@if ($credentials->paypal_status == 'active')
    @php
        $paypalSource = "https://www.paypal.com/sdk/js?client-id={$credentials->paypal_client_id}";
        if ($activePaypalAccountDetail) {
            $paypalSource = "https://www.paypal.com/sdk/js?client-id={$credentials->paypal_client_id}&merchant-id={$activePaypalAccountDetail}";
        }
    @endphp
    <script src={{$paypalSource}}></script>

    <script>
        paypal.Buttons({
            style: {
                color:  'black',
                shape:  'rect',
                layout: 'horizontal',
                height: 55,
                tagline: false
            },
            createOrder: function (data, actions) {
                return fetch("{{ route('front.paypal.createOrder') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(res => res.json())
                .then(data => data.id)
            },
            onApprove: function (data, actions) {
                let url = "{{ route('front.paypal.captureOrder', ['orderId' => ':orderId']) }}"
                url = url.replace(':orderId', data.orderID)

                return $.easyAjax({
                    url,
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    redirect: true
                })
            }
        }).render('#paypal-button-container');
    </script>
@endif
