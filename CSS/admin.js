/*
  Archivo: admin.js
  Propósito: Lógica exclusiva del panel de administración
  Incluye:
  - Previsualización de imágenes
  - Cropper.js (imagen principal, galería y editor Quill)
  - Editor Quill
  - Manejo de estado / programación de publicaciones
  - Validaciones defensivas (no rompe si un elemento no existe)
*/
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
  // Toggle Sidebar (Mobile)
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
   const sidebarBackdrop = document.getElementById('sidebarBackdrop');
  const setSidebarState = (isOpen) => {
    sidebar.classList.toggle('active', isOpen);
    sidebarBackdrop?.classList.toggle('active', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
  };
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', () => {
      setSidebarState(!sidebar.classList.contains('active'));
    });
    sidebarBackdrop?.addEventListener('click', () => setSidebarState(false));
    // Close sidebar when clicking outside (optional but good for UX)
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 768 && 
            sidebar.classList.contains('active') && 
            !sidebar.contains(e.target) && 
            e.target !== sidebarToggle &&
            !sidebarToggle.contains(e.target)) {
            setSidebarState(false);
        }
    });
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) {
        setSidebarState(false);
      }
    });
  }
/* ===============================
   PREVISUALIZACIÓN DE IMÁGENES
================================ */
(() => {
  const input = document.getElementById('imagenCorreo');
  const preview = document.getElementById('preview');
  if (!input || !preview) return;
  let archivosTemporales;
  try {
    archivosTemporales = new DataTransfer();
  } catch {
    archivosTemporales = { files: [], items: { add(){} } };
  }
  input.addEventListener('change', () => {
    Array.from(input.files).forEach(file => {
      // evitar duplicados
      for (let i = 0; i < archivosTemporales.files.length; i++) {
        if (archivosTemporales.files[i].name === file.name) return;
      }
      archivosTemporales.items.add(file);
      const reader = new FileReader();
      reader.onload = e => {
        const div = document.createElement('div');
        div.classList.add('preview-item');
        div.innerHTML = `<img src="${e.target.result}"><span>${file.name}</span>`;
        preview.appendChild(div);
      };
      reader.readAsDataURL(file);
    });
    input.files = archivosTemporales.files;
    input.name = "imagenCorreo";
  });
})();
/* ===============================
   PREVISUALIZACIÓN DE VIDEO
================================ */
(() => {
  const videoInput = document.getElementById('video_url');
  const videoPreview = document.getElementById('videoPreview');
  if (!videoInput || !videoPreview) return;
  videoInput.addEventListener('input', () => {
    const url = videoInput.value.trim();
    videoPreview.innerHTML = '';
    if (!url) return;
    // YouTube
    if (url.includes('youtube.com') || url.includes('youtu.be')) {
      const id = url.includes('youtu.be')
        ? url.split('/').pop()
        : new URL(url).searchParams.get('v');
      if (id) {
        videoPreview.innerHTML =
          `<iframe src="https://www.youtube.com/embed/${id}" allowfullscreen></iframe>`;
      }
      return;
    }
    // Vimeo
    if (url.includes('vimeo.com')) {
      const id = url.split('/').pop();
      videoPreview.innerHTML =
        `<iframe src="https://player.vimeo.com/video/${id}" allowfullscreen></iframe>`;
      return;
    }
    // Video directo
    if (url.match(/\.(mp4|webm|ogg)$/)) {
      videoPreview.innerHTML =
        `<video controls><source src="${url}"></video>`;
      return;
    }
    videoPreview.innerHTML = `<p class="error">No se pudo previsualizar el video</p>`;
  });
})();
/* ===============================
   CROP IMAGEN PRINCIPAL (GALERÍA)
================================ */
(() => {
  // Configuración de los 3 croppers independientes
  const cropConfigs = [
    {
      num: 1,
      name: 'Original',
      ratio: NaN, // Sin ratio fijo
      inputId: 'imageInputCrop1',
      imgId: 'cropperImage1',
      previewId: 'preview1',
      confirmBtnSelector: '[data-crop="1"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="1"].crop-btn-reset'
    },
    {
      num: 2,
      name: 'Banner',
      ratio: 21 / 6,
      inputId: 'imageInputCrop2',
      imgId: 'cropperImage2',
      previewId: 'preview2',
      confirmBtnSelector: '[data-crop="2"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="2"].crop-btn-reset'
    },
    {
      num: 3,
      name: 'Miniatura',
      ratio: 16 / 9,
      inputId: 'imageInputCrop3',
      imgId: 'cropperImage3',
      previewId: 'preview3',
      confirmBtnSelector: '[data-crop="3"].crop-btn-confirm',
      resetBtnSelector: '[data-crop="3"].crop-btn-reset'
    }
  ];

  // Objeto para almacenar los croppers
  const croppers = {};
  
  // Detectar si estamos en editar o crear
  const isEditForm = !!document.getElementById('formEdicion');
  const isCreateForm = !!document.getElementById('formPublicacion');

  // Inicializar cada cropper
  cropConfigs.forEach(config => {
    const input = document.getElementById(config.inputId);
    const img = document.getElementById(config.imgId);
    const preview = document.getElementById(config.previewId);
    const confirmBtn = document.querySelector(config.confirmBtnSelector);
    const resetBtn = document.querySelector(config.resetBtnSelector);
    const hiddenInput = document.getElementById(`crop${config.num}`);
    const cropperContainer = img?.closest('.cropper-container');
    const cropActions = confirmBtn?.closest('.crop-actions');
    const imageSection = input?.closest('.crop-image-section');

    if (!input || !img || !preview) return;

    // En editar, ocultar cropper-container por defecto
    if (isEditForm && cropperContainer) {
      cropperContainer.style.display = 'none';
    }

    // Cargar imagen
    input.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
        img.src = reader.result;
        
        // Mostrar cropper-container
        if (cropperContainer) cropperContainer.style.display = 'block';
        
        // Destruir cropper anterior si existe
        if (croppers[config.num]) {
          croppers[config.num].destroy();
        }

        // Crear nuevo cropper
        croppers[config.num] = new Cropper(img, {
          viewMode: 1,
          autoCropArea: 1,
          aspectRatio: config.ratio,
          cropBoxResizable: false,
          dragMode: 'move',
          responsive: true,
          guides: true,
          background: false
        });
      };
      reader.readAsDataURL(file);
    });

    // Confirmar recorte
    confirmBtn?.addEventListener('click', () => {
      const cropper = croppers[config.num];
      if (!cropper) {
        alert(`Por favor, carga una imagen primero para ${config.name}`);
        return;
      }

      const canvas = cropper.getCroppedCanvas({
        imageSmoothingQuality: 'high'
      });
      const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

      // Guardar en input hidden
      if (hiddenInput) {
        hiddenInput.value = dataUrl;
      }

      // Mostrar preview
      preview.innerHTML = `<img src="${dataUrl}" style="width: auto; max-height:150px; object-fit:contain;">`;
      
      // Ocultar solo el cropper-container (pero mantener visible los botones)
      if (cropperContainer) cropperContainer.style.display = 'none';
    });

    // Reset
    resetBtn?.addEventListener('click', () => {
      input.value = '';
      preview.innerHTML = '';
      if (hiddenInput) {
        hiddenInput.value = '';
      }
      if (croppers[config.num]) {
        croppers[config.num].destroy();
        croppers[config.num] = null;
        img.src = '';
      }
      
      // Mostrar cropper-container y crop-actions nuevamente
      if (cropperContainer) cropperContainer.style.display = 'block';
    });
  });
})();
/* ===============================
   EDITOR QUILL
================================ */
const editorElement = document.getElementById('editor');
let quill = null;

if (editorElement) {
  // ====== REGISTROS NECESARIOS ======
  // Importaciones de Quill
  const Font = Quill.import('formats/font');
  const Size = Quill.import('formats/size');
  const ImageResize = Quill.import('modules/imageResize');
  const Parchment = Quill.import('parchment');
  // Declaraciones
  Font.whitelist = ['arial', 'times', 'roboto', 'courier'];
  Quill.register(Font, true);
  Size.whitelist = ['small', false, 'large', 'huge'];
  Quill.register(Size, true);
  Quill.register(ImageResize, true);
  const LineHeightStyle = new Parchment.Attributor.Style(
    'lineheight',
    'line-height',
    {
      scope: Parchment.Scope.BLOCK,
      whitelist: ['0', '0.85', '1', '1.5', '2', '2.5', '3']
    }
  );
  Quill.register(LineHeightStyle, true);

  // ===== Define social embed blot =====
const BlockEmbed = Quill.import('blots/block/embed');
class SocialEmbedBlot extends BlockEmbed {
  static create(value) {
    const node = super.create();
    node.setAttribute('data-url', value);
    node.classList.add('social-embed');
    // insert preview HTML
    node.innerHTML = renderizarEmbedSocialJS(value);
    return node;
  }
  static value(node) {
    return node.getAttribute('data-url');
  }
}
SocialEmbedBlot.blotName = 'socialEmbed';
SocialEmbedBlot.tagName = 'div';
SocialEmbedBlot.className = 'social-embed';
Quill.register(SocialEmbedBlot);
}

// simple JS mirror of PHP helper, used for preview inside editor
function renderizarEmbedSocialJS(url) {
  // twitter/x
  if (/twitter\.com|x\.com/i.test(url)) {
    return `
      <blockquote class="twitter-tweet">
        <a href="${url}"></a>
      </blockquote>
      <script async src="https://platform.twitter.com/widgets.js"></script>
    `;
  }
  if (/instagram\.com/i.test(url)) {
    return `
      <div class="social-embed-container"><div class="video-responsive instagram-embed">
        <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="${url}" data-instgrm-version="14"></blockquote>
        <script async src="https://www.instagram.com/embed.js"></script>
      </div></div>
    `;
  }
  if (/facebook\.com/i.test(url)) {
    return `
      <iframe src="https://www.facebook.com/plugins/post.php?href=${encodeURIComponent(url)}" width="100%" height="500" frameborder="0"></iframe>
    `;
  }
  if (/youtube\.com|youtu\.be/i.test(url)) {
    const m = url.match(/(v=|\/)([0-9A-Za-z_-]{11})/);
    if (m && m[2]) {
      return `<div class="video-responsive"><iframe src="https://www.youtube.com/embed/${m[2]}" frameborder="0" allowfullscreen></iframe></div>`;
    }
  }
  if (/vimeo\.com/i.test(url)) {
    const m = url.match(/vimeo\.com\/(\d+)/);
    if (m && m[1]) {
      return `<div class="video-responsive"><iframe src="https://player.vimeo.com/video/${m[1]}" frameborder="0" allowfullscreen></iframe></div>`;
    }
  }
  if (/tiktok\.com/i.test(url)) {
    const m = url.match(/video\/(\d+)/);
    if (m && m[1]) {
      return `<iframe src="https://www.tiktok.com/embed/v2/${m[1]}" width="100%" height="600" frameborder="0" allowfullscreen></iframe>`;
    }
  }
  // fallback: just hyperlink
  return `<a href="${url}" target="_blank">${url}</a>`;
}

// ====== INICIALIZACIÓN ======
// clipboard matcher for existing social-embed divs
const Delta = Quill.import('delta');

if (editorElement) {
  // create editor instance
  quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Escribe aquí...',
    modules: {
      toolbar: {
        container: '.editor-toolbar',
        handlers: {
          image: imageHandler,
          embed: embedHandler
        }
      },
      imageResize: {
        modules: [ 'Resize', 'DisplaySize']
      }
    }
  });

  // convert pasted existing wrapper divs into blot
  quill.clipboard.addMatcher('DIV', function(node, delta) {
    if (node.classList && node.classList.contains('social-embed')) {
      const url = node.getAttribute('data-url');
      return new Delta().insert({ socialEmbed: url });
    }
    return delta;
  });

  // ====== CARGAR CONTENIDO EXISTENTE ======
  const editorContent = document.getElementById('editorContent');
  if (editorContent && editorContent.textContent.trim().length > 0) {
    quill.clipboard.dangerouslyPasteHTML(editorContent.innerHTML);
  }
}
// ====== HANDLER DE IMÁGENES (PREPARADO PARA CROP) ======
function imageHandler() {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.onchange = () => {
    const file = input.files[0];
    if (!file || !quill) return;
    const reader = new FileReader();
    reader.onload = () => {
      const range = quill.getSelection(true);
      if (!range) return;
      quill.insertEmbed(range.index, 'image', reader.result);
      quill.setSelection(range.index + 1);
    };
    reader.readAsDataURL(file);
  };
}

// ====== HANDLER PARA EMBEDS SOCIALES ======
function embedHandler() {
  const url = prompt("Introduce la URL a embeber (Twitter, Instagram, Facebook, etc.):");
  if (!url || !quill) return;
  // escape quotes to avoid breaking attributes
  const safeUrl = url.replace(/"/g, '&quot;');
  const range = quill.getSelection(true);
  if (!range) return;
  const html = `<div class="social-embed" data-url="${safeUrl}"><a href="${safeUrl}" target="_blank">${safeUrl}</a></div>`;
  quill.clipboard.dangerouslyPasteHTML(range.index, html);
  // move cursor after the inserted block
  quill.setSelection(range.index + 1);
}
/* verifica el contenido del editor antes de enviar el formulario */
const form = document.getElementById('formPublicacion') || document.getElementById('formEdicion');
const contenidoInput = document.getElementById('contenido');
if (form && contenidoInput && quill) {
  form.addEventListener('submit', () => {
    let html = quill.root.innerHTML;
    // ensure social-embed placeholders are minimal (strip inner HTML/scripts)
    html = html.replace(/<div class="social-embed"[^>]*data-url="([^"]+)"[^>]*>.*?<\/div>/gi,
                        '<div class="social-embed" data-url="$1"></div>');
    contenidoInput.value = html;
  });
}
// modal-delete
function initDeleteModals() {
    const modalConfigs = [
        {
            selector: ".btn-delete",
            overlayId: "modalOverlay",
            titleId: "modalTitle",
            inputId: "modalId",
            formatTitle: title => `¿Eliminar la noticia "${title}"?`,
            dataKey: "titulo"
        },
        {
            selector: ".btn-delete-publicidad",
            overlayId: "modalOverlayP",
            titleId: "modalTitleP",
            inputId: "modalIdP",
            formatTitle: title => `¿Eliminar la publicidad "${title}"?`,
            dataKey: "titulo"
        },
        {
            selector: ".btn-delete-usuario",
            overlayId: "modalOverlayU",
            titleId: "modalTitleU",
            inputId: "modalIdU",
            formatTitle: title => `¿Eliminar el usuario "${title}"?`,
            dataKey: "nombre"
        }
    ];

    const configByClass = {};
    modalConfigs.forEach(config => {
        configByClass[config.selector.replace('.', '')] = config;
    });

    document.body.addEventListener("click", event => {
        const button = event.target.closest(
            ".btn-delete, .btn-delete-publicidad, .btn-delete-usuario"
        );
        if (!button) return;
        event.preventDefault();

        const config = modalConfigs.find(c => button.matches(c.selector));
        if (!config) return;

        const overlay = document.getElementById(config.overlayId);
        const title = document.getElementById(config.titleId);
        const input = document.getElementById(config.inputId);
        if (!overlay || !title || !input) return;

        const dataValue = button.dataset[config.dataKey] || "";
        title.textContent = config.formatTitle(dataValue);
        input.value = button.dataset.id || "";
        overlay.style.display = "flex";
    });

    document.querySelectorAll(".btn-cancel").forEach(cancelBtn => {
        cancelBtn.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            const overlay = cancelBtn.closest(".crop-modal");
            if (overlay) {
                overlay.style.display = "none";
            }
        });
    });

    document.body.addEventListener("click", event => {
        const overlay = event.target.closest(".crop-modal");
        if (!overlay) return;
        if (event.target === overlay) {
            overlay.style.display = "none";
        }
    });

    document.querySelectorAll(".crop-modal-content").forEach(modalContent => {
        modalContent.addEventListener("click", e => e.stopPropagation());
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDeleteModals);
} else {
    initDeleteModals();
}
// modal validacion
document.addEventListener("DOMContentLoaded", () => {
    const modalTime = document.getElementById("timeModalOverlay");
    const autoAdjustBtn = document.getElementById("autoAdjustBtn");
    const manualAdjustBtn = document.getElementById("manualAdjustBtn");
    const fechaInput = document.getElementsByName("fecha_publicacion")[0];
    const guardarNoticiaBtns = document.getElementsByName("guardarNoticia");
    const modalForm = document.getElementById("formPublicacion");
    function getLocalDatetimeString(date = new Date()) {
      const offset = date.getTimezoneOffset();
      const local = new Date(date.getTime() - offset * 60000);
      return local.toISOString().slice(0,16);
    } 
    guardarNoticiaBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const ahora = getLocalDatetimeString();
            const fechaSeleccionada = fechaInput.value;
            if (fechaSeleccionada < ahora) {
                modalTime.style.display = "flex";
            } else {
                modalForm.requestSubmit();
            }
        });
    });
    autoAdjustBtn.addEventListener("click", () => {
        fechaInput.value = getLocalDatetimeString();
        modalTime.style.display = "none";
        modalForm.requestSubmit();
    });
    manualAdjustBtn.addEventListener("click", () => {
        modalTime.style.display = "none";
    });
    // Evitar cerrar modal al hacer click dentro
    const modalContent = document.querySelector(".crop-modal-content");
    modalContent?.addEventListener("click", e => e.stopPropagation());
});
// Crop de publicidad
(() => {
  let cropper;
  const inputImage = document.getElementById("imagen");
  const imagePreview = document.getElementById("imagePreview");
  const resultPreview = document.getElementById("resultPreview");
  const cropBtn = document.getElementById("cropBtn");
  const resetBtn = document.getElementById("resetBtn");
  const tipoPublicidad = document.getElementById("tipo");
  const inputCrop = document.getElementById("imagenCrop");

  // Tamaños según tipo
  function getAspectRatio() {
      let tipo = tipoPublicidad.value;
      if (tipo == "1") return 21 / 6; // Banner
      if (tipo == "2") return 1 / 1;  // Cuadro
      return NaN;
  }

  // Cargar imagen
  inputImage.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
          imagePreview.src = reader.result;
          imagePreview.style.display = "block";

          if (cropper) cropper.destroy();

          cropper = new Cropper(imagePreview, {
              aspectRatio: getAspectRatio(),
              viewMode: 1,
              autoCropArea: 1,
              cropBoxResizable: false,
              dragMode: 'move',
              responsive: true,
              guides: true,
              background: false
          });
      };
      reader.readAsDataURL(file);
  });

  // Cambiar tipo publicidad en tiempo real
  tipoPublicidad.addEventListener("change", () => {
      if (cropper) {
          cropper.setAspectRatio(getAspectRatio());
      }
  });

  // Recortar
  cropBtn.addEventListener("click", () => {
      if (!cropper) return;

      const canvas = cropper.getCroppedCanvas({
          width: 1200,
          height: 600,
      });

      const croppedImage = canvas.toDataURL("image/jpeg", 0.9);
      resultPreview.src = croppedImage;
      inputCrop.value = croppedImage; // enviar al backend
  });

  // Deshacer
  resetBtn.addEventListener("click", () => {
      if (cropper) cropper.reset();
      resultPreview.src = "";
      inputCrop.value = "";
  });
})();