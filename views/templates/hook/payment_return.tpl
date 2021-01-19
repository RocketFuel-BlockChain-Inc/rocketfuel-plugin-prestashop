{*
 * RocketFuel - A Payment Module for PrestaShop 1.7
 *
 * HTML to be displayed in the order confirmation page
 *
 *}

<P>You need pay an order using RocketFuel.</P>

<style>
  #rocketfuel-drag {
    width: 200px;
    height: 60px;
    margin-right: 105px;
    top: 10px;
    right: 10px;
    background: transparent;
    position: absolute;
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

<iframe id="rocketfuel-iframe" src="https://iframe.rocketdemo.net"></iframe>


<script>
  const iframe = document.getElementById('rocketfuel-iframe');
  iframe.onload = function() {
    // Отправляем корзину
    iframe.contentWindow.postMessage({
      type: 'rocketfuel_send_cart',
      data: {$payload|@json_encode nofilter},
    }, '*');
  };

  // Это пример прослушивания сообщений
  window.addEventListener('message', (event) => {
    // Это сообщения и для айфрейма и для расширения
    if (event.data.type === 'rocketfuel_result_ok') {
      // TODO just flag about success payment
    }
    // Это сообщение для айфрейма
    if (event.data.type === 'rocketfuel_change_height') {
      iframe.style.height = event.data.data;
    }
    // Это сообщение для айфрейма
    if (event.data.type === 'rocketfuel_iframe_close') {
      // TODO destroy iframe
      iframe.remove();
      document.getElementById("rocketfuel-drag").remove();
    }
    // Это сообщение для айфрейма (расширение делает такое же сообщение, но через инжектированный скрипт)
    if (event.data.type === 'rocketfuel_get_cart') {
      iframe.contentWindow.postMessage({
        type: 'rocketfuel_send_cart',
        data: {$payload|@json_encode nofilter},
      }, '*');
    }
  });

  // Это пример отправки корзины в расширение (при помощи инжетированного скрипта)
  setTimeout(function() {
    const data = {$payload|@json_encode nofilter};

    rocketfuel.setCartData(data);
  }, 1000);


  // Make the iframe draggable:
  dragElement(document.getElementById("rocketfuel-drag"), iframe);

  function dragElement(elmnt, iframe) {
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    if (document.getElementById(elmnt.id + "header")) {
      // if present, the header is where you move the DIV from:
      document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
    } else {
      // otherwise, move the DIV from anywhere inside the DIV:
      elmnt.onmousedown = dragMouseDown;
    }

    function dragMouseDown(e) {
      e = e || window.event;
      e.preventDefault();
      // get the mouse cursor position at startup:
      pos3 = e.clientX;
      pos4 = e.clientY;
      document.onmouseup = closeDragElement;
      // call a function whenever the cursor moves:
      document.onmousemove = elementDrag;
    }

    function elementDrag(e) {
      e = e || window.event;
      e.preventDefault();
      // calculate the new cursor position:
      pos1 = pos3 - e.clientX;
      pos2 = pos4 - e.clientY;
      pos3 = e.clientX;
      pos4 = e.clientY;
      // set the element's new position:
      elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
      elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
      iframe.style.top = (iframe.offsetTop - pos2) + "px";
      iframe.style.left = (iframe.offsetLeft - pos1) + "px";
    }

    function closeDragElement() {
      // stop moving when mouse button is released:
      document.onmouseup = null;
      document.onmousemove = null;
    }
  }
</script>
