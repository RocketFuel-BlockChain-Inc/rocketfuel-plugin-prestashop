
(() => {

    const params = new URLSearchParams(location.search);

    const order_id = params.get('id_order');
    const cart_id = params.get('id_cart');
    const rest_url = document.querySelector('input[name=rest_url]').value;
    let fd = new FormData();

    fd.append("order_id", order_id);
    fd.append("cart_id", cart_id);

    fd.append("status", localStorage.getItem('rocketfuel-presta-order-status'));

    fd.append("temp_order_id", localStorage.getItem('rocketfuel-presta-temporary-order'));

    fetch(rest_url, {
        method: "POST",
        body: fd
    }).then(res => res.json()).then(result => {

        console.log({ result })

    }).catch(e => {

        console.log({ e })

    })

})()