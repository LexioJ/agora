/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { ref, computed, type Ref } from 'vue'
import { toRaw } from 'vue'
import { useSessionStore } from '../../stores/session'
import { useInquiryStore } from '../../stores/inquiry'
import { BaseEntry } from '../../Types/index.ts'
import { createPermissionContextForContent, ContentType } from '../../utils/permissions.ts'

/**
 * Gestionnaire pour les catégories et localisations hiérarchiques
 */
export function useHierarchicalSelect(inquiryStore: ReturnType<typeof useInquiryStore>) {
  const sessionStore = useSessionStore()
  
  // Build hierarchy for location and category dropdowns
  function buildHierarchy(list: BaseEntry[], parentId = 0, depth = 0): BaseEntry[] {
    if (!Array.isArray(list)) return []
    return list
      .filter((item) => item?.parentId === parentId)
      .map((item) => {
        const children = buildHierarchy(list, item.id, depth + 1)
        return {
          ...item,
          depth,
          children,
        }
      })
      .flatMap((item) => [item, ...item.children])
  }

  const hierarchicalLocation = computed(() => {
    if (!Array.isArray(sessionStore.appSettings.locationTab)) return []
    return buildHierarchy(sessionStore.appSettings.locationTab).map((item) => ({
      value: item.id,
      label: `${'— '.repeat(item.depth ?? 0)}${item.name ?? '[no name]'}`,
      original: item,
    }))
  })

  const hierarchicalCategory = computed(() => {
    if (!Array.isArray(sessionStore.appSettings.categoryTab)) return []
    return buildHierarchy(sessionStore.appSettings.categoryTab).map((item) => ({
      value: item.id,
      label: `${'— '.repeat(item.depth ?? 0)}${item.name ?? '[no name]'}`,
      original: item,
    }))
  })

  // Get hierarchy path for location and category display
  function getHierarchyPath(items: BaseEntry[], targetId: number): string {
    const itemMap: Record<number, BaseEntry> = {}

    items.forEach((item) => {
      itemMap[item.id] = item
    })

    if (!itemMap[targetId]) {
      return 'ID not found'
    }

    function buildPath(item: BaseEntry): string {
      if (item.parentId === 0) {
        return item.name
      }
      const parent = itemMap[item.parentId]
      if (parent) {
        return `${buildPath(parent)} -> ${item.name}`
      }
      return item.name
    }

    return buildPath(itemMap[targetId])
  }

  return {
    hierarchicalLocation,
    hierarchicalCategory,
    getHierarchyPath
  }
}

/**
 * Gestionnaire pour la sélection de catégorie et localisation
 */
export function useCategoryLocationSelection(inquiryStore: ReturnType<typeof useInquiryStore>) {
  const { hierarchicalLocation, hierarchicalCategory } = useHierarchicalSelect(inquiryStore)
  
  const selectedCategory = ref(inquiryStore.categoryId || 0)
  const selectedLocation = ref(inquiryStore.locationId || 0)

  // Watchers for location and category
  const setupCategoryLocationWatchers = () => {
    const locationStop = watch(
      selectedLocation,
      (newVal) => {
        const rawValue = toRaw(newVal)
        if (rawValue) {
          inquiryStore.locationId = rawValue.value
        }
      },
      { deep: true }
    )

    const categoryStop = watch(
      selectedCategory,
      (newVal) => {
        const rawValue = toRaw(newVal)
        if (rawValue) {
          inquiryStore.categoryId = rawValue.value
        }
      },
      { deep: true }
    )

    return { locationStop, categoryStop }
  }

  // Initialize location and category
  const initializeCategoryLocation = () => {
    // Initialize location
    const locations = hierarchicalLocation.value
    if (locations.length) {
      if (inquiryStore.locationId === 0) {
        selectedLocation.value = locations[0]
        inquiryStore.locationId = locations[0].value
      } else {
        const selected = locations.find((loc) => loc.value === inquiryStore.locationId)
        selectedLocation.value = selected || locations[0]
        inquiryStore.locationId = selected?.value || locations[0].value
      }
    }

    // Initialize category
    const categories = hierarchicalCategory.value
    if (categories.length) {
      if (inquiryStore.categoryId === 0) {
        selectedCategory.value = categories[0]
        inquiryStore.categoryId = categories[0].value
      } else {
        const selected = categories.find((loc) => loc.value === inquiryStore.categoryId)
        selectedCategory.value = selected || categories[0]
        inquiryStore.categoryId = selected?.value || categories[0].value
      }
    }
  }

  return {
    selectedCategory,
    selectedLocation,
    hierarchicalLocation,
    hierarchicalCategory,
    setupCategoryLocationWatchers,
    initializeCategoryLocation
  }
}

/**
 * Gestionnaire pour les permissions et états de lecture seule
 */
export function useInquiryPermissions(inquiryStore: ReturnType<typeof useInquiryStore>) {
  const sessionStore = useSessionStore()
  
  const context = computed(() => createPermissionContextForContent(
    ContentType.Inquiry,
    inquiryStore.owner.id,
    inquiryStore.configuration.access === 'public',
    inquiryStore.status.isLocked,
    inquiryStore.status.isExpired,
    inquiryStore.status.deletionDate > 0,
    inquiryStore.status.isArchived,
    inquiryStore.inquiryGroups.length > 0,
    inquiryStore.inquiryGroups,
    inquiryStore.type
  ))

  const isReadonly = computed(() => {
    const user = sessionStore.currentUser
    if (!user) return true
    return !canEdit(context.value)
  })

  const isReadonlyDescription = ref(true)

  const setupDescriptionReadonly = () => {
    watch(
      () => inquiryStore.type,
      (newType) => {
        if (newType === 'debate') {
          isReadonlyDescription.value = false
        } else {
          isReadonlyDescription.value = isReadonly.value
        }
      },
      { immediate: true }
    )
  }

  // Determine if category/location should be shown as select or label
  const showCategoryAsLabel = computed(() => {
    if (inquiryStore.parentId !== 0) return true
    if (isReadonly.value) return true
    return false
  })

  const showLocationAsLabel = computed(() => {
    if (inquiryStore.parentId !== 0) return true
    if (isReadonly.value) return true
    return false
  })

  return {
    context,
    isReadonly,
    isReadonlyDescription,
    showCategoryAsLabel,
    showLocationAsLabel,
    setupDescriptionReadonly
  }
}

/**
 * Gestionnaire pour les actions d'enquête
 */
export function useInquiryActions(inquiryStore: ReturnType<typeof useInquiryStore>) {
  const sessionStore = useSessionStore()
  
  const allowedTypesForActions = computed(() => [
    InquiryTypeValues.PROJECT,
    InquiryTypeValues.PROPOSAL,
    InquiryTypeValues.GRIEVANCE,
  ])

  // Check if actions menu should be shown
  const showActionsMenu = computed(
    () => {
      const { isReadonly } = useInquiryPermissions(inquiryStore)
      return isReadonly.value && allowedTypesForActions.value.includes(inquiryStore.type)
    }
  )

  // Check if response button should be shown
  const showResponseButton = computed(
    () => sessionStore.currentUser?.isOfficial && inquiryStore.type !== InquiryTypeValues.OFFICIAL
  )

  // Check if save button should be shown
  const showSaveButton = computed(() => {
    const { isReadonlyDescription } = useInquiryPermissions(inquiryStore)
    return !isReadonlyDescription.value
  })

  return {
    allowedTypesForActions,
    showActionsMenu,
    showResponseButton,
    showSaveButton
  }
}
