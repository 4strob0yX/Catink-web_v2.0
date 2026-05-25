<?php
/**
 * Componente de comentarios para noticias
 * Uso: include este archivo donde se necesite la sección de comentarios.
 * Requiere que $noticiaId esté definido antes de incluirlo.
 */
if (!isset($noticiaId)) return;
?>
<div id="seccion-comentarios" class="comentarios-section">
    <h3><i class="bi bi-chat-dots"></i> Comentarios <span id="totalComentarios"></span></h3>
    
    <!-- FORMULARIO -->
    <div class="comentario-form-wrapper">
        <form id="formComentario" class="comentario-form">
            <input type="hidden" name="noticia_id" value="<?= $noticiaId ?>">
            <input type="hidden" name="parent_id" id="parentId" value="">
            <div id="replyIndicator" style="display:none;" class="reply-indicator">
                <small>Respondiendo a <strong id="replyTo"></strong></small>
                <button type="button" id="cancelReply" class="btn-cancel-reply">&times;</button>
            </div>
            <div class="row" style="gap:10px 0;">
                <div class="col-md-6">
                    <input type="text" name="nombre" placeholder="Tu nombre *" required class="form-control">
                </div>
                <div class="col-md-6">
                    <input type="email" name="correo" placeholder="Correo (opcional)" class="form-control">
                </div>
            </div>
            <textarea name="contenido" placeholder="Escribe tu comentario..." required class="form-control" rows="3" maxlength="1000"></textarea>
            <div class="d-flex justify-content-between align-items-center" style="margin-top:10px;">
                <small class="text-muted"><span id="charCount">0</span>/1000</small>
                <button type="submit" class="btn btn-success" id="btnEnviar">
                    <i class="bi bi-send"></i> Comentar
                </button>
            </div>
            <div id="formMsg" style="display:none; margin-top:10px;"></div>
        </form>
    </div>

    <!-- LISTA DE COMENTARIOS -->
    <div id="listaComentarios" class="comentarios-lista">
        <p class="text-muted text-center">Cargando comentarios...</p>
    </div>
</div>

<style>
.comentarios-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid var(--border);
}
.comentario-form-wrapper {
    margin-bottom: 30px;
}
.comentario-form textarea,
.comentario-form input {
    margin-top: 10px;
}
.reply-indicator {
    background: rgba(0,123,255,0.08);
    border-left: 3px solid #007bff;
    padding: 8px 12px;
    margin-top: 10px;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.btn-cancel-reply {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: var(--text);
}
.comentarios-lista {
    margin-top: 20px;
}
.comentario-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 16px;
    background: var(--card-bg);
}
.comentario-item.respuesta {
    margin-left: 40px;
    border-left: 3px solid var(--accent);
}
.comentario-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.comentario-autor {
    font-weight: 700;
    color: var(--text);
}
.comentario-fecha {
    font-size: 12px;
    color: var(--muted);
}
.comentario-contenido {
    margin: 10px 0;
    line-height: 1.6;
    word-break: break-word;
}
.comentario-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 10px;
}
.comentario-actions button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background .15s;
}
.comentario-actions button:hover {
    background: rgba(0,0,0,0.05);
}
.comentario-actions button.active {
    color: var(--accent);
}
.badge-censurado {
    font-size: 10px;
    background: #ffc107;
    color: #000;
    padding: 2px 6px;
    border-radius: 3px;
}
@media (max-width: 768px) {
    .comentario-item.respuesta {
        margin-left: 16px;
    }
}
</style>

<script>
(function() {
    const noticiaId = <?= $noticiaId ?>;
    const lista = document.getElementById('listaComentarios');
    const form = document.getElementById('formComentario');
    const textarea = form.querySelector('textarea[name="contenido"]');
    const charCount = document.getElementById('charCount');
    const formMsg = document.getElementById('formMsg');

    // Contador de caracteres
    textarea.addEventListener('input', () => {
        charCount.textContent = textarea.value.length;
    });

    // Cargar comentarios
    function cargarComentarios() {
        fetch(`./controllers/obtener_comentarios.php?noticia_id=${noticiaId}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('totalComentarios').textContent = `(${data.total})`;
                if (data.total === 0) {
                    lista.innerHTML = '<p class="text-muted text-center">Sé el primero en comentar.</p>';
                    return;
                }
                lista.innerHTML = data.comentarios.map(c => renderComentario(c)).join('');
                bindActions();
            })
            .catch(() => {
                lista.innerHTML = '<p class="text-muted">Error al cargar comentarios.</p>';
            });
    }

    function renderComentario(c) {
        const censurado = c.estado === 'censurado' ? '<span class="badge-censurado">moderado</span>' : '';
        let html = `
            <div class="comentario-item" id="com-${c.id_com}">
                <div class="comentario-header">
                    <span class="comentario-autor">${escapeHtml(c.nombre)} ${censurado}</span>
                    <span class="comentario-fecha">${c.fecha_formateada}</span>
                </div>
                <div class="comentario-contenido">${escapeHtml(c.contenido)}</div>
                <div class="comentario-actions">
                    <button class="btn-like" data-id="${c.id_com}"><i class="bi bi-hand-thumbs-up"></i> <span>${c.likes}</span></button>
                    <button class="btn-dislike" data-id="${c.id_com}"><i class="bi bi-hand-thumbs-down"></i> <span>${c.dislikes}</span></button>
                    <button class="btn-reply" data-id="${c.id_com}" data-nombre="${escapeHtml(c.nombre)}"><i class="bi bi-reply"></i> Responder</button>
                    <button class="btn-report" data-id="${c.id_com}"><i class="bi bi-flag"></i></button>
                </div>
            </div>
        `;
        // Respuestas
        if (c.respuestas && c.respuestas.length > 0) {
            html += c.respuestas.map(r => {
                const cenR = r.estado === 'censurado' ? '<span class="badge-censurado">moderado</span>' : '';
                return `
                    <div class="comentario-item respuesta" id="com-${r.id_com}">
                        <div class="comentario-header">
                            <span class="comentario-autor">${escapeHtml(r.nombre)} ${cenR}</span>
                            <span class="comentario-fecha">${r.fecha_formateada}</span>
                        </div>
                        <div class="comentario-contenido">${escapeHtml(r.contenido)}</div>
                        <div class="comentario-actions">
                            <button class="btn-like" data-id="${r.id_com}"><i class="bi bi-hand-thumbs-up"></i> <span>${r.likes}</span></button>
                            <button class="btn-dislike" data-id="${r.id_com}"><i class="bi bi-hand-thumbs-down"></i> <span>${r.dislikes}</span></button>
                            <button class="btn-report" data-id="${r.id_com}"><i class="bi bi-flag"></i></button>
                        </div>
                    </div>
                `;
            }).join('');
        }
        return html;
    }

    function bindActions() {
        // Likes
        document.querySelectorAll('.btn-like').forEach(btn => {
            btn.addEventListener('click', () => reaccionar(btn.dataset.id, 'like'));
        });
        // Dislikes
        document.querySelectorAll('.btn-dislike').forEach(btn => {
            btn.addEventListener('click', () => reaccionar(btn.dataset.id, 'dislike'));
        });
        // Responder
        document.querySelectorAll('.btn-reply').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('parentId').value = btn.dataset.id;
                document.getElementById('replyTo').textContent = btn.dataset.nombre;
                document.getElementById('replyIndicator').style.display = 'flex';
                textarea.focus();
            });
        });
        // Reportar
        document.querySelectorAll('.btn-report').forEach(btn => {
            btn.addEventListener('click', () => reportar(btn.dataset.id));
        });
    }

    // Cancelar respuesta
    document.getElementById('cancelReply').addEventListener('click', () => {
        document.getElementById('parentId').value = '';
        document.getElementById('replyIndicator').style.display = 'none';
    });

    // Enviar comentario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnEnviar');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass"></i> Enviando...';

        const formData = new FormData(form);
        fetch('./controllers/crear_comentario.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send"></i> Comentar';
                if (d.success) {
                    showMsg(d.mensaje, 'success');
                    form.reset();
                    charCount.textContent = '0';
                    document.getElementById('parentId').value = '';
                    document.getElementById('replyIndicator').style.display = 'none';
                    cargarComentarios();
                } else {
                    showMsg(d.error, 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send"></i> Comentar';
                showMsg('Error de conexión', 'error');
            });
    });

    function reaccionar(id, tipo) {
        const form = new FormData();
        form.append('comentario_id', id);
        form.append('tipo', tipo);
        fetch('./controllers/reaccionar_comentario.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const com = document.getElementById('com-' + id);
                    com.querySelector('.btn-like span').textContent = d.likes;
                    com.querySelector('.btn-dislike span').textContent = d.dislikes;
                }
            });
    }

    function reportar(id) {
        const motivo = prompt('Motivo del reporte:\n1) spam\n2) ofensivo\n3) irrelevante\n4) acoso\n5) otro\n\nEscribe el motivo:');
        if (!motivo) return;
        const motivosMap = {'1':'spam','2':'ofensivo','3':'irrelevante','4':'acoso','5':'otro'};
        const motivoFinal = motivosMap[motivo] || motivo;
        
        const form = new FormData();
        form.append('comentario_id', id);
        form.append('motivo', motivoFinal);
        fetch('./controllers/reportar_comentario.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(d => {
                alert(d.mensaje || d.error);
            });
    }

    function showMsg(text, type) {
        formMsg.style.display = 'block';
        formMsg.style.color = type === 'success' ? 'green' : 'red';
        formMsg.textContent = text;
        setTimeout(() => { formMsg.style.display = 'none'; }, 5000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Init
    cargarComentarios();
})();
</script>
