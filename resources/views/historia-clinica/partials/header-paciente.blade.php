<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-heartbeat me-2"></i>
                    Historia Clínica Cardiovascular
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">Información del Paciente</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Nombre:</strong></td>
                                <td>{{ $cita['paciente']['nombre_completo'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Documento:</strong></td>
                                <td>{{ $cita['paciente']['documento'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Fecha Nacimiento:</strong></td>
                                <td>{{ $cita['paciente']['fecha_nacimiento'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Sexo:</strong></td>
                                <td>{{ $cita['paciente']['sexo'] === 'M' ? 'Masculino' : 'Femenino' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-primary">Información de la Cita</h5>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Fecha:</strong></td>
                                <td>{{ $cita['fecha'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Hora:</strong></td>
                                <td>{{ $cita['hora'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Consultorio:</strong></td>
                                <td>{{ $cita['agenda']['consultorio'] ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Profesional:</strong></td>
                                <td>{{ $usuario['nombre_completo'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
