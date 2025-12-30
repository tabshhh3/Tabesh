/**
 * Admin Order Form API Service
 */
import { apiClient } from './api'
import type { ApiResponse } from '@/types'
import type { Customer, PriceCalculation, OrderFormData, FormConfig } from '@/types/orderForm'

export const adminOrderFormService = {
  /**
   * Search for customers
   */
  async searchCustomers(query: string): Promise<ApiResponse<Customer[]>> {
    return apiClient.get<Customer[]>('/admin/search-customers', { q: query })
  },

  /**
   * Create a new customer
   */
  async createCustomer(data: {
    mobile: string
    first_name: string
    last_name: string
  }): Promise<ApiResponse<Customer>> {
    return apiClient.post<Customer>('/admin/create-customer', data)
  },

  /**
   * Calculate order price
   */
  async calculatePrice(orderData: Partial<OrderFormData>): Promise<ApiResponse<PriceCalculation>> {
    return apiClient.post<PriceCalculation>('/calculate-price', orderData)
  },

  /**
   * Submit order
   */
  async submitOrder(orderData: OrderFormData): Promise<ApiResponse<{ order_id: number; order_number: string }>> {
    return apiClient.post<{ order_id: number; order_number: string }>('/admin/submit-order', orderData)
  },

  /**
   * Get form configuration (book sizes, paper types, etc.)
   */
  async getFormConfig(): Promise<ApiResponse<FormConfig>> {
    return apiClient.get<FormConfig>('/admin/form-config')
  },
}
