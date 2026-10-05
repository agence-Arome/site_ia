/* Animate presentation only. Editorial values are never changed or fabricated. */
(function () {
 'use strict';
 const cards = document.querySelectorAll('.agency-stat');
 if (!cards.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
 const observer = new IntersectionObserver(function (entries) {
  entries.forEach(function (entry) {
   if (!entry.isIntersecting) return;
   entry.target.classList.add('is-revealed');
   observer.unobserve(entry.target);
  });
 }, { threshold: 0.2 });
 cards.forEach(function (card, index) {
  card.style.setProperty('--stat-delay', (index * 120) + 'ms');
  observer.observe(card);
 });
}());
