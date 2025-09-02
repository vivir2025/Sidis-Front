<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MasterDataService
{
    protected $offlineService;

    public function __construct(OfflineService $offlineService)
    {
        $this->offlineService = $offlineService;
    }

    /**
     * ✅ MAPEAR UUID DE PROCESO A ID NUMÉRICO
     */
    public function getProcesoIdByUuid(string $uuid): ?int
    {
        try {
            // ✅ INTENTAR DESDE SQLite PRIMERO
            if ($this->offlineService->isSQLiteAvailable()) {
                $proceso = DB::connection('offline')
                    ->table('procesos')
                    ->where('uuid', $uuid)
                    ->first();
                
                if ($proceso && isset($proceso->id)) {
                    return (int) $proceso->id;
                }
            }

            // ✅ FALLBACK: Buscar en JSON
            $masterData = $this->offlineService->getMasterDataOffline();
            
            if (isset($masterData['procesos'])) {
                foreach ($masterData['procesos'] as $proceso) {
                    if ($proceso['uuid'] === $uuid) {
                        return isset($proceso['id']) ? (int) $proceso['id'] : null;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error mapeando proceso UUID a ID', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ MAPEAR UUID DE BRIGADA A ID NUMÉRICO
     */
    public function getBrigadaIdByUuid(string $uuid): ?int
    {
        try {
            // ✅ INTENTAR DESDE SQLite PRIMERO
            if ($this->offlineService->isSQLiteAvailable()) {
                $brigada = DB::connection('offline')
                    ->table('brigadas')
                    ->where('uuid', $uuid)
                    ->first();
                
                if ($brigada && isset($brigada->id)) {
                    return (int) $brigada->id;
                }
            }

            // ✅ FALLBACK: Buscar en JSON
            $masterData = $this->offlineService->getMasterDataOffline();
            
            if (isset($masterData['brigadas'])) {
                foreach ($masterData['brigadas'] as $brigada) {
                    if ($brigada['uuid'] === $uuid) {
                        return isset($brigada['id']) ? (int) $brigada['id'] : null;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error mapeando brigada UUID a ID', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ MAPEAR ID NUMÉRICO DE PROCESO A UUID
     */
    public function getProcesoUuidById(int $id): ?string
    {
        try {
            if ($this->offlineService->isSQLiteAvailable()) {
                $proceso = DB::connection('offline')
                    ->table('procesos')
                    ->where('id', $id)
                    ->first();
                
                return $proceso ? $proceso->uuid : null;
            }

            $masterData = $this->offlineService->getMasterDataOffline();
            
            if (isset($masterData['procesos'])) {
                foreach ($masterData['procesos'] as $proceso) {
                    if (isset($proceso['id']) && (int) $proceso['id'] === $id) {
                        return $proceso['uuid'];
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error mapeando proceso ID a UUID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ MAPEAR ID NUMÉRICO DE BRIGADA A UUID
     */
    public function getBrigadaUuidById(int $id): ?string
    {
        try {
            if ($this->offlineService->isSQLiteAvailable()) {
                $brigada = DB::connection('offline')
                    ->table('brigadas')
                    ->where('id', $id)
                    ->first();
                
                return $brigada ? $brigada->uuid : null;
            }

            $masterData = $this->offlineService->getMasterDataOffline();
            
            if (isset($masterData['brigadas'])) {
                foreach ($masterData['brigadas'] as $brigada) {
                    if (isset($brigada['id']) && (int) $brigada['id'] === $id) {
                        return $brigada['uuid'];
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error mapeando brigada ID a UUID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
