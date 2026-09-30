(function () {
  'use strict';
  document.querySelectorAll('.ewp-form').forEach(function (form) {
    var select = form.querySelector('[name="service"]');
    function update() {
      if (!select) return;
      var option = select.options[select.selectedIndex];
      var label = form.querySelector('[data-question-label]');
      label.textContent = (option.dataset.question || 'Précisez votre besoin pour la prestation choisie.') + ' *';
    }
    if (select) select.addEventListener('change', update);
    update();
    var timer;
    function refresh() {
      fetch(form.dataset.tokenUrl, {credentials: 'same-origin', cache: 'no-store'})
        .then(function (r) { if (!r.ok) throw new Error('token'); return r.json(); })
        .then(function (data) { if (typeof data.token === 'string') form.elements.token.value = data.token; })
        .catch(function () { /* Initial server token remains a no-JS fallback. */ });
    }
    refresh();
    timer = window.setInterval(refresh, 50 * 60 * 1000);
    form.addEventListener('submit', function () {
      window.clearInterval(timer);
      form.querySelector('[type="submit"]').disabled = true;
      form.querySelector('[role="status"]').textContent = 'Enregistrement en cours…';
    });
    window.addEventListener('pageshow', function () { form.querySelector('[type="submit"]').disabled = false; });
  });
})();
