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
       const RocketfuelPaymentEngine = {

        url: new URL(window.location.href),
        watchIframeShow: false,
        
        orderId: function() {
            return this.url.searchParams.get("id_order");
        },

        getUUID: function() {
            return this.url.searchParams.get("uuid");
        },
        getEnvironment: function() {
            let environment = this.url.searchParams.get("env");

            return environment || 'prod';
        },
        getUserData: function() {
            let user_data = this.url.searchParams.get("user_data");

            if (!user_data) return false;

            let user_json = atob(user_data.replace(' ', '+'));

            return JSON.parse(user_json);
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
        prepareRetrigger: function() {

            //show retrigger button
            document.getElementById('rocketfuel_retrigger_payment_button').disabled = false;
            document.getElementById('rocketfuel_retrigger_payment').style.display = 'block';
            document.getElementById('rocketfuel_before_payment').style.display = 'none';
            
            // this.startPayment();
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
                console.log(userData);
                let payload, response, rkflToken;
               
                RocketfuelPaymentEngine.rkfl = new RocketFuel({
                    environment: RocketfuelPaymentEngine.getEnvironment()
                });

                if (userData.first_name && userData.email && userData.merchant_auth) {
                    payload = {
                        firstName: userData.first_name,
                        lastName: userData.last_name,
                        email: userData.email,
                        merchantAuth: userData.merchant_auth,
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

    RocketfuelPaymentEngine.init();