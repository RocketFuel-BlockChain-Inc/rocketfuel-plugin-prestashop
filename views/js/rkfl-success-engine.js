
(() => {

    const params = new URLSearchParams(location.search);

    const order_id = params.get('order_id');
    const cart_id = params.get('cart_id');

    let fd = new FormData();

    fd.append("order_id", order_id);
    fd.append("cart_id", cart_id);

    fd.append("status", localStorage.getItem('rocketfuel-presta-order-status'));

    fd.append("temp_order_id", localStorage.getItem('rocketfuel-presta-temporary-order'));

    fetch('/modules/rocketfuel/update-order.php', {
        method: "POST",
        body: fd
    }).then(res => res.json()).then(result => {

        console.log({ result })

    }).catch(e => {

        console.log({ e })

    })

})()