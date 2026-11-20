'use strict';
const menu = document.querySelector('.mobile-menu');
const sidebar = document.querySelector('#sidebar');
menu?.addEventListener('click', () => {
  const open = sidebar.classList.toggle('open');
  menu.setAttribute('aria-expanded', String(open));
});
document.addEventListener('click', (event) => {
  if (sidebar?.classList.contains('open') && !sidebar.contains(event.target) && !menu?.contains(event.target)) {
    sidebar.classList.remove('open'); menu?.setAttribute('aria-expanded', 'false');
  }
});
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') { sidebar?.classList.remove('open'); menu?.setAttribute('aria-expanded','false'); }
});
document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); });
});
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
document.querySelectorAll('input[data-preview]').forEach(input => {
  let previous;
  input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file || !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) return;
    if (previous) URL.revokeObjectURL(previous);
    previous = URL.createObjectURL(file);
    document.getElementById(input.dataset.preview).src = previous;
  });
});
const saleForm = document.querySelector('#sale-form');
if (saleForm) {
  const quantity = document.querySelector('#quantity');
  const submit = document.querySelector('#record-sale');
  const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
  const update = () => {
    const selected = saleForm.querySelector('input[name="product_id"]:checked');
    if (!selected) { submit.disabled = true; return; }
    const stock = Number(selected.dataset.stock);
    const units = Number(quantity.value);
    const cents = Math.round(Number(selected.dataset.price) * 100);
    quantity.max = stock;
    document.querySelector('#selected-name').textContent = selected.dataset.name;
    document.querySelector('#selected-image').src = selected.dataset.image;
    document.querySelector('#selected-price').textContent = currency.format(cents / 100) + ' each';
    document.querySelector('#stock-hint').textContent = stock + ' units available. Stock is checked again at checkout.';
    document.querySelector('#sale-total').textContent = currency.format(cents * (Number.isFinite(units) && units > 0 ? units : 0) / 100);
    submit.disabled = !Number.isInteger(units) || units < 1 || units > stock;
  };
  saleForm.querySelectorAll('input[name="product_id"]').forEach(radio => radio.addEventListener('change', update));
  quantity.addEventListener('input', update);
  document.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
    const max = Number(quantity.max) || 1000000;
    quantity.value = Math.max(1, Math.min(max, (Number(quantity.value) || 1) + Number(button.dataset.step)));
    update();
  }));
  saleForm.addEventListener('submit', () => { submit.disabled = true; submit.textContent = 'Recording sale…'; });
  window.addEventListener('pageshow', update);
  update();
}
