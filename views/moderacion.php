<?php
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__ . "/../data/conexion.php");

// Protección ACL - requiere permiso de noticias (editar)
$ACLNoticias = $_SESSION['ACL']['noticias'] ?? ['crear'=>false,'leer'=>false,'editar'=>false,'eliminar'=>false];
if (!$superadmin && empty($ACLNoticias['editar'])) {
    header("Location: admin.php");
    exit();
}

// ============================
// STATS DE MODERACIÓN
// ============================
$stats = $con->query("
    SELECT
        (SELECT COUNT(*) FROM comentarios WHERE estado = 'pendiente') AS pendientes,
        (SELECT COUNT(*) FROM comentarios WHERE estado = 'aprobado') AS aprobados,
        (SELECT COUNT(*) FROM comentarios WHERE estado = 'rechazado') AS rechazados,
        (SELECT COUNT(*) FROM comentarios WHERE estado = 'censurado') AS censurados,
        (SELECT COUNT(*) FROM comentarios_reportes WHERE estado = 'pendiente') AS reportes_pendientes
")->fetch_assoc();

// ============================
// COMENTARIOS PENDIENTES
// ============================
$filtro = $_GET['filtro'] ?? 'pendiente';
$filtrosValidos = ['pendiente', 'aprobado', 'rechazado', 'censurado', 'todos'];
if (!in_array($filtro, $filtrosValidos)) $filtro = 'pendiente';

$sqlFiltro = $filtro === 'todos' ? "" : "WHERE c.estado = '{$filtro}'";
$comentarios = $con->query("
    SELECT c.*, n.titulo AS noticia_titulo,
           (SELECT COUNT(*) FROM comentarios_reportes WHERE comentario_id = c.id_com AND estado = 'pendiente') AS reportes
    FROM comentarios c
    LEFT JOIN noticias n ON c.noticia_id = n.id
    {$sqlFiltro}
    ORDER BY c.fecha DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);

// ============================
// PALABRAS PROHIBIDAS
// ============================
$palabras = $con->query("SELECT * FROM palabras_prohibidas ORDER BY severidad DESC, palabra ASC")->fetch_all(MYSQLI_ASSOC);

// ============================
// LOG DE MODERACIÓN (últimos 20)
// ============================
$logs = $con->query("
    SELECT ml.*, c.contenido AS comentario_texto, u.usuario AS moderador
    FROM moderacion_log ml
    LEFT JOIN comentarios c ON ml.comentario_id = c.id_com
    LEFT JOIN usuarios u ON ml.usuario_id = u.id_u
    ORDER BY ml.fecha DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// ============================
// CONFIGURACIÓN
// ============================
$config = $con->query("SELECT * FROM moderacion_config")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-fluid">
    <h1><i class="bi bi-shield-check"></i> Moderación de Comentarios</h1>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-hourglass fs-3 text-warning"></i>
                    <h4><?= $stats['pendientes'] ?></h4>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-check-circle fs-3 text-success"></i>
                    <h4><?= $stats['aprobados'] ?></h4>
                    <small class="text-muted">Aprobados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-x-circle fs-3 text-danger"></i>
                    <h4><?= $stats['rechazados'] ?></h4>
                    <small class="text-muted">Rechazados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-asterisk fs-3 text-info"></i>
                    <h4><?= $stats['censurados'] ?></h4>
                    <small class="text-muted">Censurados</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <i class="bi bi-flag fs-3 text-danger"></i>
                    <h4><?= $stats['reportes_pendientes'] ?></h4>
                    <small class="text-muted">Reportes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
                <a href="?filtro=pendiente" class="btn <?= $filtro==='pendiente' ? 'btn-success' : 'btn-secondary' ?>">Pendientes (<?= $stats['pendientes'] ?>)</a>
                <a href="?filtro=aprobado" class="btn <?= $filtro==='aprobado' ? 'btn-success' : 'btn-secondary' ?>">Aprobados</a>
                <a href="?filtro=rechazado" class="btn <?= $filtro==='rechazado' ? 'btn-success' : 'btn-secondary' ?>">Rechazados</a>
                <a href="?filtro=censurado" class="btn <?= $filtro==='censurado' ? 'btn-success' : 'btn-secondary' ?>">Censurados</a>
                <a href="?filtro=todos" class="btn <?= $filtro==='todos' ? 'btn-success' : 'btn-secondary' ?>">Todos</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($comentarios)): ?>
                <p class="text-muted text-center">No hay comentarios en esta categoría.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Autor</th>
                                <th>Comentario</th>
                                <th>Noticia</th>
                                <th>Estado</th>
                                <th>Reportes</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $c): ?>
                            <tr id="row-<?= $c['id_com'] ?>">
                                <td>
                                    <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($c['ip'] ?? '') ?></small>
                                </td>
                                <td style="max-width:300px;">
                                    <p style="margin:0; word-break:break-word;"><?= htmlspecialchars(mb_strimwidth($c['contenido'], 0, 150, '...')) ?></p>
                                    <?php if ($c['contenido_original'] && $c['contenido_original'] !== $c['contenido']): ?>
                                        <details><summary class="text-muted" style="font-size:11px;">Ver original</summary>
                                        <small class="text-danger"><?= htmlspecialchars($c['contenido_original']) ?></small></details>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= htmlspecialchars(mb_strimwidth($c['noticia_titulo'] ?? 'N/A', 0, 40, '...')) ?></small></td>
                                <td>
                                    <?php
                                    $badgeClass = match($c['estado']) {
                                        'aprobado' => 'bg-success',
                                        'pendiente' => 'bg-warning',
                                        'rechazado' => 'bg-danger',
                                        'censurado' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>" style="padding:4px 8px; border-radius:4px; color:#fff; font-size:11px;"><?= $c['estado'] ?></span>
                                </td>
                                <td class="text-center"><?= $c['reportes'] > 0 ? '<span style="color:red; font-weight:bold;">'.$c['reportes'].'</span>' : '0' ?></td>
                                <td><small><?= date('d/m/y H:i', strtotime($c['fecha'])) ?></small></td>
                                <td>
                                    <div class="btn-group" style="gap:4px;">
                                        <?php if ($c['estado'] !== 'aprobado'): ?>
                                            <button class="btn btn-edit btn-mod" data-id="<?= $c['id_com'] ?>" data-accion="aprobar" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                                        <?php endif; ?>
                                        <?php if ($c['estado'] !== 'rechazado'): ?>
                                            <button class="btn btn-delete btn-mod" data-id="<?= $c['id_com'] ?>" data-accion="rechazar" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger btn-mod" data-id="<?= $c['id_com'] ?>" data-accion="eliminar" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PALABRAS PROHIBIDAS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="margin:0;"><i class="bi bi-chat-left-text"></i> Palabras Prohibidas (<?= count($palabras) ?>)</h5>
            <button class="btn btn-success" id="btnAgregarPalabra"><i class="bi bi-plus-lg"></i> Agregar</button>
        </div>
        <div class="card-body">
            <!-- Form agregar -->
            <div id="formPalabra" style="display:none; margin-bottom:20px;">
                <div class="row" style="gap:10px 0;">
                    <div class="col-md-3">
                        <input type="text" id="inputPalabra" class="form-control" placeholder="Palabra...">
                    </div>
                    <div class="col-md-2">
                        <select id="selectSeveridad" class="form-control">
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="selectAccion" class="form-control">
                            <option value="censurar">Censurar</option>
                            <option value="rechazar">Rechazar</option>
                            <option value="revisar">Revisar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success w-100" id="btnGuardarPalabra">Guardar</button>
                    </div>
                </div>
            </div>
            <!-- Tabla -->
            <div class="table-responsive">
                <table class="table" id="tablaPalabras">
                    <thead>
                        <tr>
                            <th>Palabra</th>
                            <th>Severidad</th>
                            <th>Acción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($palabras as $p): ?>
                        <tr id="palabra-<?= $p['id_palabra'] ?>">
                            <td><code><?= htmlspecialchars($p['palabra']) ?></code></td>
                            <td>
                                <?php
                                $sevColor = match($p['severidad']) {
                                    'alta' => 'color:red;',
                                    'media' => 'color:orange;',
                                    'baja' => 'color:green;',
                                    default => ''
                                };
                                ?>
                                <span style="<?= $sevColor ?> font-weight:600;"><?= $p['severidad'] ?></span>
                            </td>
                            <td><?= $p['accion'] ?></td>
                            <td><?= $p['activo'] ? '✅ Activo' : '❌ Inactivo' ?></td>
                            <td>
                                <button class="btn btn-delete btn-eliminar-palabra" data-id="<?= $p['id_palabra'] ?>"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CONFIGURACIÓN -->
    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 style="margin:0;"><i class="bi bi-gear"></i> Configuración de Moderación</h5></div>
        <div class="card-body">
            <form id="formConfig">
                <?php foreach ($config as $cfg): ?>
                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <label><strong><?= htmlspecialchars($cfg['descripcion'] ?? $cfg['clave']) ?></strong></label>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="config[<?= $cfg['clave'] ?>]" value="<?= htmlspecialchars($cfg['valor']) ?>" class="form-control">
                    </div>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success">Guardar Configuración</button>
            </form>
        </div>
    </div>

    <!-- LOG DE MODERACIÓN -->
    <div class="card shadow-sm mb-4">
        <div class="card-header"><h5 style="margin:0;"><i class="bi bi-clock-history"></i> Historial de Moderación</h5></div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <p class="text-muted">Sin acciones registradas.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Fecha</th><th>Acción</th><th>Moderador</th><th>Motivo</th><th>Palabras</th></tr></thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><small><?= date('d/m/y H:i', strtotime($log['fecha'])) ?></small></td>
                                <td><span class="badge bg-secondary" style="padding:3px 6px; border-radius:4px; color:#fff; font-size:11px;"><?= $log['accion'] ?></span></td>
                                <td><?= $log['automatico'] ? '<em>Sistema</em>' : htmlspecialchars($log['moderador'] ?? 'N/A') ?></td>
                                <td><small><?= htmlspecialchars(mb_strimwidth($log['motivo'] ?? '', 0, 60, '...')) ?></small></td>
                                <td><small><code><?= htmlspecialchars($log['palabras_detectadas'] ?? '-') ?></code></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ============================
// MODERAR COMENTARIOS
// ============================
document.querySelectorAll('.btn-mod').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const accion = this.dataset.accion;
        if (accion === 'eliminar' && !confirm('¿Eliminar este comentario permanentemente?')) return;
        
        const form = new FormData();
        form.append('comentario_id', id);
        form.append('accion', accion);
        
        fetch('./../controllers/moderar_comentario.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('row-' + id)?.remove();
                } else {
                    alert(d.error || 'Error');
                }
            });
    });
});

// ============================
// PALABRAS PROHIBIDAS
// ============================
document.getElementById('btnAgregarPalabra').addEventListener('click', () => {
    document.getElementById('formPalabra').style.display = 
        document.getElementById('formPalabra').style.display === 'none' ? 'block' : 'none';
});

document.getElementById('btnGuardarPalabra').addEventListener('click', () => {
    const palabra = document.getElementById('inputPalabra').value.trim();
    if (!palabra) return alert('Escribe una palabra');
    
    const form = new FormData();
    form.append('accion', 'crear');
    form.append('palabra', palabra);
    form.append('severidad', document.getElementById('selectSeveridad').value);
    form.append('accion_palabra', document.getElementById('selectAccion').value);
    
    fetch('./../controllers/gestionar_palabras.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                location.reload();
            } else {
                alert(d.error || 'Error');
            }
        });
});

document.querySelectorAll('.btn-eliminar-palabra').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('¿Eliminar esta palabra?')) return;
        const form = new FormData();
        form.append('accion', 'eliminar');
        form.append('id', this.dataset.id);
        
        fetch('./../controllers/gestionar_palabras.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('palabra-' + this.dataset.id)?.remove();
                }
            });
    });
});

// ============================
// GUARDAR CONFIGURACIÓN
// ============================
document.getElementById('formConfig').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('guardar_config', '1');
    
    fetch('./../controllers/guardar_moderacion_config.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(d => {
            if (d.success) alert('Configuración guardada');
            else alert(d.error || 'Error');
        });
});
</script>
<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>
