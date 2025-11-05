<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-calendar-day me-2"></i>
            Recordatorio de 24 Horas
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            <i class="fas fa-info-circle me-1"></i>
            Registre todos los alimentos y bebidas consumidos en las últimas 24 horas
        </p>

        <div class="row">
            {{-- Antecedentes Básicos --}}
           
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Hábito Intestinal</label>
                <textarea class="form-control" name="habito_intestinal" rows="2" 
                    placeholder="Describa el hábito intestinal..."></textarea>
            </div>

            {{-- Desayuno --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold text-primary">
                    <i class="fas fa-sun me-1"></i> Desayuno
                </label>
                <textarea class="form-control" name="comida_desayuno" rows="3" 
                    placeholder="Ej: 1 taza de café con leche, 2 huevos revueltos, 2 rebanadas de pan integral..."></textarea>
            </div>

            {{-- Media Mañana --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold text-warning">
                    <i class="fas fa-apple-alt me-1"></i> Media Mañana
                </label>
                <textarea class="form-control" name="comida_medio_desayuno" rows="3" 
                    placeholder="Ej: 1 manzana, 1 yogurt griego..."></textarea>
            </div>

            {{-- Almuerzo --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold text-success">
                    <i class="fas fa-drumstick-bite me-1"></i> Almuerzo
                </label>
                <textarea class="form-control" name="comida_almuerzo" rows="3" 
                    placeholder="Ej: 1 plato de arroz, pechuga de pollo a la plancha, ensalada verde..."></textarea>
            </div>

            {{-- Media Tarde --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold text-info">
                    <i class="fas fa-cookie-bite me-1"></i> Media Tarde
                </label>
                <textarea class="form-control" name="comida_medio_almuerzo" rows="3" 
                    placeholder="Ej: 1 taza de té, galletas integrales..."></textarea>
            </div>

            {{-- Cena --}}
            <div class="col-md-12 mb-3">
                <label class="form-label fw-bold text-danger">
                    <i class="fas fa-moon me-1"></i> Cena
                </label>
                <textarea class="form-control" name="comida_cena" rows="3" 
                    placeholder="Ej: Sopa de verduras, pescado al horno, ensalada..."></textarea>
            </div>
        </div>
    </div>
</div>
