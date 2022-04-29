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

document.addEventListener("DOMContentLoaded", function() {
    //select check to know when the terms checkbox is checked and the rocketfuel radio is chosen
    let radio = document.querySelector("input[name='payment-option']")
    let butt = document.querySelector('.js-payment-confirmation .ps-shown-by-js')
    let butt_inner_html = butt.innerHTML
    function replace_button(rep){
  
        butt.innerHTML = rep;
    }
    let checkbox = document.getElementById("conditions_to_approve\[terms-and-conditions\]");
    radio.addEventListener('change', function (e) {
        let target = e.target;
        if (this.getAttribute('data-module-name') === "Rocketfuel"){
            if (checkbox.checked) {
                replace_button(pay_button_enabled())
            }else {
                replace_button(pay_button())
            }
        }else{
            replace_button(butt_inner_html)
        }
    })

    checkbox.addEventListener('change', function (){
        if (this.checked){
            if (document.querySelector("[data-module-name='Rocketfuel']").checked){
                console.log('inside')
                return replace_button(pay_button_enabled())
            }/*else{
                console.log('outside')
                return replace_button(butt_inner_html)
            }*/
        }else{
            return replace_button(butt_inner_html)
        }
    })
})

function pay_button(){
    return "<a href='#' class='btn btn-secondary disabled-link'>Pay with rocketfuel </a>"
}

function pay_button_enabled(){
    return "<a href='#' class='btn btn-primary' onclick='return pay()'>Pay with rocketfuel </a>"
}
const RocketfuelPaymentEngine = {

    url: new URL(window.location.href),
    watchIframeShow: false,

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
        return payload;
    },

    //cart_id
    orderId: function() {
        return this.payLoad().order;
    },

    merchantAuth: function() {
        return this.payLoad().merchant_auth;
    },


    getUUID: function() {
        return this.payLoad().uuid;
    },

    getEnvironment: function() {
        let environment = this.payLoad().environment;

        return environment || 'prod';
    },

    getUserData: function() {
        let user_data = this.payLoad().customer;

        if (!user_data) return false;

        //let user_json = atob(user_data.replace(' ', '+'));

        return JSON.parse(user_data);
    },
    updateOrder: function(result) {
        try {

            let rest_url = document.querySelector("input[name=rest_url]").value;

            console.log("Response from callback :", result);

            console.log("orderId :", RocketfuelPaymentEngine.orderId);


            let result_status = parseInt(result.status);

            let fd = new FormData();
            fd.append("order_id", RocketfuelPaymentEngine.orderId);
            fd.append("status", result_status);
            fetch(rest_url, {
                method: "POST",
                body: fd
            }).then(res => res.json()).then(result => {
                console.log(result)

            }).catch(e => {
                console.log(e)

            })
        } catch (error) {

        }
        //RocketfuelPaymentEngine.showFinalOrderDetails();

    },
    showFinalOrderDetails: () => {
        document.getElementById('rocket_fuel_payment_overlay_gateway').remove();
    },
    startPayment: function(autoTriggerState = true) {

        if (!autoTriggerState) {
            document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Preparing Payment window...";
            this.watchIframeShow = true;
        }

        document.getElementById('rocketfuel_retrigger_payment_button').disabled = true;

        let checkIframe = setInterval(() => {

            if (RocketfuelPaymentEngine.rkfl.iframeInfo.iframe) {
                RocketfuelPaymentEngine.rkfl.initPayment();
                clearInterval(checkIframe);
            }

        }, 500);

    },
    triggerPlaceOrder: function () {
        // document.getElementById('place_order').style.display = 'inherit';
        console.log('Trigger is calling');

        // $('form.checkout').trigger('submit');

        // document.getElementById('place_order').style.display = 'none';

     
    },
    prepareProgressMessage: function() {

        //hide retrigger button
        document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Resume"; //revert trigger button message

        document.getElementById('rocketfuel_retrigger_payment').style.display = 'none';
        document.getElementById('rocketfuel_before_payment').style.display = 'block';

    },

    windowListener: function() {
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
    setLocalStorage: function(key,value){
        localStorage.setItem(key,value);
    },
    initRocketFuel: async function() {
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

                    console.log(rkflConfig);

                    RocketfuelPaymentEngine.rkfl = new RocketFuel(rkflConfig);

                    resolve(true);

                } catch (error) {

                    reject();

                }

            }

            resolve('no auto');
        })

    },

    init: async function() {

        let engine = this;
        console.log('Start initiating RKFL');

        try {

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

function pay(){
    event.preventDefault();
    var x = document.getElementById("rocket_fuel_payment_overlay_gateway");
    x.style.display = "block";
    RocketfuelPaymentEngine.init();
}