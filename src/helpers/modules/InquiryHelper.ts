/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { computed, type Ref } from 'vue'
import { InquiryGeneralIcons, StatusIcons } from '../../utils/icons.ts'

export interface InquiryFamily {
  id: number | string
  label: string
  inquiry_type: string
  icon?: string
  description?: string
}

export interface InquiryType {
  id: number | string
  inquiry_type: string
  label: string
  family: string
  icon?: string
  description?: string
  isOption?: number
}

export async function confirmAction(message: string): Promise<boolean> {
  return Promise.resolve(window.confirm(message))
}

// ============================================================================
// FONCTIONS FOR GENERIC FAMILY AND TYPES
// ============================================================================

/**
 * Get inquiry item data (works for both families and types)
 */
export function getInquiryItemData(item: InquiryFamily | InquiryType | null, fallbackLabel: string = '') {
  if (!item) {
    return {
      icon: InquiryGeneralIcons.activity,
      label: fallbackLabel,
      description: ''
    }
  }

  const iconName = item?.icon?.toLowerCase() || 
    ('inquiry_type' in item ? 'accountgroup' : 'filedocumentedit')

  return {
    icon: InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity,
    label: item.label || fallbackLabel,
    description: item.description || ''
  }
}

/**
 * Get inquiry type data from type string
 */
export function getInquiryTypeData(inquiryType: string, inquiryTypes: InquiryType[], fallbackLabel: string = '') {
  const typeInfo = inquiryTypes.find(t => t.inquiry_type === inquiryType)
  return getInquiryItemData(typeInfo, fallbackLabel || inquiryType)
}

// ============================================================================
// FONCTIONS FOR INQUIRY TYPES
// ============================================================================

/**
 * Get filtered inquiry types by family
 */
export function getFilteredInquiryTypes(
  selectedFamily: Ref<string | null>,
  inquiryFamilies: InquiryFamily[],
  inquiryTypes: InquiryType[],
  isOptionFilter: number = 0
) {
  return computed(() => {
    if (!selectedFamily.value) return []

    const family = inquiryFamilies.find(f => f.id.toString() === selectedFamily.value)
    if (!family) return []

    return inquiryTypes.filter(type =>
      type.family === family.inquiry_type && type.isOption === isOptionFilter
    ) || []
  })
}

/**
 * Group inquiry types by family
 */
export function getInquiryTypesByFamily(inquiryTypes: InquiryType[]) {
  const grouped: Record<string, InquiryType[]> = {}

  inquiryTypes.forEach(type => {
    const familyKey = type.family
    if (!grouped[familyKey]) {
      grouped[familyKey] = []
    }
    grouped[familyKey].push(type)
  })

  return grouped
}

/**
 * Get available transformation types for an inquiry type based on transform_response field
 */
export function getAvailableTransformTypes(inquiryType: string, inquiryTypes: InquiryType[]): InquiryType[] {
  const currentType = inquiryTypes.find(t => t.inquiry_type === inquiryType)
  if (!currentType || !currentType.allowed_transformation) return []

  // Parse allowed_response if it's a string (JSON)
  let allowedTransforms: string[] = []
  if (typeof currentType.allowed_transformation === 'string') {
    try {
      allowedTransforms = JSON.parse(currentType.allowed_transformation)
    } catch {
      allowedTransforms = []
    }
  } else if (Array.isArray(currentType.allowed_transformation)) {
    allowedTransforms = currentType.allowed_transformation
  }

  // Filter inquiry types that are in allowed_response
  return inquiryTypes.filter(type =>
    allowedTransforms.includes(type.inquiry_type) && type.isOption === 0
  )
}

/**
 * Get available response types for an inquiry type based on allowed_response field
 */
export function getAvailableResponseTypes(inquiryType: string, inquiryTypes: InquiryType[]): InquiryType[] {
  const currentType = inquiryTypes.find(t => t.inquiry_type === inquiryType)
  if (!currentType || !currentType.allowed_response) return []

  // Parse allowed_response if it's a string (JSON)
  let allowedResponses: string[] = []
  if (typeof currentType.allowed_response === 'string') {
    try {
      allowedResponses = JSON.parse(currentType.allowed_response)
    } catch {
      allowedResponses = []
    }
  } else if (Array.isArray(currentType.allowed_response)) {
    allowedResponses = currentType.allowed_response
  }

  // Filter inquiry types that are in allowed_response
  return inquiryTypes.filter(type =>
    allowedResponses.includes(type.inquiry_type) && type.isOption === 0
  )
}


/**
 * Get inquiry types for specific family
 */
export function getInquiryTypesForFamily(
  familyInquiryType: string,
  inquiryTypesByFamily: Record<string, InquiryType[]>,
  isOptionFilter: number = 0
) {
  const types = inquiryTypesByFamily[familyInquiryType] || []
  return types.filter(type => type.isOption === isOptionFilter)
}

/**
 * Count inquiry types by family
 */
export function countInquiryTypesByFamily(
  familyInquiryType: string,
  inquiryTypes: InquiryType[],
  isOptionFilter: number = 0
): number {
  return inquiryTypes.filter(type =>
    type.family === familyInquiryType && type.isOption === isOptionFilter
  ).length
}

/**
 * Get available inquiry types for creation (filter out official and suggestion)
 */
export function getAvailableInquiryTypesForCreation(inquiryTypes: InquiryType[]): InquiryType[] {
  return inquiryTypes.filter(type => 
    !['official', 'suggestion'].includes(type.inquiry_type)
  )
}

/**
 * Get inquiry type options for radio/select components
 */
export function getInquiryTypeOptions(inquiryTypes: InquiryType[]) {
  return inquiryTypes.map(type => ({
    value: type.inquiry_type,
    label: type.label,
    description: type.description,
  }))
}

/**
 * Get family by inquiry type
 */
export function getFamilyByInquiryType(inquiryType: string, inquiryTypes: InquiryType[], inquiryFamilies: InquiryFamily[]): InquiryFamily | null {
  const typeInfo = inquiryTypes.find(t => t.inquiry_type === inquiryType)
  if (!typeInfo) return null
  
  return inquiryFamilies.find(family => family.inquiry_type === typeInfo.family) || null
}
