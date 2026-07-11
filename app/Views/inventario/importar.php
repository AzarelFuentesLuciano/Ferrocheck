<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importador Ferromex</title>
    <link rel="stylesheet" href="/assets/css/importador.css">
</head>
<body>
    <div class="importador-container">
        <div class="importador-card">
            <div class="importador-header">
                <h1>Importador Ferromex</h1>
                <p>Importa el inventario de Ferromex de forma ordenada, segura y profesional.</p>
            </div>

            <label class="dropzone" for="fileInput" id="dropzone">
                <input type="file" id="fileInput" name="archivo" accept=".xlsx,.xls" hidden>
                <div class="dropzone-content">
                    <div class="dropzone-icon">📄</div>
                    <h2>Arrastra y suelta tu archivo aquí</h2>
                    <p>Archivos compatibles: .xlsx y .xls</p>
                </div>
            </label>

            <div class="file-info" id="fileInfo" hidden>
                <div class="file-info-item">
                    <span class="label">Archivo:</span>
                    <span class="value" id="fileName">-</span>
                </div>
                <div class="file-info-item">
                    <span class="label">Tamaño:</span>
                    <span class="value" id="fileSize">-</span>
                </div>
                <div class="file-info-item">
                    <span class="label">Tipo:</span>
                    <span class="value" id="fileType">-</span>
                </div>
            </div>

            <div class="progress-block" aria-live="polite">
                <div class="progress-labels">
                    <span>Progreso</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progressFill"></div>
                </div>
                <p class="status-message" id="statusMessage">Listo para importar</p>
            </div>

            <div class="actions">
                <label class="btn btn-secondary" for="fileInput">Seleccionar archivo</label>
                <button class="btn btn-primary" id="importBtn" type="button" disabled>Importar</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/importador.js"></script>
</body>
</html>
