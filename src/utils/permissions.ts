// SPDX-FileCopyrightText: 2023 Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

import { useSessionStore } from '../stores/session.ts'
import { useAppSettingsStore } from '../stores/appSettings.ts'
import { InquiryType } from '../helpers/index.ts'

export interface InquiryTypeSettings {
  supportInquiry: boolean
  commentInquiry: boolean
  attachFileInquiry: boolean
  shareInquiry?: boolean
  editorType: string
}

export interface InquiryTypeRights {
  supportInquiry: boolean
  commentInquiry: boolean
  attachFileInquiry: boolean
  shareInquiry?: boolean
  editorType: string
}

export interface ModeratorRights {
  changeInquiryStatus?: boolean
  deleteInquiry?: boolean
  archiveInquiry?: boolean
  transferInquiry?: boolean
  modifyInquiry?: boolean
  addShares?: boolean
  addSharesExternal?: boolean
  deanonymize?: boolean
  seeUsernames?: boolean
}

export interface OfficialRights {
  changeInquiryStatus?: boolean
  deleteInquiry?: boolean
  archiveInquiry?: boolean
  transferInquiry?: boolean
  modifyInquiry?: boolean
}

export interface InquiryType {
  inquiry_type: string
  allowed_response?: string | string[]
  allowed_transformation?: string | string[]
  isOption?: number
}


export const DefaultInquiryRights: InquiryRights = {
  supportInquiry: true, 
  commentInquiry: false,
  attachFileInquiry: false,
  shareInquiry: false,
  editorType: 'wysiwyg',
}

export const DefaultModeratorRights: ModeratorRights = {
  changeInquiryStatus: true,
  deleteInquiry: true,
  archiveInquiry: true,
  transferInquiry: true,
  modifyInquiry: true,
  addShares: true,
  addSharesExternal: false,
  deanonymize: false,
}

export const DefaultOfficialRights: OfficialRights = {
  changeInquiryStatus: false,
  deleteInquiry: false,
  archiveInquiry: false,
  transferInquiry: false,
  modifyInquiry: false,
}

/**
 * User TYPE
 */
export enum UserType {
  Guest = 'guest',
  User = 'user',
  Moderator = 'moderator',
  Official = 'official',
  Admin = 'admin',
  Owner = 'owner',
}

/**
 * Content type
 */
export enum ContentType {
  Inquiry = 'inquiry',
  Comment = 'comment',
  Support = 'support',
  Attachment = 'attachment',
  Share = 'share',
}

/**
 * Access levels
 */
export enum AccessLevel {
  Private = 'private',
  Moderate = 'moderate',
  Open = 'open',
}

/**
 * Inquiry Family types
 */
export enum InquiryFamily {
  Legislatif = 'legislatif',
  Administratif = 'administratif', 
  Collective = 'collective'
}

/**
 * Interface rights permission
 */
export interface PermissionContext {
  userType: UserType
  contentType: ContentType
  isOwner: boolean
  isPublic: boolean
  isLocked: boolean
  isExpired: boolean
  isDeleted: boolean
  isArchived: boolean
  hasGroupRestrictions: boolean
  userGroups: string[]
  allowedGroups: string[]
  inquiryType?: string
  inquiryFamily?: InquiryFamily
  accessLevel?: AccessLevel
  isFinalStatus?: boolean
  moderationStatus?: string
}

// GET METHODS


function getCurrentUserType(): UserType {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser

  if (!currentUser) return UserType.Guest
  if (currentUser.isAdmin) return UserType.Admin
  if (currentUser.isModerator) return UserType.Moderator
  if (currentUser.isOfficial) return UserType.Official
  return UserType.User
}

function getCurrentUserGroups(): string[] {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  return currentUser?.groups || []
}

export function getCurrentModeratorRights(): ModeratorRights | null {
  const sessionStore = useSessionStore()
  const appSettings = useAppSettingsStore()
  const currentUser = sessionStore.currentUser
  return currentUser?.isModerator ? appSettings.moderatorRights : null
}

export function getCurrentOfficialRights(): OfficialRights | null {
  const sessionStore = useSessionStore()
  const appSettings = useAppSettingsStore()
  const currentUser = sessionStore.currentUser
  return currentUser?.isOfficial ? appSettings.officialRights : null
}

function isContentOwner(contentOwnerId: string): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  return currentUser?.id === contentOwnerId
}

export function canInquiryTypePerformAction(
  inquiryType: string,
  action: keyof InquiryTypeSettings
): boolean {
  const sessionStore = useSessionStore()
  const typeRights = sessionStore.appSettings.inquiryTypeRights[inquiryType]
  return typeRights?.[action] ?? false
}

function hasGroupAccess(context: PermissionContext): boolean {
  if (!context.hasGroupRestrictions || context.userType === UserType.Guest) {
    return true
  }

  if (context.userType === UserType.Admin || context.userType === UserType.Moderator) {
    return true
  }

  return context.userGroups.some((group) => context.allowedGroups.includes(group))
}

function isContentBlocked(context: PermissionContext): boolean {
  return context.isArchived || context.isDeleted || context.isLocked || context.isExpired
}

function isAccessRestrictedForComments(context: PermissionContext): boolean {
  if (context.isFinalStatus) {
    return true
  }
  
  if (context.moderationStatus && context.moderationStatus !== 'accepted') {
    return true
  }
  
  switch (context.accessLevel) {
    case AccessLevel.Private:
      return true
    case AccessLevel.Moderate:
      return context.userType !== UserType.Moderator && context.userType !== UserType.Admin
    case AccessLevel.Open:
    default:
      return false
  }
}

function isAccessRestrictedForSupports(context: PermissionContext): boolean {
  if (context.isFinalStatus) {
    return true
  }
  
  // Check if moderation status is accepted
  if (context.moderationStatus && context.moderationStatus !== 'accepted') {
    return true
  }
  
  switch (context.accessLevel) {
    case AccessLevel.Private:
    case AccessLevel.Moderate:
      return true
    case AccessLevel.Open:
    default:
      return false
  }
}

function isAccessRestrictedForSharing(context: PermissionContext): boolean {
  if (context.isFinalStatus) {
    return true
  }
  
  switch (context.accessLevel) {
    case AccessLevel.Private:
    case AccessLevel.Moderate:
      return true
    case AccessLevel.Open:
    default:
      return false
  }
}

function isAccessRestrictedForAttachments(context: PermissionContext): boolean {
  if (context.isFinalStatus) {
    return true
  }
  
  switch (context.accessLevel) {
    case AccessLevel.Private:
    case AccessLevel.Moderate:
      return true
    case AccessLevel.Open:
    default:
      return false
  }
}


/**
 * Check if user can create official response
 * @param context
 */
export function canCreateOfficialResponse(context: PermissionContext): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  
  // Check if user is official and moderation status is accepted
  if (!currentUser?.isOfficial || context.moderationStatus !== 'accepted') {
    return false
  }
  
  // Additional checks for content blocking
  if (isContentBlocked(context)) {
    return false
  }
  
  return true
}

/**
 * Check if user can create transformation based on inquiry family
 * @param context
 */
export function canCreateTransformation(context: PermissionContext): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  
  // Base check - must be able to edit and have accepted moderation
  if (!canEdit(context) || context.moderationStatus !== 'accepted') {
    return false
  }
  
  // Check for official user - they can create transformations regardless of family
  if (currentUser?.isOfficial) {
    return true
  }
  
  // Check based on inquiry family and user permissions
  if (context.inquiryFamily) {
    switch (context.inquiryFamily) {
      case InquiryFamily.Legislatif:
        return currentUser?.isLegislatif || false
      case InquiryFamily.Administratif:
        return currentUser?.isAdministratif || false
      case InquiryFamily.Collective:
        return currentUser?.isCollective || false
      default:
        return false
    }
  }
  
  return false
}

/**
 * Check if user can create content based on family type
 * @param context
 */
export function canCreateByFamily(context: PermissionContext): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  
  // Base check - must be able to edit and have accepted moderation
  if (!canEdit(context) || context.moderationStatus !== 'accepted') {
    return false
  }
  
  // Check based on inquiry family and user permissions
  if (context.inquiryFamily) {
    switch (context.inquiryFamily) {
      case InquiryFamily.Legislatif:
        return currentUser?.isLegislatif || false
      case InquiryFamily.Administratif:
        return currentUser?.isAdministratif || false
      case InquiryFamily.Collective:
        return currentUser?.isCollective || false
      default:
        return false
    }
  }
  
  return false
}

export function canViewToggle(context: PermissionContext): boolean {
  return [UserType.Admin, UserType.Owner, UserType.Moderator, UserType.User].includes(
    context.userType
  )
}

/**
 * @param context
 */
export function canDelete(context: PermissionContext): boolean {
  if (context.isDeleted) return false

  if (context.userType === UserType.Admin || context.isOwner) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.deleteInquiry ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.deleteInquiry ?? false
  }

  return false
}

/**
 * @param context
 */
export function canRestore(context: PermissionContext): boolean {
  if (!(context.isArchived || context.isDeleted)) return false

  if (context.userType === UserType.Admin || context.isOwner) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.archiveInquiry ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.archiveInquiry ?? false
  }

  return false
}

/**
 * @param context
 */
export function canArchive(context: PermissionContext): boolean {
  if (context.isArchived || context.isDeleted) return false

  if (context.userType === UserType.Admin || context.isOwner) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.archiveInquiry ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.archiveInquiry ?? false
  }

  return false
}

/**
 * @param context
 */
export function canTransfer(context: PermissionContext): boolean {
  if (context.userType === UserType.Admin || context.isOwner) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.transferInquiry ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.transferInquiry ?? false
  }

  return false
}

/**
 * @param context
 */
export function canModerate(context: PermissionContext): boolean {
  if (context.userType === UserType.Admin) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.changeInquiryStatus ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.changeInquiryStatus ?? false
  }

  return false
}

/**
 * @param context
 */
export function canEdit(context: PermissionContext): boolean {
  if (context.isLocked || context.isArchived || context.isDeleted) {
    return false
  }
  if (context.userType === UserType.Admin || context.isOwner) {
    return true
  }

  if (context.userType === UserType.Moderator) {
    const moderatorRights = getCurrentModeratorRights()
    return moderatorRights?.modifyInquiry ?? false
  }

  if (context.userType === UserType.User) {
    const officialRights = getCurrentOfficialRights()
    return officialRights?.modifyInquiry ?? false
  }

  return false
}

/**
 * @param context
 */
export function canComment(context: PermissionContext): boolean {
  const appSettings = useAppSettingsStore()

  if (isContentBlocked(context)) {
    return false
  }

  // Vérification des restrictions d'accès et de statut final
  if (isAccessRestrictedForComments(context)) {
    return false
  }

  if (context.inquiryType && !canInquiryTypePerformAction(context.inquiryType, 'commentInquiry')) {
    return false
  }

  if (!hasGroupAccess(context)) {
    return false
  }

  if (context.userType === UserType.Guest) {
    return context.isPublic && appSettings.allowGuestComments
  }

  return true
}

/**
 * @param context
 */
export function canSupport(context: PermissionContext): boolean {
  const appSettings = useSessionStore().appSettings

  if (isContentBlocked(context)) {
    return false
  }

  if (isAccessRestrictedForSupports(context)) {
    return false
  }

  if (context.inquiryType && !canInquiryTypePerformAction(context.inquiryType, 'supportInquiry')) {
    return false
  }
  if (!hasGroupAccess(context)) {
    return false
  }

  if (context.userType === UserType.Guest) {
    return context.isPublic && appSettings.allowGuestSupport
  }

  return true
}

/**
 * @param context
 */
export function canShare(context: PermissionContext): boolean {
  const sessionStore = useSessionStore()

  if (context.isArchived || context.isDeleted) {
    return false
  }

  if (isAccessRestrictedForSharing(context)) {
    return false
  }

  if (context.accessLevel !== AccessLevel.Open && context.userType !== UserType.Moderator && context.userType !== UserType.Admin) {
    return false
  }

  if (sessionStore.appPermissions.allowAllAccess) {
    return true
  }

  if (context.userType === UserType.Guest) {
    return false
  }

  if (context.userType === UserType.Admin || context.userType === UserType.Moderator) {
    return true
  }

  if (context.userType === UserType.Official) {
    return context.isOwner
  }

  return false
}

/**
 * @param context
 */
export function canAttachFile(context: PermissionContext): boolean {
  if (context.isArchived || context.isDeleted || context.isLocked) {
    return false
  }

  if (isAccessRestrictedForAttachments(context)) {
    return false
  }

  if (
    context.inquiryType &&
    !canInquiryTypePerformAction(context.inquiryType, 'attachFileInquiry')
  ) {
    return false
  }

  if (!hasGroupAccess(context)) {
    return false
  }

  if (context.userType === UserType.Guest) {
    return false
  }

  return true
}

export function canView(context: PermissionContext): boolean {
  const appSettings = useAppSettingsStore()

  if (context.isDeleted) {
    return [UserType.Moderator, UserType.Admin, UserType.Owner].includes(context.userType)
  }

  if (context.isArchived) {
    return [UserType.Moderator, UserType.Admin, UserType.Owner, UserType.User].includes(
      context.userType
    )
  }

  if (context.hasGroupRestrictions) {
    if (
      !hasGroupAccess(context) &&
      context.userType !== UserType.Admin &&
      context.userType !== UserType.Moderator
    ) {
      return false
    }
  }

  if (context.userType === UserType.Guest) {
    return context.isPublic && appSettings.allowPublicAccess
  }

  return true
}

export function canCreate(context: PermissionContext): boolean {
  const appSettings = useAppSettingsStore()

  if (context.userType === UserType.Guest) {
    return appSettings.allowGuestCreation
  }
  return true
}

export function canLock(context: PermissionContext): boolean {
  return [UserType.Moderator, UserType.Admin].includes(context.userType)
}

/**
 * Check if user can create a specific response type
 * @param responseType
 * @param context
 */
export function canCreateResponseType(responseType: string, context: PermissionContext): boolean {

  if (responseType === 'official') {
    return canCreateOfficialResponse(context)
  }
  
  // For regular responses: need edit rights AND accepted moderation
  const canCreate = canEdit(context) && context.moderationStatus === 'accepted'
  return canCreate
}

/**
 * Check if user can create a specific transformation type
 * @param transformType
 * @param context
 */
export function canCreateTransformationType(transformType: string, context: PermissionContext): boolean {
  // All transformations use the same permission check
  return canCreateTransformation(context)
}

/**
 * Check if response actions menu should be shown
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function shouldShowResponseActions(
  inquiryType: string, 
  inquiryTypes: any[],
  context: PermissionContext
): boolean {
  // You'll need to import getAvailableResponseTypes or recreate the logic here
  const availableTypes = inquiryTypes.filter(type => {
    // Simplified logic - you might need to adjust this based on your data structure
    const currentType = inquiryTypes.find(t => t.inquiry_type === inquiryType)
    if (!currentType?.allowed_response) return false
    
    let allowedResponses: string[] = []
    if (typeof currentType.allowed_response === 'string') {
      try {
        allowedResponses = JSON.parse(currentType.allowed_response)
      } catch {
        allowedResponses = []
      }
    } else {
      allowedResponses = currentType.allowed_response
    }
    
    return allowedResponses.includes(type.inquiry_type) && type.isOption === 0
  })
  
  if (availableTypes.length === 0) {
    return false
  }
  
  // Check if user has permission for at least one response type
  const hasPermission = availableTypes.some(type => 
    canCreateResponseType(type.inquiry_type, context)
  )
  
  return hasPermission
}

/**
 * Check if transformation actions menu should be shown
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function shouldShowTransformationActions(
  inquiryType: string, 
  inquiryTypes: any[],
  context: PermissionContext
): boolean {
  // Simplified logic - adjust based on your data structure
  const availableTypes = inquiryTypes.filter(type => {
    const currentType = inquiryTypes.find(t => t.inquiry_type === inquiryType)
    if (!currentType?.allowed_transformation) return false
    
    let allowedTransforms: string[] = []
    if (typeof currentType.allowed_transformation === 'string') {
      try {
        allowedTransforms = JSON.parse(currentType.allowed_transformation)
      } catch {
        allowedTransforms = []
      }
    } else {
      allowedTransforms = currentType.allowed_transformation
    }
    
    return allowedTransforms.includes(type.inquiry_type) && type.isOption === 0
  })
  
  if (availableTypes.length === 0) {
    return false
  }
  
  const hasPermission = canCreateTransformation(context)
  
  return hasPermission
}

export function createPermissionContextForContent(
  contentType: ContentType,
  contentOwnerId: string,
  isPublic: boolean = true,
  isLocked: boolean = false,
  isExpired: boolean = false,
  isDeleted: boolean = false,
  isArchived: boolean = false,
  hasGroupRestrictions: boolean = false,
  allowedGroups: string[] = [],
  inquiryType?: string,
  inquiryFamily?: InquiryFamily,
  accessLevel?: AccessLevel,
  isFinalStatus?: boolean,
  moderationStatus?: string
): PermissionContext {
  const userType = getCurrentUserType()
  const userGroups = getCurrentUserGroups()
  const isOwner = isContentOwner(contentOwnerId)

  return {
    userType,
    contentType,
    isOwner,
    isPublic,
    isLocked,
    isExpired,
    isDeleted,
    isArchived,
    hasGroupRestrictions,
    userGroups,
    allowedGroups,
    inquiryType,
    inquiryFamily,
    accessLevel,
    isFinalStatus,
    moderationStatus,
  }
}

export default {
  // Enums
  UserType,
  ContentType,
  AccessLevel,
  InquiryFamily,

  // Default values
  DefaultModeratorRights,
  DefaultInquiryRights,
  DefaultOfficialRights,

  // Permission functions
  canView,
  canViewToggle,
  canComment,
  canSupport,
  canShare,
  canAttachFile,
  canDelete,
  canArchive,
  canRestore,
  canTransfer,
  canModerate,
  canEdit,
  canCreate,
  canLock,
  
  // Creation, filtering inquiries
  canCreateOfficialResponse,
  canCreateTransformation,
  canCreateByFamily,
  canCreateResponseType,
  canCreateTransformationType,
  shouldShowResponseActions,
  shouldShowTransformationActions,


  // Context functions
  createPermissionContextForContent,

  // Helper functions
  getCurrentUserType,
  getCurrentUserGroups,
  getCurrentModeratorRights,
  getCurrentOfficialRights,
  canInquiryTypePerformAction,
}
