/**
 * Price Footer Component
 * 
 * Shows calculated prices and action buttons
 */
import React from 'react'
import { Button } from '@/components/UI'
import type { PriceCalculation } from '@/types/orderForm'

interface PriceFooterProps {
  priceData: PriceCalculation | null
  isCalculating: boolean
  isSubmitting: boolean
  onCalculate: () => void
  onSubmit: (e: React.FormEvent) => void
  onCancel?: () => void
  overridePrice?: number
  onOverridePriceChange: (value: number | undefined) => void
}

export const PriceFooter: React.FC<PriceFooterProps> = ({
  priceData,
  isCalculating,
  isSubmitting,
  onCalculate,
  onSubmit,
  onCancel,
  overridePrice,
  onOverridePriceChange,
}) => {
  const [isOverrideEnabled, setIsOverrideEnabled] = React.useState(false)
  
  const formatPrice = (price: number | undefined): string => {
    if (!price && price !== 0) return '---'
    return price.toLocaleString('fa-IR')
  }
  
  const handleOverrideToggle = (enabled: boolean) => {
    setIsOverrideEnabled(enabled)
    if (!enabled) {
      onOverridePriceChange(undefined)
    }
  }
  
  const finalUnitPrice = overridePrice || priceData?.unit_price || 0
  const finalTotalPrice = finalUnitPrice && priceData ? finalUnitPrice * (priceData.total_price / priceData.unit_price) : 0
  
  return (
    <footer className="aof-footer">
      <div className="aof-price-bar">
        {/* Unit Price */}
        <div className="aof-price-item aof-price-unit">
          <span className="price-label">تک جلد:</span>
          <span className="price-value">{formatPrice(priceData?.unit_price)}</span>
          <span className="price-unit">تومان</span>
        </div>
        
        {/* Calculated Total Price */}
        <div className="aof-price-item aof-price-calculated">
          <span className="price-label">کل محاسبه:</span>
          <span className="price-value">{formatPrice(priceData?.total_price)}</span>
          <span className="price-unit">تومان</span>
        </div>
        
        {/* Final Price */}
        <div className="aof-price-item aof-price-final">
          <span className="price-label">نهایی:</span>
          <div className="price-breakdown">
            <div>
              <span className="price-sublabel">تک جلد:</span>
              <span className="price-value">{formatPrice(finalUnitPrice)}</span>
              <span className="price-unit">تومان</span>
            </div>
            <div>
              <span className="price-sublabel">کل:</span>
              <span className="price-value">{formatPrice(finalTotalPrice)}</span>
              <span className="price-unit">تومان</span>
            </div>
          </div>
        </div>
        
        {/* Override Unit Price */}
        <div className="aof-override">
          <label className="aof-override-toggle">
            <input
              type="checkbox"
              checked={isOverrideEnabled}
              onChange={(e) => handleOverrideToggle(e.target.checked)}
            />
            <span className="toggle-slider"></span>
            <span className="toggle-label">قیمت تک جلد دلخواه</span>
          </label>
          <input
            type="number"
            value={overridePrice || ''}
            onChange={(e) => onOverridePriceChange(e.target.value ? parseInt(e.target.value) : undefined)}
            className="aof-input aof-input-sm"
            placeholder="تومان"
            min={0}
            step={100}
            disabled={!isOverrideEnabled}
          />
          <small className="price-helper">قیمت کل = تک جلد × تیراژ</small>
        </div>
      </div>
      
      <div className="aof-actions">
        <Button
          type="button"
          onClick={onCalculate}
          disabled={isCalculating || isSubmitting}
          variant="secondary"
        >
          {isCalculating ? 'در حال محاسبه...' : '🧮 محاسبه'}
        </Button>
        
        <Button
          type="submit"
          onClick={onSubmit}
          disabled={isSubmitting || isCalculating}
          variant="primary"
        >
          {isSubmitting ? 'در حال ثبت...' : '✓ ثبت سفارش'}
        </Button>
        
        {onCancel && (
          <Button
            type="button"
            onClick={onCancel}
            disabled={isSubmitting}
            variant="ghost"
          >
            انصراف
          </Button>
        )}
      </div>
    </footer>
  )
}
