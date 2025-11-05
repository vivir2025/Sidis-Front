<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-clock me-2"></i>
            Horarios de Comida
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="20%">Tiempo de Comida</th>
                        <th width="15%">Hora</th>
                        <th width="65%">Observaciones / Alimentos Consumidos</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Desayuno --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-sun text-warning me-2"></i>
                            Desayuno
                        </td>
                        <td>
                            <input type="time" class="form-control" name="desayuno_hora" 
                                value="07:00">
                        </td>
                        <td>
                            <textarea class="form-control" name="desayuno_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>

                    {{-- Media Mañana --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-coffee text-brown me-2"></i>
                            Media Mañana
                        </td>
                        <td>
                            <input type="time" class="form-control" name="media_manana_hora" 
                                value="10:00">
                        </td>
                        <td>
                            <textarea class="form-control" name="media_manana_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>

                    {{-- Almuerzo --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-utensils text-success me-2"></i>
                            Almuerzo
                        </td>
                        <td>
                            <input type="time" class="form-control" name="almuerzo_hora" 
                                value="12:30">
                        </td>
                        <td>
                            <textarea class="form-control" name="almuerzo_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>

                    {{-- Media Tarde --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-apple-alt text-danger me-2"></i>
                            Media Tarde
                        </td>
                        <td>
                            <input type="time" class="form-control" name="media_tarde_hora" 
                                value="15:00">
                        </td>
                        <td>
                            <textarea class="form-control" name="media_tarde_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>

                    {{-- Cena --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-moon text-primary me-2"></i>
                            Cena
                        </td>
                        <td>
                            <input type="time" class="form-control" name="cena_hora" 
                                value="19:00">
                        </td>
                        <td>
                            <textarea class="form-control" name="cena_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>

                    {{-- Refrigerio Nocturno --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-cookie-bite text-secondary me-2"></i>
                            Refrigerio Nocturno
                        </td>
                        <td>
                            <input type="time" class="form-control" name="refrigerio_nocturno_hora" 
                                value="21:00">
                        </td>
                        <td>
                            <textarea class="form-control" name="refrigerio_nocturno_hora_observacion" rows="2" 
                                placeholder="Describa los alimentos consumidos..."></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Nota informativa --}}
        <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Nota:</strong> Haga clic en el campo de hora para seleccionar el horario deseado.
        </div>
    </div>
</div>

{{-- Estilos adicionales para mejorar la apariencia --}}
<style>
    /* Mejorar apariencia del input time */
    input[type="time"] {
        cursor: pointer;
        font-size: 1rem;
        padding: 0.5rem;
    }

    input[type="time"]::-webkit-calendar-picker-indicator {
        cursor: pointer;
        font-size: 1.2rem;
    }

    /* Hover effect en las filas */
    tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    /* Iconos de comida */
    .text-brown {
        color: #8B4513;
    }
</style>
