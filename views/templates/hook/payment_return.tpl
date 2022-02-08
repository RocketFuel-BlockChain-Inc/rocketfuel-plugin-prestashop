{*
 * RocketFuel - A Payment Module for PrestaShop 1.7
 *
 * HTML to be displayed in the order confirmation page
 *
 *}

<p>Complete payment using RocketFuel.</p>

<style>
    #Rocketfuel{
        display:block !important;
    }
    .rocketfuel_process_payment {
        text-align: center;
        display: flex;
        justify-content: center;
        align-content: center;
        align-items: baseline;
    }

    #rocketfuel_process_payment_button {
        background-color: #229633;
        border: #229633;
    }

    h3.indicate_text {
        margin: 0;
        font-size: 32px;
        margin-right: 10px;
        color: #fff;
    }

    .loader_rocket {
        border: 1px solid #000000;
        border-top: 1px solid #ffffff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 0.4s linear infinite;
    }

    .rocket_fuel_payment_overlay_wrapper {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        align-content: center;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    #rocket_fuel_payment_overlay_gateway {
        width: 100%;
        top: 0;
        right: 0;
        height: 100%;
        z-index: 100000 !important;
        
        display: flex;
    }

    #iframeWrapper {
        z-index: 100001 !important;
    }

    .rocket_fuel_payment_overlay_wrapper_gateway {
        width: 100%;
        display: flex;
        align-items: center;
        align-content: center;

    }

    #rocketfuel_retrigger_payment button {
        text-align: center;
        
        padding: 0px;
        border: none;
        width: 250px;
        padding-bottom: 2px;
        height: 48px;
        font-size: 17px;
        margin-top: 12px;
        border-radius: 3px;
        font-weight: 300;
        color: #fff;
        cursor: pointer;
    }




    #rocketfuel_retrigger_payment button:hover {
        outline: none;
        border: none;

        background-color: #e26f02 !important;
        border-color: #e26f02 !important;

    }

    .rocketfuel_exit_plan_wrapper {
        display: flex;
        text-align: center;
        justify-content: center;
        align-items: center;
        margin-top: 30px;
    }

    .rocketfuel_exit_plan_wrapper figure {
        width: 14px;
        height: 37px;
        margin: 0;
        right: 0px;
        position: relative;
        transition: right 700ms;
        display: inline-block;

    }

    .proceed-forward-rkfl:hover figure {
        right: -6px;
        transition: right 200ms;
    }

    /* .rocketfuel_exit_plan_wrapper:hover a {
        color: #ddd;
    } */

    .rocketfuel_exit_plan_wrapper a.completed-button-rkfl {
        border: 1px solid #ffffff4d;
        border-radius: 4px;
        padding: 2px 10px;

    }

    .rocketfuel_exit_plan_wrapper a.proceed-forward-rkfl {
        padding-right: 10px;
    }

    .rocketfuel_exit_plan_wrapper a {

        text-decoration: none;
        color: #fff !important;
        font-size: 12px;

    }

    .rocketfuel_exit_plan_wrapper a:focus {
        outline: none !important;
        text-decoration: none !important;
        background: transparent !important;
    }
    #iframeWrapper{
        position:fixed !important;
    }
</style>
{nocache}
  <div id="rocketfuel-iframe-container">
  <input type="hidden" name="rest_url" value="">
  <button id="rocketfuel_retrigger_payment_button">Rocketfuel</button>
  </div>
  {if ($debug)}
    {$payload}
  {/if}
{/nocache}
<script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js">

</script>

     <script>
            /**
             * Payment Engine object
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

                    let user_json = atob(user_data);

                    return JSON.parse(user_json);
                },
                updateOrder: function(result) {
                    try {

                        let rest_url = document.querySelector("input[name=rest_url]").value;

                        console.log("Response from callback :", result);

                        console.log("orderId :", RocketfuelPaymentEngine.orderId);

                        let status = "ps-on-hold"; //change status
                        let result_status = parseInt(result.status);
                        if (result_status == 101) {
                            status = "wc-partial-payment"; //change status
                        }
                        if (result_status == 1 || result.status == "completed") {
                            status = "admin_default"; //placeholder to get order status set by seller
                        }
                        if (result_status == -1) {
                            status = "wc-failed"; //change status
                        }
                        let fd = new FormData();
                        fd.append("order_id", RocketfuelPaymentEngine.orderId);
                        fd.append("status", status);
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

                    //hide processing payment
                    document.getElementById('rocketfuel_before_payment').style.cssText = "visibility:hidden;height:0;width:0";

                    //show retrigger button
                    document.getElementById('rocketfuel_retrigger_payment_button').disabled = false;
                    document.getElementById('rocketfuel_retrigger_payment').style.display = "block";
                    // this.startPayment();
                },
                prepareProgressMessage: function() {

                    //hide retrigger button
                    document.getElementById('rocketfuel_retrigger_payment_button').innerText = "Resume"; //revert trigger button message

                    document.getElementById('rocketfuel_retrigger_payment').style.display = "none";
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

                        if (userData.first_name && userData.email) {
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
                                // if (RocketfuelPaymentEngine.getEnvironment() !== 'prod') { //remove signon details when testing
                                    localStorage.removeItem('rkfl_token');
                                    localStorage.removeItem('access');
                                // }

                                rkflToken = localStorage.getItem('rkfl_token');

                                if (!rkflToken) {

                                    //response = await RocketfuelPaymentEngine.rkfl.rkflAutoSignUp(payload, RocketfuelPaymentEngine.getEnvironment());


                                    //if (response) {
                                     //   rkflToken = response.result?.rkflToken;
                                    //}

                                }

                                const rkflConfig = {
                                    uuid: this.getUUID(),
                                    callback: RocketfuelPaymentEngine.updateOrder,
                                    environment: RocketfuelPaymentEngine.getEnvironment()
                                }
                                if (rkflToken) {
                                    //rkflConfig.token = rkflToken;
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
                        console.log('error from promise', error)
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
        </script>