<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use App\Services\ApiService;

class ExistsInSedes implements Rule
{
    public function passes($attribute, $value)
    {
        $apiService = app(ApiService::class);
        $sedes = [];

        // Intentar obtener sedes actuales
        if ($apiService->isOnline()) {
            $response = $apiService->get('/master-data/sedes');
            if ($response['success']) {
                $sedes = $response['data']['data'] ?? $response['data'];
            }
        }

        // Si no hay sedes online, usar cache
        if (empty($sedes)) {
            $sedes = Cache::get('sedes_cache', []);
        }

        // Si no hay cache, usar sedes por defecto
        if (empty($sedes)) {
            $sedes = [
                ['id' => 1, 'nombre' => 'Cajibio'],
                ['id' => 2, 'nombre' => 'Piendamo'],
                ['id' => 3, 'nombre' => 'Morales']
            ];
        }

        // Verificar si el ID existe
        return collect($sedes)->pluck('id')->contains((int)$value);
    }

    public function message()
    {
        return 'La sede seleccionada no es válida.';
    }
}
