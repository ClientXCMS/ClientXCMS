import * as bootstrap from 'bootstrap';

const toggle = document.querySelectorAll('#checkout-form .collapse');
const gatewayInputs = document.querySelectorAll('.gateway-input');
const buttons = document.querySelectorAll('#checkout-form [data-bs-toggle="collapse"]');
buttons.forEach(function (el) {
  el.addEventListener('click', function (e) {
      Array.from(toggle).filter(function(el) {
          return el.getAttribute('data-bs-target') !== e.target.getAttribute('data-bs-target')
      }).map(function(el) {
          new bootstrap.Collapse(el).hide();
      });
  })
})
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function () {
        const index = (location.href.split('#')[1] ?? '') === 'login' ? 0 : 1 ;
        console.log(toggle[index]);
        new bootstrap.Collapse(toggle[index]).show();
    }, 400);
});
gatewayInputs.forEach(function (el) {
  el.addEventListener('change', function (e) {
      e.target.parentNode.classList.add('gateway-selected');
      Array.from(gatewayInputs).filter(function(el) {
          return el !== e.target;
      }).map(function(el) {
          el.parentNode.classList.remove('gateway-selected');
      });
  })
})
document.querySelector('#btnCheckout').addEventListener('click', function (e) {
    e.preventDefault();
    document.querySelector('#checkoutForm').submit();
})
