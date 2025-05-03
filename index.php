<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Analizador de IPs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="icono.ico" type="image/x-icon">
    <style>
        .scroll-box {
            max-height: 300px;
            min-height: 300px; /* <-- esto lo fuerza a mantenerse igualado aunque esté vacío */
            overflow-y: auto;
            cursor: pointer;
        }
        .scroll-box div:hover {
            background-color: #e9ecef;
        }
        .fixed-height-list {
            max-height: 200px;
            min-height: 200px;
            overflow-y: auto;
            padding: 0.5rem; /* Añadir algo de espacio interno */
        }

        .card-body {
            padding: 0; /* Remueve el padding adicional si lo hubiera */
        }

        .card-header {
            padding: 0.75rem 1rem; /* Ajuste para mejorar la alineación */
        }
        select.form-select {
            height: 400px;
            font-size: 14px;
        }
        .resaltado {
            background-color: #ffff99 !important;
        }
        .rango-gris {
            background-color: #cccccc !important;
        }
        .rango-amarillo {
            background-color: #ffff99 !important;
        }
        .rango-rojo {
            background-color: #ff9999 !important;
        }
        label {
            font-weight: bold;
            margin-top: 20px;
        }
        .terminal-like {
        background-color: #000000; /* Fondo negro */
        color: #ffffff; /* Texto blanco */ /* Cambiado a blanco */
        font-family: monospace, monospace;
        font-size: 14px;
        padding: 10px;
        border: 1px solid #333; /* Borde gris oscuro */
        border-radius: 5px;
        overflow: auto;
        line-height: 1.5;
        width: 100%; /* Asegurar que ocupe el ancho de su contenedor */
        box-sizing: border-box; /* Incluir padding y border en el ancho */
    }
    .textarea-container {
        position: relative;
        width: 100%; /* Asegurar que el contenedor ocupe el ancho */
    }
    .textarea-copy-button {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
        z-index: 10;
        background-color: #333; /* Botón gris oscuro */
        color: #fff;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }
    .textarea-copy-button:hover {
        background-color: #555;
    }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h2 class="mb-4 text-primary">Analizador de IPs por Octetos</h2>

    <form method="post" class="mb-5">
        <div class="mb-3">
            <label for="lista" class="form-label">Pega tu lista de IPs (formato: IP - repeticiones, ...):</label>
            <textarea name="lista" class="form-control" rows="6"><?php echo isset($_POST['lista']) ? htmlspecialchars($_POST['lista']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Procesar</button>
    </form>

<?php if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["lista"])):
    $entrada = $_POST["lista"];
    $lineas = preg_split("/,\s*/", trim($entrada));

    $datos = [];
    foreach ($lineas as $linea) {
        if (preg_match("/^\s*([\d\.]+)\s*-\s*(\d+)\s*$/", $linea, $matches)) {
            $ip = $matches[1];
            $reps = intval($matches[2]);
            $datos[] = ['ip' => $ip, 'reps' => $reps];
        }
    }

    usort($datos, fn($a, $b) => $b['reps'] - $a['reps']);
    $json = [];

    foreach ($datos as $dato) {
        $ip = $dato['ip'];
        $reps = $dato['reps'];
        $octetos = explode('.', $ip);
        if (count($octetos) === 4) {
            $json[] = [
                'ip' => $ip,
                'reps' => $reps,
                'o1' => $octetos[0],
                'o2' => $octetos[1],
                'o3' => $octetos[2],
                'o4' => $octetos[3]
            ];
        }
    }

    $jsonData = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

<div class="row">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">Primer octeto</div>
            <div id="list-o1" class="card-body scroll-box"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">Segundo octeto</div>
            <div id="list-o2" class="card-body scroll-box"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">Tercer octeto</div>
            <div id="list-o3" class="card-body scroll-box"></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">IPs coincidentes</div>
            <ul id="ip-listado" class="list-group fixed-height-list"></ul>
        </div>
    </div>
</div>


        <div class="row mt-4">
            <div class="col">
                <label class="form-label">Comando sugerido:</label>
                <pre id="comandoBase" class="bg-dark text-white p-3 rounded">iptables -A INPUT -s 0.0.0.0/0 -j DROP</pre>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <button class="btn btn-primary" onclick="generarComandos()">Generar Comandos</button>
            </div>
        </div>
        <div class="row mt-4">
            <div class="row mt-4">
                <div class="col-sm-4">
                    <label class="form-label">Comandos de bloqueo /8 : :</label>
                    <div class="textarea-container">
                        <textarea id="comandos8" class="terminal-like" rows="5" readonly></textarea>
                        <button class="btn btn-sm btn-outline-secondary textarea-copy-button" onclick="copyToClipboard(document.getElementById('comandos8').value)">Copiar</button>
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Comandos de bloqueo /16 ::</label>
                    <div class="textarea-container">
                        <textarea id="comandos16" class="terminal-like" rows="5" readonly></textarea>
                        <button class="btn btn-sm btn-outline-secondary textarea-copy-button" onclick="copyToClipboard(document.getElementById('comandos16').value)">Copiar</button>
                    </div>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Comandos de bloqueo /24 ::</label>
                    <div class="textarea-container">
                        <textarea id="comandos24" class="terminal-like" rows="5" readonly></textarea>
                        <button class="btn btn-sm btn-outline-secondary textarea-copy-button" onclick="copyToClipboard(document.getElementById('comandos24').value)">Copiar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-1">
            <br><br><br>
        </div>

<script>

    function getColorClass(reps) {
        if (reps >= 500) return 'bg-danger text-white';
        if (reps >= 301) return 'bg-warning';
        if (reps >= 101) return 'bg-warning-subtle';
        if (reps >= 51) return 'bg-light';
        return 'bg-white';
    }

    const ipData = <?php echo $jsonData; ?>;

    const unique = arr => [...new Set(arr)];

    const listO1 = document.getElementById('list-o1');
    const listO2 = document.getElementById('list-o2');
    const listO3 = document.getElementById('list-o3');
    const ipListado = document.getElementById('ip-listado');

    // Obtener lista con sumatoria de repeticiones por primer octeto
    const oct1Data = {};
    ipData.forEach(ip => {
        if (!oct1Data[ip.o1]) oct1Data[ip.o1] = { count: 0, reps: 0 };
        oct1Data[ip.o1].count += 1;
        oct1Data[ip.o1].reps += ip.reps;
    });

    const oct1Sorted = Object.keys(oct1Data).sort((a, b) => oct1Data[b].reps - oct1Data[a].reps);
    listO1.innerHTML = oct1Sorted.map(o1 => {
        const reps = oct1Data[o1].reps;
        const color = getColorClass(reps);
        return `<div data-o1="${o1}" class="mb-1 p-2 rounded ${color}">
            ${o1.padStart(3, '0')}.xxx.xxx.xxx
            <small class="text-muted">REPETICIONES ${reps}</small>
        </div>`;
    }).join('');

    listO1.addEventListener('click', e => {
        const o1 = e.target.closest('div')?.dataset.o1;
        if (!o1) return;
        const filtered = ipData.filter(ip => ip.o1 === o1);

        const oct2Data = {};
        filtered.forEach(ip => {
            if (!oct2Data[ip.o2]) oct2Data[ip.o2] = { reps: 0 };
            oct2Data[ip.o2].reps += ip.reps;
        });

        const oct2Sorted = Object.keys(oct2Data)
        .sort((a, b) => oct2Data[b].reps - oct2Data[a].reps);
        listO2.innerHTML = oct2Sorted.map(o2 => {
            const reps = oct2Data[o2].reps;
            const color = getColorClass(reps);
            return `<div data-o1="${o1}" data-o2="${o2}" class="mb-1 p-2 rounded ${color}">
                ${o1.padStart(3, '0')}.${o2.padStart(3, '0')}.xxx.xxx
                <small class="text-muted">REPETICIONES ${reps}</small>
            </div>`;
        }).join('');
        listO3.innerHTML = '';
        ipListado.innerHTML = '';
    });

    listO2.addEventListener('click', e => {
        const o1 = e.target.closest('div')?.dataset.o1;
        const o2 = e.target.closest('div')?.dataset.o2;
        if (!o2) return;
        const filtered = ipData.filter(ip => ip.o1 === o1 && ip.o2 === o2);

        const oct3Data = {};
        filtered.forEach(ip => {
            if (!oct3Data[ip.o3]) oct3Data[ip.o3] = { reps: 0 };
            oct3Data[ip.o3].reps += ip.reps;
        });

        const oct3Sorted = Object.keys(oct3Data)
        .sort((a, b) => oct3Data[b].reps - oct3Data[a].reps);

        listO3.innerHTML = oct3Sorted.map(o3 => {
            const reps = oct3Data[o3].reps;
            const color = getColorClass(reps);
            return `<div data-o1="${o1}" data-o2="${o2}" data-o3="${o3}" class="mb-1 p-2 rounded ${color}">
                ${o1.padStart(3, '0')}.${o2.padStart(3, '0')}.${o3.padStart(3, '0')}.xxx
                <small class="text-muted">REPETICIONES ${reps}</small>
            </div>`;
        }).join('');
        ipListado.innerHTML = '';
    });

    listO3.addEventListener('click', e => {
        const o1 = e.target.closest('div')?.dataset.o1;
        const o2 = e.target.closest('div')?.dataset.o2;
        const o3 = e.target.closest('div')?.dataset.o3;
        if (!o3) return;
        const resultado = ipData.filter(ip => ip.o1 === o1 && ip.o2 === o2 && ip.o3 === o3);
        ipListado.innerHTML = resultado.map(ip =>
            `<li class="list-group-item">${ip.ip} (reps: ${ip.reps})</li>`
        ).join('');
    });
</script>

<script>
function generarComandos() {
    const listO1Element = document.getElementById("list-o1");
    const listO2Element = document.getElementById("list-o2");
    const listO3Element = document.getElementById("list-o3");
    const ipListadoElement = document.getElementById("ip-listado");
    const comandos8Textarea = document.getElementById("comandos8");
    const comandos16Textarea = document.getElementById("comandos16");
    const comandos24Textarea = document.getElementById("comandos24");

    const comandos8 = [];
    const comandos16 = [];
    const comandos24 = [];

    // Función para quitar ceros a la izquierda
    const limpiarOcteto = octeto => String(Number(octeto));

    // Comandos /8 basados en el primer elemento seleccionado en la lista de Primer octeto (si hay alguno)
    if (listO1Element && listO1Element.querySelector('.bg-info, .bg-success, .bg-primary, .bg-warning, .bg-warning-subtle, .bg-light, .bg-danger')) {
        const elementoO1 = listO1Element.querySelector('.bg-info, .bg-success, .bg-primary, .bg-warning, .bg-warning-subtle, .bg-light, .bg-danger');
        const o1 = elementoO1.dataset.o1;
        if (o1) {
            comandos8.push(`iptables -A INPUT -s ${limpiarOcteto(o1)}.0.0.0/8 -j DROP`);
        }
    }

    // Comandos /16 basados en los elementos actualmente en la lista de Segundo octeto
    if (listO2Element && listO2Element.children.length > 0) {
        for (const child of listO2Element.children) {
            const o1 = child.dataset.o1;
            const o2 = child.dataset.o2;
            if (o1 && o2) {
                comandos16.push(`iptables -A INPUT -s ${limpiarOcteto(o1)}.${limpiarOcteto(o2)}.0.0/16 -j DROP`);
            }
        }
    }

    // Comandos /24 basados en los elementos actualmente en la lista de Tercer octeto
    if (listO3Element && listO3Element.children.length > 0) {
        for (const child of listO3Element.children) {
            const o1 = child.dataset.o1;
            const o2 = child.dataset.o2;
            const o3 = child.dataset.o3;
            if (o1 && o2 && o3) {
                comandos24.push(`iptables -A INPUT -s ${limpiarOcteto(o1)}.${limpiarOcteto(o2)}.${limpiarOcteto(o3)}.0/24 -j DROP`);
            }
        }
    }

    comandos8Textarea.value = comandos8.join('\n');
    comandos16Textarea.value = comandos16.join('\n');
    comandos24Textarea.value = comandos24.join('\n');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copiado al portapapeles!');
    }).catch(err => {
        console.error('Error al copiar al portapapeles: ', err);
        alert('No se pudo copiar al portapapeles.');
    });
}
</script>

<?php endif; ?>
</div>
</body>
</html>