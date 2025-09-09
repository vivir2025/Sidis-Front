// public/js/cups-autocomplete.js

class CupsAutocomplete {
    constructor(options = {}) {
        this.codigoInput = options.codigoInput;
        this.nombreInput = options.nombreInput;
        this.hiddenInput = options.hiddenInput;
        this.resultsContainer = options.resultsContainer;
        this.minLength = options.minLength || 2;
        this.delay = options.delay || 300;
        
        this.searchTimeout = null;
        this.currentRequest = null;
        this.selectedCups = null;
        
        this.init();
    }
    
    init() {
        if (this.codigoInput) {
            this.setupCodigoInput();
        }
        
        if (this.nombreInput) {
            this.setupNombreInput();
        }
        
        this.setupResultsContainer();
        this.setupClickOutside();
    }
    
    setupCodigoInput() {
        this.codigoInput.addEventListener('input', (e) => {
            const codigo = e.target.value.trim();
            
            if (codigo.length >= this.minLength) {
                this.buscarPorCodigo(codigo);
            } else {
                this.clearResults();
                this.clearNombre();
            }
        });
        
        this.codigoInput.addEventListener('blur', () => {
            // Delay para permitir click en resultados
            setTimeout(() => {
                if (!this.selectedCups && this.codigoInput.value) {
                    this.clearNombre();
                }
            }, 200);
        });
    }
    
    setupNombreInput() {
        this.nombreInput.addEventListener('input', (e) => {
            const nombre = e.target.value.trim();
            
            if (nombre.length >= this.minLength) {
                this.buscarPorNombre(nombre);
            } else {
                this.clearResults();
                this.clearCodigo();
            }
        });
        
        this.nombreInput.addEventListener('blur', () => {
            setTimeout(() => {
                if (!this.selectedCups && this.nombreInput.value) {
                    this.clearCodigo();
                }
            }, 200);
        });
    }
    
    setupResultsContainer() {
        if (this.resultsContainer) {
            this.resultsContainer.style.display = 'none';
            this.resultsContainer.classList.add('cups-results');
        }
    }
    
    setupClickOutside() {
        document.addEventListener('click', (e) => {
            if (!this.isClickInside(e.target)) {
                this.clearResults();
            }
        });
    }
    
    isClickInside(target) {
        return this.codigoInput?.contains(target) ||
               this.nombreInput?.contains(target) ||
               this.resultsContainer?.contains(target);
    }
    
    buscarPorCodigo(codigo) {
        this.clearSearchTimeout();
        
        this.searchTimeout = setTimeout(() => {
            this.realizarBusqueda(codigo, 'codigo');
        }, this.delay);
    }
    
    buscarPorNombre(nombre) {
        this.clearSearchTimeout();
        
        this.searchTimeout = setTimeout(() => {
            this.realizarBusqueda(nombre, 'nombre');
        }, this.delay);
    }
    
    async realizarBusqueda(termino, tipo) {
        try {
            this.showLoading();
            
            // Cancelar request anterior
            if (this.currentRequest) {
                this.currentRequest.abort();
            }
            
            const controller = new AbortController();
            this.currentRequest = controller;
            
            const url = tipo === 'codigo' 
                ? `/cups/codigo?codigo=${encodeURIComponent(termino)}`
                : `/cups/buscar?q=${encodeURIComponent(termino)}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: controller.signal
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (tipo === 'codigo' && data.data) {
                    // Búsqueda exacta por código
                    this.seleccionarCups(data.data);
                    this.clearResults();
                } else if (tipo === 'nombre' && data.data) {
                    // Búsqueda por nombre - mostrar resultados
                    this.mostrarResultados(Array.isArray(data.data) ? data.data : [data.data]);
                }
            } else {
                this.mostrarError(data.error || 'Error en la búsqueda');
            }
            
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error buscando CUPS:', error);
                this.mostrarError('Error de conexión');
            }
        } finally {
            this.hideLoading();
            this.currentRequest = null;
        }
    }
    
    mostrarResultados(cups) {
        if (!this.resultsContainer || cups.length === 0) {
            this.clearResults();
            return;
        }
        
        let html = '';
        
        cups.forEach(cup => {
            html += `
                <div class="cups-result-item" data-uuid="${cup.uuid}" data-codigo="${cup.codigo}" data-nombre="${cup.nombre}">
                    <div class="cups-codigo">${cup.codigo}</div>
                    <div class="cups-nombre">${cup.nombre}</div>
                    ${cup.categoria ? `<div class="cups-categoria">${cup.categoria}</div>` : ''}
                </div>
            `;
        });
        
        this.resultsContainer.innerHTML = html;
        this.resultsContainer.style.display = 'block';
        
        // Agregar event listeners
        this.resultsContainer.querySelectorAll('.cups-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const cupsData = {
                    uuid: item.dataset.uuid,
                    codigo: item.dataset.codigo,
                    nombre: item.dataset.nombre
                };
                
                this.seleccionarCups(cupsData);
                this.clearResults();
            });
        });
    }
    
    seleccionarCups(cups) {
        this.selectedCups = cups;
        
        if (this.codigoInput) {
            this.codigoInput.value = cups.codigo;
        }
        
        if (this.nombreInput) {
            this.nombreInput.value = cups.nombre;
        }
        
        if (this.hiddenInput) {
            this.hiddenInput.value = cups.uuid;
        }
        
        // Trigger custom event
        this.triggerChangeEvent(cups);
    }
    
    triggerChangeEvent(cups) {
        const event = new CustomEvent('cupsSelected', {
            detail: cups
        });
        
        if (this.codigoInput) {
            this.codigoInput.dispatchEvent(event);
        }
        
        if (this.nombreInput) {
            this.nombreInput.dispatchEvent(event);
        }
    }
    
    clearResults() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = '';
            this.resultsContainer.style.display = 'none';
        }
    }
    
    clearCodigo() {
        if (this.codigoInput) {
            this.codigoInput.value = '';
        }
        
        if (this.hiddenInput) {
            this.hiddenInput.value = '';
        }
        
        this.selectedCups = null;
    }
    
    clearNombre() {
        if (this.nombreInput) {
            this.nombreInput.value = '';
        }
        
        if (this.hiddenInput) {
            this.hiddenInput.value = '';
        }
        
        this.selectedCups = null;
    }
    
    clearSearchTimeout() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = null;
        }
    }
    
    showLoading() {
        // Implementar indicador de carga
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = '<div class="cups-loading">Buscando...</div>';
            this.resultsContainer.style.display = 'block';
        }
    }
    
    hideLoading() {
        // Ocultar indicador de carga se maneja en mostrarResultados o clearResults
    }
    
    mostrarError(mensaje) {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = `<div class="cups-error">${mensaje}</div>`;
            this.resultsContainer.style.display = 'block';
            
            setTimeout(() => {
                this.clearResults();
            }, 3000);
        }
    }
    
    // Método público para limpiar todo
    clear() {
        this.clearCodigo();
        this.clearNombre();
        this.clearResults();
        this.selectedCups = null;
    }
    
    // Método público para obtener CUPS seleccionado
    getSelected() {
        return this.selectedCups;
    }
    
    // Método público para establecer CUPS
    setCups(cups) {
        this.seleccionarCups(cups);
    }
}

// Hacer disponible globalmente
window.CupsAutocomplete = CupsAutocomplete;
