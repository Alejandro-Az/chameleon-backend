Notas de implementación: parche de sesiones de autenticación en frontend

Archivos de referencia (repositorio frontend):
- src/lib/api/sessionsApi.ts
- src/components/profile/SessionsPanel.tsx
- src/pages/ProfilePage.tsx (si existe)

Resumen
- `Session.id` debe tratarse como `string` (ULID) en tipos y runtime.
- Las URLs deben usar `session.id` (string) al llamar `DELETE /api/v1/auth/sessions/{id}`.
- Variables de estado que guarden IDs de sesión (por ejemplo `revokingSessionId`) deben tiparse como `string | null`.

sessionsApi.ts (ejemplo)

- Cambiar request para usar `session.id` (string) al revocar:

Ejemplo (TypeScript):

```ts
export async function revokeSession(sessionId: string) {
  return api.delete(`/auth/sessions/${sessionId}`);
}
```

- Si antes se asumía número, actualizar tipos y llamadas.

SessionsPanel.tsx (cambios sugeridos)

- Usar `Session.id: string` en props y tipos.
- En mapeos de lista, usar `key={session.id}`.
- Al revocar, enviar `session.id` directamente.
- Manejar estado `revokingSessionId: string | null`.

Ejemplo (pseudo):

```ts
const [revokingSessionId, setRevokingSessionId] = useState<string | null>(null);

async function handleRevoke(session: Session) {
  setRevokingSessionId(session.id);
  try {
    await revokeSession(session.id);
    // refresh list
  } finally {
    setRevokingSessionId(null);
  }
}
```

Notas de despliegue
- Backend acepta únicamente ULID público para `/auth/sessions/{id}`.
- Actualizar interfaces TypeScript donde `Session.id` esté como `number` a `string`.
- Actualizar tests unitarios/integración que asuman ID numérico o hagan parseo a número.

Opcional: helper de migración
- Si el frontend tiene helpers de serialización de IDs, asegurar que acepten strings y nunca conviertan a número.

Si hace falta, se pueden generar diffs concretos para esos archivos del frontend.
