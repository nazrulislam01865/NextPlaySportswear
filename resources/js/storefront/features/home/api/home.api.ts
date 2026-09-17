import { apiClient } from '../../../api/client';
import { API_ENDPOINTS } from '../../../api/endpoints';
import type { ApiEnvelope } from '../../../api/types';
import type { HomePageData } from '../types/home.types';

export async function fetchHomePage(): Promise<HomePageData> {
  const response = await apiClient.get<ApiEnvelope<HomePageData>>(API_ENDPOINTS.home);
  return response.data.data;
}
