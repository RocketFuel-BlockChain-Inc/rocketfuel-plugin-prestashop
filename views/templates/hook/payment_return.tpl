{*
 * RocketFuel - A Payment Module for PrestaShop 1.7
 *
 * HTML to be displayed in the order confirmation page
 *
 *}

<P>Complete payment using RocketFuel.</P>

<style>
  #rocketfuel-drag {
    width: 200px;
    height: 60px;
    margin-right: 105px;
    top: 10px;
    right: 10px;
    background: transparent;
    position: fixed;
    z-index: 10001 !important;
    cursor: pointer;
  }

  #rocketfuel-drag:active {
    cursor: grabbing;
  }

  #rocketfuel-drag:active + #rocketfuel-iframe {
    box-shadow: 0px 4px 7px rgba(0, 0, 0, 0.6);
    transform: scale(1.01);
  }

  #rocketfuel-dragheader {
    width: 100%;
    height: 100%;
  }

  #rocketfuel-iframe {
    border: 0px;
    margin: 0px;
    padding: 0px;
    overflow: hidden;
    width: 365px;
    height: 100px;
    top: 10px;
    right: 10px;
    box-shadow: 0px 4px 7px rgba(0, 0, 0, 0.3);
    border-radius: 2px;
    transition: height 0.5s ease, box-shadow 250ms ease-in-out 0s, transform 250ms cubic-bezier(0.25, 0.8, 0.25, 1) 0s;
    clip: auto !important;
    display: block !important;
    opacity: 1 !important;
    position: fixed !important;
    z-index: 10000 !important;
    transform: translate3d(0px, 0px, 0px);
  }
</style>
{nocache}
  <div id="rocketfuel-iframe-container">
    <div id="rocketfuel-drag">
      <div id="rocketfuel-dragheader"></div>
    </div>

  </div>
  {if ($debug)}
    {$payload}
  {/if}
{/nocache}
<script src="https://d3rpjm0wf8u2co.cloudfront.net/static/rkfl.js">

</script>
