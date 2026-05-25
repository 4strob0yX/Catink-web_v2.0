<?php require_once(__DIR__ . "/../views/helpers/urlhelper.php"); ?>
<!-- Modal de cookies -->
<div id="cookie-modal" class="cookie-modal">
  <div class="cookie-content">
    <h2>Uso de Cookies</h2>
    <p>Utilizamos cookies para publicidad, análisis y contenido embebido. 
       Puede aceptar o rechazar el uso de cookies según su preferencia.</p>
    <div class="cookie-buttons">
      <button onclick="aceptarCookies()">Aceptar</button>
      <button onclick="negarCookies()">Negar</button>
      <a href="<?= basePath() . '/cookies' ?>" class="leer-mas">Leer más</a>
    </div>
  </div>
</div>
<!-- Fin del contenido principal -->
</main>
<!-- Script local: reemplaza comportamientos de Bootstrap (colapso, tema, carrusel) -->
<script src="https://www.catink.com.mx/CSS/scripts.js"></script>
<script async src="https://platform.twitter.com/widgets.js"></script>
<script>
  const input = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearBtn');
  const searchBtn = document.getElementById('searchBtn');
  const basePath = '<?= basePath() ?>'; // Inyectado desde PHP
  
  function performSearch() {
    const q = input.value.trim();
    if (q.length >= 2) {
      // Redirige a la búsqueda con URL amigable
      window.location.href = basePath + `/buscar/${encodeURIComponent(q)}`;
    }
  }
  
  if (input) {
    // Buscar al presionar Enter
    input.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        performSearch();
      }
    });
  }
  
  // Botón limpiar
  if (clearBtn) {
    clearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      input.value = '';
      input.focus();
    });
  }
  
  // Botón buscar
  if (searchBtn) {
    searchBtn.addEventListener('click', function (e) {
      e.preventDefault();
      performSearch();
    });
  }
  const resultsBox = document.getElementById('searchResults');
    let timeout = null;
    
    if (input && resultsBox) {
      input.addEventListener('input', function () {
        const q = input.value.trim();
      
        clearTimeout(timeout);
      
        if (q.length < 2) {
          resultsBox.style.display = 'none';
          return;
        }
      
        timeout = setTimeout(() => {
          fetch(basePath + `/api/search.php?q=` + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
              resultsBox.innerHTML = '';
      
              if (data.length === 0) {
                resultsBox.style.display = 'none';
                return;
              }
      
              data.forEach(item => {
                const div = document.createElement('div');
                div.classList.add('search-item');
      
                div.innerHTML = `
                  <img src="${item.imagen}" alt="" class="img-search">
                  <span>${item.titulo}</span>
                `;
      
                div.addEventListener('click', () => {
                  window.location.href = item.url;
                });
      
                resultsBox.appendChild(div);
              });
      
              resultsBox.style.display = 'block';
            });
        }, 300); // debounce
      });
    }

    // Ocultar si haces click fuera
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.nav-search')) {
        resultsBox.style.display = 'none';
      }
    });
</script>
<script>
  document.addEventListener("DOMContentLoaded", function(){
      if(window.instgrm){
          window.instgrm.Embeds.process();
      }
  });
</script>
<script>
  document.addEventListener("DOMContentLoaded", function(){
      const modal = document.getElementById("cookie-modal");

      // Si ya tomó decisión, ocultar modal
      if(document.cookie.includes("cookies_decision=")){
          if(modal) modal.style.display = "none";
          if(document.cookie.includes("cookies_decision=aceptadas")) cargarCookies();
      } else {
          if(modal) modal.style.display = "flex";
      }
  });

  function aceptarCookies(){
      document.cookie = "cookies_decision=aceptadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
      cargarCookies();
      location.reload(); // para contenido embebido
  }

  function negarCookies(){
      document.cookie = "cookies_decision=negadas; path=/; max-age=" + (60*60*24*365);
      ocultarModal();
  }

  function ocultarModal(){
      const modal = document.getElementById("cookie-modal");
      if(modal) modal.style.display = "none";
  }

  function cargarCookies(){
      // GOOGLE ADSENSE solo si se aceptó
      if(document.cookie.includes("cookies_decision=aceptadas")){
          if(!document.getElementById("adsense-script")){
              var ads = document.createElement('script');
              ads.id="adsense-script";
              ads.src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js";
              ads.async=true;
              document.body.appendChild(ads);
          }
      }
  }
</script>
<script>
    document.querySelectorAll(".news-card, .card, .carousel-item").forEach(card => {
        card.addEventListener("click", function(e) {
            // Evitar conflicto si hacen click en links o tags
            if (e.target.closest("a")) return;

            const url = this.dataset.url;
            if (url) {
                window.location.href = url;
            }
        });
    });
</script>
<script>
    function updateClass() {
        const isMobile = window.innerWidth <= 768;
        // Cards
        document.querySelectorAll(".cardSpecial").forEach(card => {
            if (isMobile) {
                card.classList.add("card");
            } else {
                card.classList.remove("card");
            }
        });
        // Imágenes
        document.querySelectorAll(".imgCard").forEach(img => {
            if (isMobile) {
                img.classList.add("card-img-left");
                img.classList.remove("card-img-left-rounded");
                img.style.setProperty("max-height", "30px", "important");
            } else {
                img.classList.remove("card-img-left");
                img.classList.add("card-img-left-rounded");
                img.style.removeProperty("max-height");
            }
        });
        // Titulos
        document.querySelectorAll(".linkCard").forEach(title =>{
            if (isMobile) {
                title.classList.remove("title-limit-2");
            } else {
                title.classList.add("title-limit-2");
            }
        })
    }
    // Ejecutar al cargar
    updateClass();
    // Ejecutar al redimensionar
    window.addEventListener("resize", updateClass);
</script>
<!-- Pie de página: columnas, enlaces y barra inferior -->
<footer class="site-footer mt-5">
  <div class="container">
    <div class="row">
      <!-- Logo / descripción -->
      <div class="col">
        <img id="logo" src="" alt="CatInk Logo">
        <p class="footer-text">
          Noticias, anime, videojuegos y cultura digital.
        </p>
      </div>
      <!-- Páginas hermanas -->
      <div class="col">
        <h4 class="footer-title">Enlaces de interes</h4>
        <ul class="footer-links">
          <li><a href="<?= basePath() . '/sobre-nosotros' ?>"><i class="bi bi-building-fill"></i> Nosotros</a></li>
          <li><a href="<?= basePath() . '/terminos-condiciones' ?>"><i class="bi bi-file-earmark-text-fill"></i> Terminos y Condiciones</a></li>
          <li><a href="<?= basePath() . '/privacidad' ?>"><i class="bi bi-file-lock-fill"></i> Aviso de privacidad</a></li>
          <li><a href="<?= basePath() . '/solicitud' ?>"><i class="bi bi-briefcase-fill"></i> Unete a nuestro equipo</a></li>
          <li><a href="<?= basePath() . '/suscripcion' ?>" aria-label="Suscribete"><i class="bi bi-bookmark-star-fill"></i> Suscribete</a></li>
          <li><a href="<?= basePath() . '/contactanos' ?>"><i class="bi bi-envelope-fill"></i> Contactanos</a></li>
        </ul>
      </div>
      <!-- Redes sociales -->
      <div class="col">
        <h4 class="footer-title">Síguenos</h4>
        <div class="social-links">
          <a href="https://www.facebook.com/TheCatink?locale=es_LA" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://x.com/The_Catink/" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
          <a href="https://www.instagram.com/the.catink/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="https://www.youtube.com/@thecatink" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
          <a href="https://www.tiktok.com/@thecatink" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          <!--<a href="#" aria-label="Twitch"><i class="bi bi-twitch"></i></a>-->
        </div>
      </div>
    </div>
  </div>
  <!-- Derechos -->
  <div class="footer-bottom">
    <div class="container text-center">
      <small>
        © 2026 CatInk. Todos los derechos reservados.
      </small>
    </div>
  </div>
</footer>
</body>
</html>