/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { computed, type Ref } from 'vue'
import { InquiryIcons, InquiryGeneralIcons, StatusIcons } from '../../utils/icons.ts'
import { useSessionStore } from '../stores/session.ts'

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

/**
 */
export function getInquiryIcon(item: InquiryFamily | InquiryType | null) {
  if (!item) return InquiryGeneralIcons.activity

  const iconName = item?.icon?.toLowerCase() ||
    ('inquiry_type' in item ? 'accountgroup' : 'filedocumentedit')

  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
}

/**
 */
export function getInquiryLabel(item: InquiryFamily | InquiryType | null, fallback: string = ''): string {
  return item?.label || fallback
}

/**
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

export const InquiryTypesUI = {
  proposal: {
    label: t('agora', 'Proposal'),
    icon: InquiryIcons.proposal,
  },
  debate: {
    label: 'Debate',
    icon: InquiryIcons.debate,
  },
  petition: {
    label: 'Petition',
    icon: InquiryIcons.petition,
  },
  project: {
    label: 'Project',
    icon: InquiryIcons.project,
  },
  grievance: {
    label: 'Grievance',
    icon: InquiryIcons.grievance,
  },
  suggestion: {
    label: 'Suggestion',
    icon: InquiryIcons.suggestion,
  },
  official: {
    label: 'Official Response',
    icon: InquiryIcons.official,
  },
}

export const InquiryTypeValues = {
  PROPOSAL: 'proposal',
  PROJECT: 'project',
  GRIEVANCE: 'grievance',
  DEBATE: 'debate',
  PETITION: 'petition',
  SUGGESTION: 'suggestion',
  OFFICIAL: 'official',
} as const
