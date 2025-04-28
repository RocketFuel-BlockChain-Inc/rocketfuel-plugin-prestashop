/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    Udor Blessing

 *  @copyright 2010-2022 RocketFuel
 *  @license   LICENSE.txt
 */
(() => {

    let replaceButton;
    let defaultSubmitBtn;

    localStorage.removeItem('rocketfuel-presta-order-status');

    localStorage.removeItem('rocketfuel-presta-temporary-order');

    document.addEventListener("DOMContentLoaded", function () {
        //select check to know when the terms checkbox is checked and the rocketfuel radio is chosen
        let radio = document.querySelector("input[name='payment-option']");


        let butt = document.querySelector('.js-payment-confirmation .ps-shown-by-js') || document.querySelector('#payment-confirmation .ps-shown-by-js');

        defaultSubmitBtn = butt.querySelector('button.btn.btn-primary');

        const rkflSubmitButton = document.createElement('a');

        rkflSubmitButton.addEventListener('click', (e) => {
            pay(e);
        })

        rkflSubmitButton.id = 'rkfl-pay-btn';

        rkflSubmitButton.innerText = 'Pay with Rocketfuel';

        let classes = ['btn', 'btn-primary', 'rkfl-pay-btn'];

        classes.forEach(clas =>
            rkflSubmitButton.classList.add(clas))

        rkflSubmitButton.style.display = 'none';

        document.querySelector('#payment-confirmation button[type=submit]').parentElement.prepend(rkflSubmitButton);

        replaceButton = function (rep) {

            butt.innerHTML = rep;
        }
        function getDefaultPlaceOrder() {

            return document.querySelector('#payment-confirmation .ps-shown-by-js');
        }
        function switchSubmitBtn(activeRkfl = false) {
            const thePlaceOrderBtn = document.querySelector('#payment-confirmation .ps-shown-by-js button.btn.btn-primary');
            if (activeRkfl) {
                document.getElementById('rkfl-pay-btn').style.display = 'block';



                thePlaceOrderBtn.style.display = 'none';
                thePlaceOrderBtn.style.visibility = 'hidden';

            } else {



                document.getElementById('rkfl-pay-btn').style.display = 'none';
                thePlaceOrderBtn.style.visibility = 'initial'
            }

        }
        let checkbox = document.getElementById("conditions_to_approve\[terms-and-conditions\]");

        document.getElementById('checkout-payment-step').addEventListener('change', function (e) {
            let target = e.target;
            if (checkbox.checked) {
                if (target.getAttribute('data-module-name') === "Rocketfuel") {

                    console.log('switchSubmitBtn is called')
                    switchSubmitBtn(true);

                } else {
                    if (document.querySelector('input[data-module-name=Rocketfuel]').checked === true) {
                        console.log('switchSubmitBtn is called')

                        switchSubmitBtn(true);
                    } else {

                        switchSubmitBtn();
                    }


                }
            } else {
                switchSubmitBtn();
            }


        })
    })


    const RocketfuelPaymentEngine = {

        url: new URL(window.location.href),
        baseUrl: window.prestashop.urls.base_url,
        watchIframeShow: false,
        payloadResponse: {},
        isPlaceOrderLoading: false,
        payLoad: function getPayload() {
            let url = document.querySelector("input[name=payload_url]").value;
            let payload;
            //Get payload for rocketfuel cart
            const request = new XMLHttpRequest();
            request.open('GET', url, false);
            request.addEventListener("readystatechange", () => {
                if (request.readyState === 4 && request.status === 200) {
                    payload = JSON.parse(request.responseText)
                }
            });
            request.send();
            if (!payload?.uuid) {
                throw new Error(payload?.message || payload?.error || 'Unable to initiate payment, please try again');
            }
            console.log('Payload response:', payload);
            RocketfuelPaymentEngine.payloadResponse = payload;

        },

        //cart_id
        cartId: function () {
            return RocketfuelPaymentEngine.payloadResponse.cart_id;
        },
        orderId: function () {
            return RocketfuelPaymentEngine.payloadResponse.order;
        },

        merchantAuth: function () {
            return RocketfuelPaymentEngine.payloadResponse.merchant_auth;
        },


        getUUID: function () {
            return RocketfuelPaymentEngine.payloadResponse.uuid;
        },

        getEnvironment: function () {
            let environment = RocketfuelPaymentEngine.payloadResponse.environment;

            return environment || 'prod';
        },

        getUserData: function () {
            let user_data = RocketfuelPaymentEngine.payloadResponse.customer;

            if (!user_data) return false;

            return JSON.parse(user_data);
        },
        updateOrder: function (result) {
            try {

                let rest_url = document.querySelector("input[name=rest_url]").value;

                console.log("Response from callback :", result);

                console.log("orderId :", RocketfuelPaymentEngine.orderId());
                let result_status = parseInt(result.status);

                localStorage.setItem('rocketfuel-presta-order-status', result_status);
                localStorage.setItem('rocketfuel-presta-temporary-order', RocketfuelPaymentEngine.orderId())

            } catch (error) {

            }

        },

        startPayment: function (autoTriggerState = true) {

            if (!autoTriggerState) {
                document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
                this.watchIframeShow = true;
            }


            let checkIframe = setInterval(() => {

                if (RocketfuelPaymentEngine.rkfl.iframeInfo.iframe) {
                    RocketfuelPaymentEngine.rkfl.initPayment();
                    clearInterval(checkIframe);
                }

            }, 500);

        },
        triggerPlaceOrder: async function () {
            if (RocketfuelPaymentEngine.isPlaceOrderLoading) {
                return;
            }
            RocketfuelPaymentEngine.isPlaceOrderLoading = true;

            console.log('Trigger Place order is called');
            const handleSuccessUrl = `${RocketfuelPaymentEngine.baseUrl}modules/rocketfuel/api/success.php`;
            // Make a POST request to get the payload for Rocketfuel cart
            try {
                const response = await fetch(handleSuccessUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ cart_id: RocketfuelPaymentEngine.cartId() }), // Send cart_id in the request body
                });

                const result = await response.json();

                if (!result.error && result.data) {
                    RocketfuelPaymentEngine.isPlaceOrderLoading = false;
                    window.location.href = `${RocketfuelPaymentEngine.baseUrl}${result.data}`;

                    return;
                }

            } catch (error) {
                console.log({ error })
            } finally {
                RocketfuelPaymentEngine.isPlaceOrderLoading = false;
            }
            console.log("manual click triggered")

            document.querySelector('#payment-confirmation .ps-shown-by-js button.btn.btn-primary').click();

        },
        prepareProgressMessage: function () {

            //hide retrigger button
            document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Resume"; //revert trigger button message

            document.getElementById('rocketfuel_retrigger_payment').style.display = 'none';
            document.getElementById('rocketfuel_before_payment').style.display = 'block';

        },
        prepareRetrigger: function () {

            document.getElementById("rocket_fuel_payment_overlay_gateway").style.display = 'none';

        },
        windowListener: function () {
            let engine = this;
            window.addEventListener('message', (event) => {

                switch (event.data.type) {
                    case 'rocketfuel_iframe_close':
                        engine.prepareRetrigger();

                        // if (event.data.paymentCompleted === 1) {
                        engine.triggerPlaceOrder();
                        // }
                        break;
                    case 'rocketfuel_new_height':
                        if (engine.watchIframeShow) {
                            engine.prepareProgressMessage();
                            engine.watchIframeShow = false;

                        }
                        break;
                    default:
                        break;
                }

            })
        },
        setLocalStorage: function (key, value) {
            localStorage.setItem(key, value);
        },
        initRocketFuel: async function () {
            return new Promise(async (resolve, reject) => {
                if (!RocketFuel) {
                    location.reload();
                    reject();
                }
                let userData = RocketfuelPaymentEngine.getUserData();
                let merchantAuth = RocketfuelPaymentEngine.merchantAuth();

                let payload, response, rkflToken;

                RocketfuelPaymentEngine.rkfl = new RocketFuel({
                    environment: RocketfuelPaymentEngine.getEnvironment()
                });

                if (userData.firstname && userData.email && merchantAuth) {
                    payload = {
                        firstName: userData.firstname,
                        lastName: userData.lastname,
                        email: userData.email,
                        merchantAuth: merchantAuth,
                        kycType: 'null',
                        kycDetails: {
                            'DOB': "01-01-1990"
                        }
                    }


                    try {
                        if (userData.email !== localStorage.getItem('rkfl_email')) { //remove signon details when email is different
                            localStorage.removeItem('rkfl_token');
                            localStorage.removeItem('access');

                        }

                        rkflToken = localStorage.getItem('rkfl_token');

                        if (!rkflToken) {

                            response = await RocketfuelPaymentEngine.rkfl.rkflAutoSignUp(payload, RocketfuelPaymentEngine.getEnvironment());

                            RocketfuelPaymentEngine.setLocalStorage('rkfl_email', userData.email);

                            if (response) {

                                rkflToken = response.result?.rkflToken;

                            }

                        }

                        const rkflConfig = {
                            uuid: this.getUUID(),
                            callback: RocketfuelPaymentEngine.updateOrder,
                            environment: RocketfuelPaymentEngine.getEnvironment()
                        }
                        if (rkflToken) {
                            rkflConfig.token = rkflToken;
                        }



                        RocketfuelPaymentEngine.rkfl = new RocketFuel(rkflConfig);

                        resolve(true);

                    } catch (error) {

                        reject();

                    }

                }

                resolve('no auto');
            })

        },
        showError: function (message) {
            const rkflPayBtn = document.getElementById('rkfl-pay-btn');
            if (!rkflPayBtn) return;

            // Create a span element for the error message
            const errorSpan = document.createElement('span');
            errorSpan.innerText = message;
            errorSpan.style.color = 'red';
            errorSpan.style.marginLeft = '10px';
            errorSpan.id = 'rkfl-error-message';

            // Append the error message to the parent of #rkfl-pay-btn
            rkflPayBtn.parentElement.appendChild(errorSpan);

            // Remove the error message after 3 seconds
            setTimeout(() => {
                if (errorSpan && errorSpan.parentElement) {
                    errorSpan.parentElement.removeChild(errorSpan);
                }
            }, 3000);
        },
        init: async function () {

            let engine = this;
            console.log('Start initiating RKFL');

            try {
                engine.payLoad(); //Get necessary data once
                await engine.initRocketFuel();

            } catch (error) {

                engine.prepareRetrigger();
                this.showError(error.message);
                console.log('error from promise', error);
                return;


            }

            console.log('Done initiating RKFL');

            engine.windowListener();

            if (document.getElementById('rocketfuel_retrigger_payment_button')) {
                document.getElementById('rocketfuel_retrigger_payment_button').addEventListener('click', () => {
                    RocketfuelPaymentEngine.startPayment(false);
                });

            }

            engine.startPayment();

        }
    }

    function pay(e) {
        e.preventDefault();
        var x = document.getElementById("rocket_fuel_payment_overlay_gateway");
        x.style.display = "block";
        RocketfuelPaymentEngine.init();
    }
})()
console.info('[ VERSION 2.1.1 ]')