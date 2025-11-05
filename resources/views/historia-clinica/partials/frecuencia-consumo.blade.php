<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar me-2"></i>
            Frecuencia de Consumo de Alimentos
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="25%">Grupo de Alimentos</th>
                        <th width="25%">Frecuencia</th>
                        <th width="50%">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Lácteos --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-cheese text-warning me-2"></i>
                            Lácteos
                        </td>
                        <td>
                            <select class="form-select" name="lacteo">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="lacteo_observacion" 
                                placeholder="Ej: Leche descremada, queso bajo en grasa...">
                        </td>
                    </tr>

                    {{-- Huevo --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-egg text-warning me-2"></i>
                            Huevo
                        </td>
                        <td>
                            <select class="form-select" name="huevo">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="huevo_observacion" 
                                placeholder="Ej: Cocido, revuelto, cantidad...">
                        </td>
                    </tr>

                    {{-- Embutidos --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-bacon text-danger me-2"></i>
                            Embutidos
                        </td>
                        <td>
                            <select class="form-select" name="embutido">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="embutido_observacion" 
                                placeholder="Ej: Jamón, salchicha, chorizo...">
                        </td>
                    </tr>

                    {{-- Carnes --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-drumstick-bite text-danger me-2"></i>
                            Carnes
                        </td>
                        <td>
                            <div class="mb-2">
                                <label class="form-label small">Carne Roja</label>
                                <select class="form-select form-select-sm" name="carne_roja">
                                    <option value="">Seleccione...</option>
                                    <option value="DIARIO">Diario</option>
                                    <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                    <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                    <option value="OCASIONAL">Ocasional</option>
                                    <option value="NUNCA">Nunca</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Carne Blanca</label>
                                <select class="form-select form-select-sm" name="carne_blanca">
                                    <option value="">Seleccione...</option>
                                    <option value="DIARIO">Diario</option>
                                    <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                    <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                    <option value="OCASIONAL">Ocasional</option>
                                    <option value="NUNCA">Nunca</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label small">Vísceras</label>
                                <select class="form-select form-select-sm" name="carne_vicera">
                                    <option value="">Seleccione...</option>
                                    <option value="DIARIO">Diario</option>
                                    <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                    <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                    <option value="OCASIONAL">Ocasional</option>
                                    <option value="NUNCA">Nunca</option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <textarea class="form-control" name="carne_observacion" rows="3" 
                                placeholder="Detalles sobre preparación, cantidad..."></textarea>
                        </td>
                    </tr>

                    {{-- Leguminosas --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-seedling text-success me-2"></i>
                            Leguminosas
                        </td>
                        <td>
                            <select class="form-select" name="leguminosas">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="leguminosas_observacion" 
                                placeholder="Ej: Frijoles, lentejas, garbanzos...">
                        </td>
                    </tr>

                    {{-- Frutas --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-apple-alt text-danger me-2"></i>
                            Frutas
                        </td>
                        <td>
                            <div class="mb-2">
                                <label class="form-label small">Jugo</label>
                                <select class="form-select form-select-sm" name="frutas_jugo">
                                    <option value="">Seleccione...</option>
                                    <option value="DIARIO">Diario</option>
                                    <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                    <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                    <option value="OCASIONAL">Ocasional</option>
                                    <option value="NUNCA">Nunca</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label small">Porción</label>
                                <select class="form-select form-select-sm" name="frutas_porcion">
                                    <option value="">Seleccione...</option>
                                    <option value="DIARIO">Diario</option>
                                    <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                    <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                    <option value="OCASIONAL">Ocasional</option>
                                    <option value="NUNCA">Nunca</option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <textarea class="form-control" name="frutas_observacion" rows="2" 
                                placeholder="Tipos de frutas, cantidad..."></textarea>
                        </td>
                    </tr>

                    {{-- Verduras y Hortalizas --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-carrot text-warning me-2"></i>
                            Verduras y Hortalizas
                        </td>
                        <td>
                            <select class="form-select" name="verduras_hortalizas">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="vh_observacion" 
                                placeholder="Tipos de verduras, preparación...">
                        </td>
                    </tr>

                    {{-- Cereales --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-bread-slice text-warning me-2"></i>
                            Cereales
                        </td>
                        <td>
                            <select class="form-select" name="cereales">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="cereales_observacion" 
                                placeholder="Ej: Arroz, pasta, pan, avena...">
                        </td>
                    </tr>

                    {{-- RTP (Raíces, Tubérculos, Plátanos) --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-leaf text-success me-2"></i>
                            RTP (Raíces/Tubérculos/Plátanos)
                        </td>
                        <td>
                            <select class="form-select" name="rtp">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="rtp_observacion" 
                                placeholder="Ej: Papa, yuca, plátano...">
                        </td>
                    </tr>

                    {{-- Azúcar y Dulces --}}
                    <tr>
                        <td class="fw-bold">
                            <i class="fas fa-candy-cane text-danger me-2"></i>
                            Azúcar y Dulces
                        </td>
                        <td>
                            <select class="form-select" name="azucar_dulce">
                                <option value="">Seleccione...</option>
                                <option value="DIARIO">Diario</option>
                                <option value="3-4_VECES_SEMANA">3-4 veces/semana</option>
                                <option value="1-2_VECES_SEMANA">1-2 veces/semana</option>
                                <option value="OCASIONAL">Ocasional</option>
                                <option value="NUNCA">Nunca</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="ad_observacion" 
                                placeholder="Tipos de dulces, cantidad...">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
