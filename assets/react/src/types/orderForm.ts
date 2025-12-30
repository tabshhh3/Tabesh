/**
 * Type definitions for Admin Order Form
 */

export interface OrderFormData {
  // Customer info
  user_id: number
  customer_type: 'existing' | 'new'
  
  // New customer fields
  new_mobile?: string
  new_first_name?: string
  new_last_name?: string
  
  // Order details
  book_title: string
  book_size: string
  paper_type: string
  paper_weight: string
  print_type: string
  binding_type: string
  license_type: string
  quantity: number
  page_count_total?: number
  page_count_color?: number
  page_count_bw?: number
  cover_paper_weight?: string
  lamination_type?: string
  extras?: string[]
  notes?: string
  
  // Price info
  override_unit_price?: number
  
  // SMS options
  send_registration_sms?: boolean
  send_order_sms?: boolean
}

export interface FormConfig {
  book_sizes: string[]
  paper_types: Record<string, string[]>
  print_types: string[]
  binding_types: string[]
  license_types: string[]
  cover_paper_weights: string[]
  lamination_types: string[]
  extras: string[]
  min_quantity: number
  max_quantity: number
  quantity_step: number
}

export interface Customer {
  ID: number
  display_name: string
  user_email: string
  user_login: string
  billing_phone?: string
  billing_state?: string
}

export interface PriceCalculation {
  unit_price: number
  total_price: number
  breakdown?: {
    paper_cost: number
    print_cost: number
    binding_cost: number
    cover_cost: number
    extras_cost: number
  }
}
