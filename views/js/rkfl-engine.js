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
console.log('here')

    let replaceButton;
    let defaultSubmitBtn;
    
    localStorage.removeItem('rocketfuel-presta-order-status');
    
    localStorage.removeItem('rocketfuel-presta-temporary-order');

    document.addEventListener("DOMContentLoaded", function () {
        //select check to know when the terms checkbox is checked and the rocketfuel radio is chosen
        let radio = document.querySelector("input[name='payment-option']");


        let butt = document.querySelector('.js-payment-confirmation .ps-shown-by-js');

        defaultSubmitBtn = document.querySelector('.js-payment-confirmation .ps-shown-by-js').querySelector('button.btn.btn-primary');

        const rkflSubmitButton = document.createElement('a');

        rkflSubmitButton.addEventListener('click', (e) => {
            pay(e);
        })

        rkflSubmitButton.id = 'rkfl-pay-btn';

        rkflSubmitButton.innerText = 'Pay with Rocketfuel';

        let classes = ['btn', 'btn-primary', 'rkfl-pay-btn'];
        console.log({ classes })
        classes.forEach(clas =>
            rkflSubmitButton.classList.add(clas))

        rkflSubmitButton.style.display = 'none';

        document.querySelector('#payment-confirmation button[type=submit]').parentElement.prepend(rkflSubmitButton);

        replaceButton = function (rep) {

            butt.innerHTML = rep;
        }
        function switchSubmitBtn(activeRkfl = false) {

            if (activeRkfl) {
                document.getElementById('rkfl-pay-btn').style.display = 'block';
                console.log({ activeRkfl }, "activeRkfl", document.querySelector('.js-payment-confirmation .ps-shown-by-js button.btn.btn-primary'));



                document.querySelector('.js-payment-confirmation .ps-shown-by-js button.btn.btn-primary').style.display = 'none';
                document.querySelector('.js-payment-confirmation .ps-shown-by-js button.btn.btn-primary').style.visibility = 'hidden';
            
            } else {

                

                document.getElementById('rkfl-pay-btn').style.display = 'none';
                document.querySelector('.js-payment-confirmation .ps-shown-by-js button.btn.btn-primary').style.visibility = 'initial'
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

                        console.log('switchSubmitBtn is called')

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
        watchIframeShow: false,
        payloadResponse: {},

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
            RocketfuelPaymentEngine.payloadResponse = payload;

        },

        //cart_id
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

            //let user_json = atob(user_data.replace(' ', '+'));

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



                // let fd = new FormData();
                // fd.append("order_id", RocketfuelPaymentEngine.orderId());
                // fd.append("status", result_status);
                // fetch(rest_url, {
                //     method: "POST",
                //     body: fd
                // }).then(res => res.json()).then(result => {
                //     console.log(result)

                // }).catch(e => {
                //     console.log(e)

                // })
            } catch (error) {

            }

        },

        startPayment: function (autoTriggerState = true) {

            if (!autoTriggerState) {
                document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
                this.watchIframeShow = true;
            }

            // document.getElementById('rocketfuel_retrigger_payment_button').disabled = true;

            let checkIframe = setInterval(() => {

                if (RocketfuelPaymentEngine.rkfl.iframeInfo.iframe) {
                    RocketfuelPaymentEngine.rkfl.initPayment();
                    clearInterval(checkIframe);
                }

            }, 500);

        },
        triggerPlaceOrder: function () {
            // document.getElementById('place_order').style.display = 'inherit';
            console.log('Trigger Place order is called');

            // replaceButton(defaultSubmitBtn);
            // switchSubmitBtn();
            // document.querySelector('.js-payment-confirmation .ps-shown-by-js').click();
            document.querySelector('.js-payment-confirmation .ps-shown-by-js button.btn.btn-primary').click();

            // $('form.checkout').trigger('submit');

            // document.getElementById('place_order').style.display = 'none';


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

                        if (event.data.paymentCompleted === 1) {
                            engine.triggerPlaceOrder();
                        }
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
                console.log(userData);
                let payload, response, rkflToken;

                RocketfuelPaymentEngine.rkfl = new RocketFuel({
                    environment: RocketfuelPaymentEngine.getEnvironment()
                });

                if (userData.firstname && userData.email && merchantAuth) {
                    console.log('in')
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

                        console.log({ rkflConfig });

                        RocketfuelPaymentEngine.rkfl = new RocketFuel(rkflConfig);

                        resolve(true);

                    } catch (error) {

                        reject();

                    }

                }

                resolve('no auto');
            })

        },

        init: async function () {

            let engine = this;
            console.log('Start initiating RKFL');

            try {
                engine.payLoad(); //Get necessary data once
                await engine.initRocketFuel();

            } catch (error) {

                console.log('error from promise', error);

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