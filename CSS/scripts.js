/*
  Archivo: scripts.js
  Propósito: funcionalidades JavaScript nativas que reemplazan comportamientos de Bootstrap.
  Contiene:
    - toggle de colapso para el menú (data-bs-toggle="collapse")
    - toggle de tema (lee/guarda en localStorage y aplica data-bs-theme)
    - carrusel mínimo con evento compatible 'slide.bs.carousel'
    - animación de progreso de indicadores (círculos SVG)
*/
document.addEventListener('DOMContentLoaded', function() {
  // Toggle de colapso: busca botones con data-bs-toggle="collapse" y alterna la clase .show
  document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const targetSelector = btn.getAttribute('data-bs-target');
      if (!targetSelector) return;
      const target = document.querySelector(targetSelector);
      if (!target) return;
      target.classList.toggle('show');
    });
  });

  // Toggle de tema: botones con id 'themeToggle'
  const themeBtns = document.querySelectorAll('#themeToggle');
  function applyTheme(theme) {
    // Aplica el atributo en el elemento <html> para que CSS use las variables
    document.documentElement.setAttribute('data-bs-theme', theme);
    // Actualiza texto de los botones (solo visual) — no usar emojis en comentarios
    themeBtns.forEach(b => b.textContent = theme === 'dark' ? '☀️' : '🌙');
  }
  themeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      localStorage.setItem('theme', next);
    });
  });
  const saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);

  // Carrusel mínimo: mantiene las slides en DOM y alterna la clase .active
  const carousel = document.getElementById('carouselExampleCaptions');
  if (carousel) {
    const items = Array.from(carousel.querySelectorAll('.carousel-item'));
    let current = items.findIndex(i => i.classList.contains('active'));
    if (current < 0) current = 0;
    const interval = parseInt(carousel.getAttribute('data-bs-interval')) || 10000;
    let timer = null;

    // Muestra la slide indicada y dispara un evento compatible con Bootstrap
    function showSlide(index) {
      if (index < 0) index = items.length - 1;
      if (index >= items.length) index = 0;
      items.forEach((it, idx) => {
        it.classList.toggle('active', idx === index);
      });
      current = index;
      const ev = new CustomEvent('slide.bs.carousel', { detail: { to: index } });
      carousel.dispatchEvent(ev);
    }

    function startAuto() {
      stopAuto();
      timer = setInterval(() => showSlide(current + 1), interval);
    }
    function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }

    // Manejo de indicadores personalizados: al hacer click se muestra la slide correspondiente
    document.querySelectorAll('.custom-indicators button').forEach((btn, idx) => {
      btn.addEventListener('click', () => {
        showSlide(idx);
        startAuto();
      });
    });

    // Inicia carrusel automático
    showSlide(current);
    startAuto();
  }

  // Animación de progreso de indicadores: dibuja stroke-dashoffset en los círculos SVG
  const indicators = document.querySelectorAll('.indicator-avatar circle');
  const duration = 10000;
  function startProgress(index) {
    indicators.forEach(circle => {
      circle.style.transition = 'none';
      circle.style.strokeDashoffset = '100';
    });
    if (!indicators[index]) return;
    void indicators[index].offsetWidth; // forzar reflow para reiniciar transición
    indicators[index].style.transition = `stroke-dashoffset ${duration}ms linear`;
    indicators[index].style.strokeDashoffset = '0';
  }

  // Conecta el evento del carrusel con la animación de progreso
  if (carousel) {
    carousel.addEventListener('slide.bs.carousel', (e) => {
      const to = (e && e.detail && typeof e.detail.to === 'number') ? e.detail.to : 0;
      startProgress(to);
    });
    // Inicio inicial del progreso
    startProgress(0);
  }
});
const carousel = document.querySelector(".video-carousel");
document.querySelector(".next-slide").addEventListener("click", () => {
    carousel.scrollBy({ left: carousel.offsetWidth, behavior: "smooth" });
});
document.querySelector(".prev-slide").addEventListener("click", () => {
    carousel.scrollBy({ left: -carousel.offsetWidth, behavior: "smooth" });
});