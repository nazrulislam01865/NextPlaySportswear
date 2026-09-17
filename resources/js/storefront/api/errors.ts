import type { AxiosError } from 'axios';
import type { ApiValidationErrors } from './types';

export class StorefrontApiError extends Error {
  constructor(
    message: string,
    public readonly status: number | null,
    public readonly code: string | null,
    public readonly errors: ApiValidationErrors,
    public readonly requestId: string | null,
  ) {
    super(message);
    this.name = 'StorefrontApiError';
  }
}

export function normalizeApiError(error: AxiosError<Record<string, any>>): StorefrontApiError {
  const payload = error.response?.data ?? {};
  return new StorefrontApiError(
    String(payload.message ?? 'Something went wrong. Please try again.'),
    error.response?.status ?? null,
    payload.code ? String(payload.code) : null,
    (payload.errors ?? {}) as ApiValidationErrors,
    payload.request_id ? String(payload.request_id) : null,
  );
}
