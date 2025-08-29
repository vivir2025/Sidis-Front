<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Registrar validación personalizada para sedes
        Validator::extend('exists_in_sedes', function ($attribute, $value, $parameters, $validator) {
            $rule = new \App\Rules\ExistsInSedes();
            return $rule->passes($attribute, $value);
        });

        Validator::replacer('exists_in_sedes', function ($message, $attribute, $rule, $parameters) {
            return 'La sede seleccionada no es válida.';
        });
    }
}
