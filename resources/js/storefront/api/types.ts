export interface ApiEnvelope<T> {
  data: T;
  meta?: Record<string, unknown>;
  request_id?: string;
}

export interface ApiValidationErrors {
  [field: string]: string[];
}
