# M5 — Policy Center

El módulo Policy Center (Settings) centraliza la configuración dinámica del sistema, permitiendo overrides en caliente desde la base de datos sin necesidad de redesplegar el aplicativo.

## 1. Precedencia
El sistema resuelve el valor final de cada política en este orden (de mayor a menor precedencia):
1. **ENV**: Solo para features o variables de solo lectura marcadas como `source: env`.
2. **DB Override**: Si existe un registro en `policy_settings`.
3. **Default**: El valor base definido en `config/kaan_policies.php`.

## 2. Modo Degradado (Anti-fragilidad)
Si la base de datos o el sistema de caché fallan durante el middleware `ApplyPolicyOverrides`, la API **no se rompe**:
- No se aplican los overrides.
- El sistema opera con los defaults (config/registry original).
- Se loguea un warning con rate-limit para evitar spam.

## 3. Redacción de información sensible (`sensitive=true`)
Las policies marcadas como `sensitive` (por ejemplo, keys de terceros):
- **Cifrado en reposo**: Se guardan en la columna `value_encrypted` de la base de datos usando la App Key.
- **Redacción en API**: Nunca se devuelven los valores reales a través de los endpoints de la API (ni en `list` ni en `show`); el output muestra un valor redactado y el metadato `redacted: true`.

## 4. CLI Helper: `runWithConfigOverrides`
Para garantizar consistencia entre las peticiones web y los procesos asíncronos o de CLI (que corren bajo Workers o Schedulers), se utiliza el helper del `PolicyService`:

```php
app(\App\Services\Policy\PolicyService::class)->runWithConfigOverrides(function() {
    // Código seguro con defaults y overrides aplicados atómicamente.
});
```
De esta manera se asegura que las configuraciones parchadas no permanezcan "leaked" en entornos long-running (Octane/RoadRunner/Workers).
