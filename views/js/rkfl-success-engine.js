// await fetch('/modules/rocketfuel/update-order.php',{
//     method:'POST',

// })

let fd = new FormData();
fd.append("order_id", localStorage.getItem('rocketfuel-presta-order-status'));

fd.append("status", localStorage.getItem('rocketfuel-presta-temporary-order'));

fetch('/modules/rocketfuel/update-order.php', {
    method: "POST",
    body: fd
}).then(res => res.json()).then(result => {
    console.log({result})

}).catch(e => {
    console.log({e})

})