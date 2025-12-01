/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { AxiosError } from 'axios'
import { orderBy } from 'lodash'

import { Logger } from '../helpers/index.ts'
import { InquiryGroupsAPI } from '../Api/index.ts'
import { User, UserType } from '../Types/index.ts'
import { useSessionStore } from './session.ts'
import { useInquiriesStore } from './inquiries.ts'
import type { InquiryGroup, InquiryGroupType } from './inquiryGroups.types.ts'

export type InquiryGroupConfiguration = {
  description: string
  protected: boolean
  group_status: string
  expire: number | null
  title_ext: string | null
}

export type InquiryGroupStatus = {
  created: number
  deleted: number
  isDeleted: boolean
  countInquiries: number
  isExpired: boolean
}

export type InquiryGroupPermissions = {
  view: boolean
  edit: boolean
  delete: boolean
  addInquiries: boolean
  reorderInquiries: boolean
  changeOwner: boolean
  archive: boolean
  clone: boolean
}

export type CurrentUserInquiryGroupStatus = {
  isOwner: boolean
  isLoggedIn: boolean
  userId: string
  userRole: UserType
  canEdit: boolean
  isProtected: boolean
}

type Meta = {
  status: 'loaded' | 'loading' | 'error'
}

export const useInquiryGroupStore = defineStore('inquiryGroup', () => {
  const inquiryGroup = ref<InquiryGroup>({
    id: 0,
    cover_id: 0,
    group_type: 'default',
    parent_id: 0,
    created: 0,
    deleted: 0,
    description: '',
    owned_group: '',
    metadata: '',
    group_status: 'draft',
    protected: 0,
    owner: {
      id: '',
      displayName: '',
      type: 'user' as UserType,
      isOwner: false,
      groups: [],
    },
    title: '',
    titleExt: '',
    inquiryIds: [],
    allowEdit: false,
  })

  const meta = ref<Meta>({
    status: 'loaded',
  })

  const updating = ref(false)

  /**
   * Current inquiry group configuration
   */
  const configuration = computed((): InquiryGroupConfiguration => ({
    description: inquiryGroup.value.description,
    protected: inquiryGroup.value.protected === 1,
    group_status: inquiryGroup.value.group_status,
    expire: 0, // TODO: Add expire field to InquiryGroup type if needed
    title_ext: inquiryGroup.value.titleExt || null,
  }))

  /**
   * Current inquiry group status
   */
  const status = computed((): InquiryGroupStatus => ({
    created: inquiryGroup.value.created,
    deleted: inquiryGroup.value.deleted,
    isDeleted: inquiryGroup.value.deleted > 0,
    countInquiries: inquiryGroup.value.inquiryIds.length,
    isExpired: false, // TODO: Check expire date if implemented
  }))

  /**
   * Current user status for the inquiry group
   */
  const currentUserStatus = computed((): CurrentUserInquiryGroupStatus => {
    const sessionStore = useSessionStore()
    return {
      isOwner: inquiryGroup.value.owner.id === sessionStore.currentUser.id,
      isLoggedIn: sessionStore.currentUser.id !== '',
      userId: sessionStore.currentUser.id,
      userRole: sessionStore.currentUser.type,
      canEdit: inquiryGroup.value.allowEdit,
      isProtected: inquiryGroup.value.protected === 1,
    }
  })

  /**
   * Permissions for the current user on the inquiry group
   */
  const permissions = computed((): InquiryGroupPermissions => {
    const sessionStore = useSessionStore()
    const isOwner = inquiryGroup.value.owner.id === sessionStore.currentUser.id
    const isAdmin = sessionStore.currentUser.type === 'admin'
    const isProtected = inquiryGroup.value.protected === 1
    
    return {
      view: true, // Always viewable if you have access
      edit: (inquiryGroup.value.allowEdit || isOwner || isAdmin) && !isProtected,
      delete: (isOwner || isAdmin) && !isProtected,
      addInquiries: (inquiryGroup.value.allowEdit || isOwner || isAdmin) && !isProtected,
      reorderInquiries: (inquiryGroup.value.allowEdit || isOwner || isAdmin) && !isProtected,
      changeOwner: (isOwner || isAdmin) && !isProtected,
      archive: (isOwner || isAdmin) && !isProtected,
      clone: true, // Always allow cloning
    }
  })

  /**
   * Check if group is in draft status
   */
  const isDraft = computed((): boolean => {
    return inquiryGroup.value.group_status === 'draft'
  })

  /**
   * Check if group is active
   */
  const isActive = computed((): boolean => {
    return inquiryGroup.value.group_status === 'active'
  })

  /**
   * Check if group is archived
   */
  const isArchived = computed((): boolean => {
    return inquiryGroup.value.group_status === 'archived'
  })

  /**
   * Reset the inquiry group store to initial state
   */
  function reset(): void {
    inquiryGroup.value = {
      id: 0,
      cover_id: 0,
      group_type: 'default',
      parent_id: 0,
      created: 0,
      deleted: 0,
      description: '',
      owned_group: '',
      metadata: '',
      group_status: 'draft',
      protected: 0,
      owner: {
        id: '',
        displayName: '',
        type: 'user' as UserType,
        isOwner: false,
        groups: [],
      },
      title: '',
      titleExt: '',
      inquiryIds: [],
      allowEdit: false,
    }
    meta.value = { status: 'loaded' }
  }

  /**
   * Load an inquiry group by ID
   */
  async function load(inquiryGroupId: number | null = null): Promise<void> {
    const sessionStore = useSessionStore()
    
    meta.value.status = 'loading'
    try {
      const groupId = inquiryGroupId ?? (sessionStore.route.params.id as number)
      const response = await InquiryGroupsAPI.getInquiryGroup(groupId)
      
      inquiryGroup.value = response.data.inquiryGroup
      meta.value.status = 'loaded'
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      meta.value.status = 'error'
      Logger.error('Error loading inquiry group', { error })
      throw error
    }
  }

  /**
   * Create a new inquiry group
   */
  async function add(payload: {
    title?: string
    title_ext?: string
    description?: string
    type?: string
    parent_id?: number
    protected?: boolean
    owned_group?: string
    group_status?: string
    inquiryIds?: number[]
  }): Promise<InquiryGroup | void> {
    try {
      const response = await InquiryGroupsAPI.addGroup({
        title: payload.title,
        titleExt: payload.title_ext,
        description: payload.description,
        type: payload.type || 'default',
        parentId: payload.parent_id,
        protected: payload.protected,
        ownedGroup: payload.owned_group,
        groupStatus: payload.group_status || 'draft',
      })

      return response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error adding inquiry group:', {
        error,
        payload,
      })
      throw error
    }
  }

  /**
   * Update the current inquiry group
   */
  async function update(payload: {
    id?: number
    title?: string
    title_ext?: string
    description?: string
    type?: string
    parent_id?: number
    protected?: boolean
    owned_group?: string
    group_status?: string
    expire?: number
  }): Promise<InquiryGroup | void> {
    const inquiriesStore = useInquiriesStore()

    try {
      const groupId = payload.id ?? inquiryGroup.value.id
      const response = await InquiryGroupsAPI.updateGroup(groupId, {
        title: payload.title,
        titleExt: payload.title_ext,
        description: payload.description,
        type: payload.type,
        parentId: payload.parent_id,
        protected: payload.protected,
        ownedGroup: payload.owned_group,
        groupStatus: payload.group_status,
        expire: payload.expire,
      })

      inquiryGroup.value = response.data.inquiryGroup
      emit('update:inquiry-group', {
        store: 'inquiryGroup',
        message: t('inquiries', 'Inquiry group updated'),
      })
      
      return response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error updating inquiry group', {
        error,
        payload,
      })
      throw error
    } finally {
      inquiriesStore.load()
    }
  }

  /**
   * Delete the current inquiry group
   */
  async function deleteGroup(): Promise<void> {
    const inquiriesStore = useInquiriesStore()

    try {
      await InquiryGroupsAPI.deleteGroup(inquiryGroup.value.id)
      emit('delete:inquiry-group', {
        store: 'inquiryGroup',
        message: t('inquiries', 'Inquiry group deleted'),
      })
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error deleting inquiry group', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    } finally {
      inquiriesStore.load()
    }
  }

  /**
   * Archive the current inquiry group
   */
  async function archive(): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.updateGroup(inquiryGroup.value.id, {
        groupStatus: 'archived',
      })
      inquiryGroup.value = response.data.inquiryGroup
      emit('archive:inquiry-group', {
        store: 'inquiryGroup',
        message: t('inquiries', 'Inquiry group archived'),
      })
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error archiving inquiry group', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    }
  }

  /**
   * Restore an archived inquiry group
   */
  async function restore(): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.updateGroup(inquiryGroup.value.id, {
        groupStatus: 'active',
      })
      inquiryGroup.value = response.data.inquiryGroup
      emit('restore:inquiry-group', {
        store: 'inquiryGroup',
        message: t('inquiries', 'Inquiry group restored'),
      })
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error restoring inquiry group', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    }
  }

  /**
   * Write configuration changes to the API
   */
  async function write(): Promise<void> {
    const inquiriesStore = useInquiriesStore()

    if (!inquiryGroup.value.title && !inquiryGroup.value.title) {
      showError(t('inquiries', 'Group title must not be empty!'))
      return
    }

    updating.value = true
    try {
      const response = await InquiryGroupsAPI.updateGroup(inquiryGroup.value.id, {
        title: inquiryGroup.value.title || inquiryGroup.value.title,
        titleExt: inquiryGroup.value.titleExt,
        description: inquiryGroup.value.description,
        type: inquiryGroup.value.group_type,
        parentId: inquiryGroup.value.parent_id,
        protected: inquiryGroup.value.protected,
        ownedGroup: inquiryGroup.value.owned_group,
        groupStatus: inquiryGroup.value.group_status,
      })

      inquiryGroup.value = response.data.inquiryGroup
      emit('update:inquiry-group', {
        store: 'inquiryGroup',
        message: t('inquiries', 'Inquiry group updated'),
      })
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error writing inquiry group:', {
        error,
        inquiryGroup: inquiryGroup.value,
      })
      showError(t('inquiries', 'Error writing inquiry group'))
      throw error
    } finally {
      updating.value = false
      inquiriesStore.load()
    }
  }

  /**
   * Add an inquiry to the current group
   */
  async function addInquiryToGroup(inquiryId: number): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.addInquiryToGroup(
        inquiryId,
        inquiryGroup.value.id
      )
      inquiryGroup.value = response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error adding inquiry to group', {
        error,
        inquiryId,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    }
  }

  /**
   * Remove an inquiry from the current group
   */
  async function removeInquiryFromGroup(inquiryId: number): Promise<void> {
    const inquiriesStore = useInquiriesStore()

    try {
      const response = await InquiryGroupsAPI.removeInquiryFromGroup(
        inquiryGroup.value.id,
        inquiryId
      )

      if (response.data.inquiryGroup === null) {
        // Group was deleted because it became empty
        reset()
      } else {
        inquiryGroup.value = response.data.inquiryGroup
      }
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error removing inquiry from group', {
        error,
        inquiryId,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    } finally {
      inquiriesStore.load()
    }
  }

  /**
   * Reorder inquiries in the group
   */
  async function reorderInquiries(inquiryIds: number[]): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.reorderInquiriesInGroup(
        inquiryGroup.value.id,
        inquiryIds
      )
      inquiryGroup.value = response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error reordering inquiries in group', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
        inquiryIds,
      })
      throw error
    }
  }

  /**
   * Change the owner of the inquiry group
   */
  async function changeOwner(newOwnerId: string): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.changeGroupOwner(
        inquiryGroup.value.id,
        newOwnerId
      )
      inquiryGroup.value = response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error changing inquiry group owner', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
        newOwnerId,
      })
      throw error
    }
  }

  /**
   * Update a single field of the inquiry group
   */
  async function updateField<K extends keyof InquiryGroup>(
    field: K,
    value: InquiryGroup[K]
  ): Promise<void> {
    const oldValue = inquiryGroup.value[field]
    inquiryGroup.value[field] = value

    try {
      await write()
    } catch (error) {
      // Revert on error
      inquiryGroup.value[field] = oldValue
      throw error
    }
  }

  /**
   * Update group status
   */
  async function updateGroupStatus(status: string): Promise<void> {
    try {
      const response = await InquiryGroupsAPI.updateGroup(inquiryGroup.value.id, {
        groupStatus: status,
      })
      inquiryGroup.value = response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error updating group status', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
        status,
      })
      throw error
    }
  }

  /**
   * Clone the current inquiry group
   */
  async function clone(): Promise<InquiryGroup | void> {
    try {
      const response = await InquiryGroupsAPI.cloneGroup(inquiryGroup.value.id)
      return response.data.inquiryGroup
    } catch (error) {
      if ((error as AxiosError)?.code === 'ERR_CANCELED') {
        return
      }
      Logger.error('Error cloning inquiry group', {
        error,
        inquiryGroupId: inquiryGroup.value.id,
      })
      throw error
    }
  }

  return {
    // State
    inquiryGroup,
    meta,
    updating,

    // Computed
    configuration,
    status,
    currentUserStatus,
    permissions,
    isDraft,
    isActive,
    isArchived,

    // Actions
    reset,
    load,
    add,
    update,
    deleteGroup,
    archive,
    restore,
    write,
    addInquiryToGroup,
    removeInquiryFromGroup,
    reorderInquiries,
    changeOwner,
    updateField,
    updateGroupStatus,
    clone,
  }
})
