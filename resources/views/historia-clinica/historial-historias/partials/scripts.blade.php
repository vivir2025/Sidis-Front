    <script>
    // ✅ DETECTAR SI VIENE CON PARÁMETRO DE IMPRESIÓN (SIN JQUERY)
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const shouldPrint = urlParams.get('print');
        
        if (shouldPrint === '1') {
            // Esperar a que cargue todo el contenido (incluyendo imágenes)
            setTimeout(function() {
                window.print();
                
                // Cerrar la ventana después de imprimir o cancelar
                window.onafterprint = function() {
                    window.close();
                };
            }, 1500); // Esperar 1.5 segundos
        }
    });
    </script>
