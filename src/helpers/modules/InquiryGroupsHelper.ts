/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { computed, type Ref } from 'vue'
import { InquiryGeneralIcons, StatusIcons } from '../../utils/icons.ts'
import type { useInquiryGroupsStore } from '../../stores/inquiryGroups.ts'
import type { useAppSettingsStore } from '../../stores/appSettings.ts'
import type { InquiryStatus } from '../../Types/index.ts'

export interface InquiryFamily {
  id: number | string
  label: string
  group_type: string
  icon?: string
  description?: string
}

export interface InquiryGroupType {
  id: number | string
  group_type: string
  label: string
  family: string
  icon?: string
  description?: string
  allowed_response?: string | string[]
  allowed_transformation?: string | string[]
  fields?: string | string[]
}

export async function confirmAction(message: string): Promise<boolean> {
  return Promise.resolve(window.confirm(message))
}

// ============================================================================
// FONCTIONS FOR GENERIC FAMILY AND TYPES
// ============================================================================

/**
 * Get inquiry item data (works for both families and types)
 * @param item
 * @param fallbackLabel
 */
export function getInquiryGroupsItemData(item: InquiryFamily | InquiryGroupType | null, fallbackLabel: string = '') {
  if (!item) {
    return {
      icon: InquiryGeneralIcons.Activity,
      label: fallbackLabel,
      description: ''
    }
  }

  const iconName = item?.icon || 
    ('group_type' in item ? 'AccountGroup' : 'FileDocumentEdit')

  return {
    icon: InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.Activity,
    label: item.label || fallbackLabel,
    description: item.description || ''
  }
}

/**
 * Get inquiry type data from type string
 * @param inquiryGroupType
 * @param inquiryGroupTypes
 * @param fallbackLabel
 */
export function getInquiryGroupTypeData(inquiryGroupType: string, inquiryGroupTypes: InquiryGroupsType[], fallbackLabel: string = '') {
  const typeInfo = inquiryGroupTypes.find(t => t.group_type === inquiryGroupType)
  return getInquiryGroupsItemData(typeInfo, fallbackLabel || inquiryGroupType)
}

// ============================================================================
// FONCTIONS FOR INQUIRY TYPES
// ============================================================================

/**
 * Get filtered inquiry types by family
 * @param selectedFamily
 * @param inquiryFamilies
 * @param inquiryGroupTypes
 */
export function getFilteredInquiryGroupTypes(
  selectedFamily: Ref<string | null>,
  inquiryFamilies: InquiryFamily[],
  inquiryGroupTypes: InquiryGroupType[],
) {
  return computed(() => {
    if (!selectedFamily.value) return []

    const family = inquiryFamilies.find(f => f.id.toString() === selectedFamily.value)
    if (!family) return []

    return inquiryGroupTypes.filter(type =>
      type.family === family.group_type 
    ) || []
  })
}

/**
 * Group inquiry types by family
 * @param inquiryGroupTypes
 */
export function getInquiryGroupTypesByFamily(inquiryGroupTypes: InquiryGroupsType[]) {
  const grouped: Record<string, InquiryGroupType[]> = {}

  inquiryGroupTypes.forEach(type => {
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
 * @param inquiryGroupType
 * @param inquiryGroupTypes
 */
export function getAvailableTransformTypes(inquiryGroupType: string, inquiryGroupTypes: InquiryGroupType[]): InquiryGroupsType[] {
  const currentType = inquiryGroupTypes.find(t => t.group_type === inquiryGroupType)
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
  return inquiryGroupTypes.filter(type =>
    allowedTransforms.includes(type.group_type)
  )
}

/**
 * Get available fields for an inquiry type based on fields
 * @param inquiryGroupType
 * @param inquiryGroupTypes
 */
export function getAvailableFields(inquiryGroupType: string, inquiryGroupTypes: InquiryGroupType[]): string[] {
  const currentType = inquiryGroupTypes.find(t => t.group_type === inquiryGroupType)
  if (!currentType || !currentType.fields) return []
  
  // Parse fields if it's a string (JSON)
  let fields: string[] = []
  if (typeof currentType.fields === 'string') {
    try {
      fields = JSON.parse(currentType.fields)
    } catch {
      fields = []
    }
  } else if (Array.isArray(currentType.fields)) {
    fields = currentType.fields
  }
  
  return fields
}

/**
 * Get available response types for an inquiry type based on allowed_response field
 * @param inquiryGroupType
 * @param inquiryGroupTypes
 */
export function getAvailableResponseTypes(inquiryGroupType: string, inquiryGroupTypes: InquiryGroupType[]): InquiryGroupsType[] {
  const currentType = inquiryGroupTypes.find(t => t.group_type === inquiryGroupType)
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
  return inquiryGroupTypes.filter(type =>
    allowedResponses.includes(type.group_type)
  )
}

/**
 * Get available inquiry types for creation (filter out official and suggestion)
 * @param inquiryGroupTypes
 */
export function getAvailableInquiryGroupTypesForCreation(inquiryGroupTypes: InquiryGroupsType[]): InquiryGroupsType[] {
  return inquiryGroupTypes.filter(type =>
    !['official', 'suggestion'].includes(type.group_type)
  )
}

/**
 * Check if inquiry has final status based on appSettings.inquiryStatusTab
 * @param inquiryStore
 * @param appSettings
 */
export function isInquiryGroupsFinalStatus(inquiryStore: ReturnType<typeof useInquiryGroupsStore>, appSettings: ReturnType<typeof useAppSettingsStore>): boolean {
  if (!inquiryStore?.type || !inquiryStore?.status?.inquiryStatus || !appSettings?.inquiryStatusTab) {
    return false
  }

  const inquiryGroupType = inquiryStore.type
  const currentStatus = inquiryStore.status.inquiryStatus

  // Find status configuration for this inquiry type and status
  const statusConfig = appSettings.inquiryStatusTab.find((status: InquiryGroupsStatus) => 
    status.inquiryGroupType === inquiryGroupType && status.statusKey === currentStatus
  )

  if (!statusConfig) {
    return false
  }

  return statusConfig.isFinal === true
}

/**
 * Get inquiry types for specific family
 * @param familyInquiryGroupType
 * @param inquiryGroupTypesByFamily
 */
export function getInquiryGroupTypesForFamily(
  familyInquiryType: string,
  inquiryGroupTypesByFamily: Record<string, InquiryGroupType[]>,
) {
  const types = inquiryGroupTypesByFamily[familyInquiryGroupType] || []
  return types
}

/**
 * Count inquiry types by family
 * @param familyInquiryGroupType
 * @param inquiryGroupTypes
 */
export function countInquiryGroupTypesByFamily(
  familyInquiryGroupType: string,
  inquiryGroupTypes: InquiryGroupType[],
): number {
  return inquiryGroupTypes.filter(type =>
    type.family === familyInquiryGroupType
  ).length
}

/**
 * Get inquiry type options for radio/select components
 * @param inquiryGroupTypes
 */
export function getInquiryGroupTypeOptions(inquiryGroupTypes: InquiryGroupsType[]) {
  return inquiryGroupTypes.map(type => ({
    value: type.group_type,
    label: type.label,
    description: type.description,
  }))
}

/**
 * Get family by inquiry type
 * @param inquiryGroupType
 * @param inquiryGroupTypes
 * @param inquiryFamilies
 */
export function getFamilyByInquiryGroupType(inquiryGroupType: string, inquiryGroupTypes: InquiryGroupsType[], inquiryFamilies: InquiryFamily[]): InquiryFamily | null {
  const typeInfo = inquiryGroupTypes.find(t => t.group_type === inquiryGroupType)
  if (!typeInfo) return null
  
  return inquiryFamilies.find(family => family.group_type === typeInfo.family) || null
}
