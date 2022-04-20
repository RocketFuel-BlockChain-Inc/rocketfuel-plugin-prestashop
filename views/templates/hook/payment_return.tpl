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
<script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js">

</script>
<p>Complete payment using RocketFuel.</p>

   <style>
   #Rocketfuel{
        display:block !important;
    }
     #iframeWrapper{
        position:fixed !important;
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
                position: fixed;
                background: rgb(0 0 0 / 97%);
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
                justify-content: center;
            }

            #rocketfuel_retrigger_payment button {
                text-align: center;
                background: #f0833c !important;
                padding: 0px;
                border: none;
                width: 300px;
                padding-bottom: 2px;
                height: 48px;
                font-size: 17px;
                margin-top: 12px;
                border-radius: 3px;
                font-weight: 300;
                color: #fff;
                cursor: pointer;
            }


            #rocketfuel_retrigger_payment {
                display: none;
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
        </style>
{nocache}
 
  <input type="hidden" name="rest_url" value="/modules/rocketfuel/update-order.php">
 
  <div id="rocket_fuel_payment_overlay_gateway">
            <div class="rocket_fuel_payment_overlay_wrapper_gateway">
                <div id="rocketfuel_before_payment">
                    <div class="rocketfuel_process_payment">
                        <h3 class="indicate_text">Processing Payment</h3> <span>
                            <div class="loader_rocket"></div>
                        </span>
                    </div>
                </div>
                <div id="rocketfuel_retrigger_payment">
                    <button id="rocketfuel_retrigger_payment_button">
                        Rocketfuel
                    </button>
                    <div class="rocketfuel_exit_plan_wrapper">


                        <a onClick="RocketfuelPaymentEngine.showFinalOrderDetails()" class="proceed-forward-rkfl" style="display: flex;align-items: center;opacity:0.4">Go back
                            &nbsp; <figure style="display: flex;align-content: center;align-items: center;">
                           <img src="/modules/rocketfuel/views/img/forward.svg" style="height:14px" alt="">
                        </figure>
                        </a>
                       

                    </div>
                </div>
            </div>
        </div>
   
 {* {if ($debug)}
  *  {$payload}
  *{/if} 
  *}
{/nocache}

 <script src="/modules/rocketfuel/views/js/rkfl-engine.js">
      
        </script>
