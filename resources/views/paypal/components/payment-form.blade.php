<div
    x-data="{ paymentProcessing: false, paymentFailed: false }"
    x-init="
        paypal.Buttons({
            // Order is created on the server and the order id is returned
            createOrder() {
                return fetch(&quot;{{ route('lunar-paypal.orders.create') }}&quot;, {
                    method: 'post',
                    // use the &quot;body&quot; param to optionally pass additional order information
                    // like product skus and quantities
                    headers: {
                        'content-type': 'application/json',
                        'X-CSRF-TOKEN': &quot;{{ csrf_token() }}&quot;,
                    },
                    body: JSON.stringify({
                        cart_id: {{ $cart->id }},
                    }),
                }).then((response) => {
                    return response.json();
                }).then((order) => {
                    return order.id;
                });
            },
            // Finalize the transaction on the server after payer approval
            onApprove(data) {
                return fetch('/lunar-paypal/orders/' + data.orderID + '/capture', {
                    method: 'post',
                    body: JSON.stringify({
                        orderID: data.orderID,
                    }),
                }).then((response) => response.json()).then((orderData) => {
                    window.location.href = &quot;{{ $returnUrl }}&quot;
                });
            },
        }).render('#paypal-button-container');


        {{-- PayPal Hosted Fields --}}
        // If this returns false or the card fields aren't visible, see Step #1.
        if (paypal.HostedFields.isEligible()) {
                let orderId;

                // Renders card fields
                paypal.HostedFields.render({
                // Call your server to set up the transaction
                createOrder: () => {
                return fetch(&quot;{{ route('lunar-paypal.orders.create') }}&quot;, {
                method: 'post',
                headers: {
                'content-type': 'application/json',
                'X-CSRF-TOKEN': &quot;{{ csrf_token() }}&quot;,
                },
                body: JSON.stringify({
                cart_id: {{ $cart->id }},
                }),
                // use the &quot;body&quot; param to optionally pass additional order information like
                // product ids or amount.
                }).then((res) => {
                return res.json();
                }).then((orderData) => {
                orderId = orderData.id; // needed later to complete capture
                return orderData.id;
                });
                },
                styles: {
                    'input': {
                        'font-size': '16px',
                        'font-family': 'helvetica, tahoma, calibri, sans-serif',
                        'color': '#3a3a3a',
                    },
                    'input.invalid': {
                        'color': 'red',
                    },
                    'input.valid': {
                        'color': 'green',
                    },
                },
                fields: {
                    number: {
                        selector: '#card-number',
                    },
                    cvv: {
                        selector: '#cvv',
                    },
                    expirationDate: {
                        selector: '#expiration-date',
                    },
                },
            }).then((cardFields) => {
                document.querySelector('#card-form').addEventListener('submit', (event) => {
                    event.preventDefault();
                    window.dispatchEvent(new CustomEvent('payment-processing'));
                    cardFields.submit({
                        // Cardholder's first and last name
                        cardholderName: &quot;{{ $cart->billingAddress->first_name . ' ' . $cart->billingAddress->last_name }}&quot;,
                        // Billing Address
                        billingAddress: {
                        // Street address, line 1
                        streetAddress: &quot;{{ $cart->billingAddress->line_one }}&quot;,
                        // Street address, line 2 (Ex: Unit, Apartment, etc.)
                        extendedAddress: &quot;{{ $cart->billingAddress->line_two }}&quot;,
                        // State
                        region: &quot;{{ $cart->billingAddress->state }}&quot;,
                        // City
                        locality: &quot;{{ $cart->billingAddress->city }}&quot;,
                        // Postal Code
                        postalCode: &quot;{{ $cart->billingAddress->postcode }}&quot;,
                        // Country Code
                        countryCodeAlpha2: 'US',
                    },
            }).then(() => {
                fetch(`/lunar-paypal/orders/${orderId}/capture`, {
                method: 'post',
            }).then((res) => {
                return res.json();
            }).then((orderData) => {
                // Two cases to handle:
                //   (1) Non-recoverable errors -> Show a failure message
                //   (2) Successful transaction -> Show confirmation or thank you
                // This example reads a v2/checkout/orders capture response, propagated from the server
                // You could use a different API or structure for your 'orderData'
                var errorDetail =
                    Array.isArray(orderData.details) && orderData.details[0];
                if (errorDetail) {
                    var msg = 'Sorry, your transaction could not be processed.';
                if (errorDetail.description)
                    msg += '\n\n' + errorDetail.description;
                if (orderData.debug_id) msg += ' (' + orderData.debug_id +
                ')';
                    return alert(msg); // Show a failure message
                }
                if (orderData.purchase_units[0].payments.captures[0].status ===
                'DECLINED') {
                    let responseCode = orderData.purchase_units[0].payments
                    .captures[0].processor_response.avs_code +
                    orderData.purchase_units[0].payments.captures[0]
                    .processor_response.cvv_code +
                    orderData.purchase_units[0].payments.captures[0]
                    .processor_response.response_code;
                    window.dispatchEvent(new CustomEvent('payment-failed'));
                    document.querySelector(
                    '#card-errors').innerHTML =
                    'Your card was declined. Please try again. (' +
                    responseCode + ')';
                    return;
            }
            // Capture Successful
            window.location.href = &quot;{{ $returnUrl }}&quot;
            });
            }).catch((err) => {
                console.log(err.details[0].description);
                window.dispatchEvent(new CustomEvent('payment-failed'));
                document.querySelector('#card-errors').innerHTML = err.details[0]
                .description;
                // alert('Payment could not be captured! ' + JSON.stringify(err))
                });
            });
            });
        } else {
            // Hides card fields if the merchant isn't eligible
            document.querySelector('#card-form').style = 'display: none';
        }
    "
    x-on:payment-processing.window="paymentProcessing = true"
    x-on:payment-failed.window="paymentFailed = true; paymentProcessing = false">
    {{-- PayPal Button --}}
    <div class="paypal-button-container"
         id="paypal-button-container"></div>

    {{-- Separator --}}
    <div class="relative flex py-5 items-center">
        <div class="flex-grow border-t border-gray-400"></div>
        <span class="flex-shrink mx-4 text-gray-400">or</span>
        <div class="flex-grow border-t border-gray-400"></div>
    </div>

    {{-- Card Form --}}
    <div class="card_container">
        <form id="card-form">
            <div>
                <div class="mt-6 grid grid-cols-4 gap-y-6 gap-x-4">
                    <div class="col-span-4">
                        <label for="card-number">
                            Card Number
                            <small class="text-xs text-red-500">*</small>
                        </label>
                        <div class="card_field"
                             id="card-number"
                             style="max-height: 20px;">
                        </div>
                    </div>

                    <div class="col-span-3">
                        <label for="expiration-date">
                            Expiration Date
                            <small class="text-xs text-red-500">*</small>
                        </label>
                        <div class="card_field"
                             id="expiration-date"></div>
                    </div>

                    <div>
                        <label for="cvv">
                            CVV
                            <small class="text-xs text-red-500">*</small>
                        </label>
                        <div class="card_field"
                             id="cvv"></div>
                    </div>
                </div>
                <div class="mt-4 text-red-600"
                     id="card-errors"
                     x-cloak
                     x-show="paymentFailed"></div>
            </div>
            <button class="font-bold my-4 px-5 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-500 disabled:opacity-50"
                    type="submit"
                    x-bind:disabled="paymentProcessing"
                    x-bind:class="{ 'opacity-50 cursor-not-allowed': paymentProcessing }">
                <div class="flex items-center space-x-2"
                     x-show="!paymentProcessing">
                    <svg class="w-4 h-4"
                         xmlns="http://www.w3.org/2000/svg"
                         fill="currentColor"
                         viewBox="0 0 24 24">
                        <path fill-rule="evenodd"
                              d="M6 9V7.25C6 3.845 8.503 1 12 1s6 2.845 6 6.25V9h.5a2.5 2.5 0 012.5 2.5v8a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 19.5v-8A2.5 2.5 0 015.5 9H6zm1.5-1.75C7.5 4.58 9.422 2.5 12 2.5c2.578 0 4.5 2.08 4.5 4.75V9h-9V7.25zm-3 4.25a1 1 0 011-1h13a1 1 0 011 1v8a1 1 0 01-1 1h-13a1 1 0 01-1-1v-8z">
                        </path>
                    </svg>
                    <span>Pay {{ $cart->total->formatted() }}</span>
                </div>
                <div class="flex items-center"
                     x-cloak
                     x-show="paymentProcessing">
                    <svg class="inline w-4 h-4 mr-3 text-white animate-spin"
                         role="status"
                         aria-hidden="true"
                         viewBox="0 0 100 101"
                         fill="none"
                         xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                              fill="#E5E7EB" />
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                              fill="currentColor" />
                    </svg>
                    Processing...
                </div>
            </button>
        </form>
    </div>
    <style>
        #card-form iframe {
            max-height: 2rem;
            border: 1px solid #ccc !important;
            padding: 0.5rem;
            border-radius: 0.25rem;
        }
    </style>
</div>
