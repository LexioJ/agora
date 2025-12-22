// SPDX-FileCopyrightText: 2023 Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

import { useSessionStore } from '../stores/session.ts'
import { useAppSettingsStore } from '../stores/appSettings.ts'
import { InquiryType } from '../helpers/index.ts'

export interface InquiryTypeSettings {
  supportInquiry: boolean
  commentInquiry: boolean
  useResourceInquiry: boolean
  shareInquiry?: boolean
  editorType: string
}

export interface InquiryTypeRights {
  supportInquiry: boolean
  supportMode: boolean
  commentInquiry: boolean
  useResourceInquiry: boolean
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
}

export interface InquiryGroupRights {
  createInquiry?: boolean
  modifyGroup?: boolean
  deleteGroup?: boolean
  addInquiry?: boolean
  removeInquiry?: boolean
  manageMembers?: boolean
  managePermissions?: boolean
  viewGroup?: boolean
}

export const DefaultInquiryRights: InquiryTypeRights = {
  supportInquiry: true, 
  supportMode: 'simple', 
  commentInquiry: false,
  useResourceInquiry: false,
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

export const DefaultInquiryGroupRights: InquiryGroupRights = {
  createInquiry: true,
  modifyGroup: true,
  deleteGroup: true,
  addInquiry: true,
  removeInquiry: true,
  manageMembers: true,
  managePermissions: true,
  viewGroup: true,
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
  InquiryGroup = 'inquiry_group',
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
  Legislatif = 'legislative',
  Administratif = 'administrative', 
  Collective = 'collective',
  Official = 'official'
}

/**
 * Group Access Level
 */
export enum GroupAccessLevel {
  Private = 'private',      // Only members can view
  Restricted = 'restricted', // Members can view, some can edit
  Public = 'public',        // Anyone can view, members can edit
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
  // Group-specific properties
  groupAccessLevel?: GroupAccessLevel
  isGroupMember?: boolean
  isGroupModerator?: boolean
  groupType?: string
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

export function getCurrentInquiryGroupRights(): InquiryGroupRights | null {
  const appSettings = useAppSettingsStore()
  return appSettings.inquiryGroupRights || DefaultInquiryGroupRights
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

  return false
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

/**
 * Check if user can edit result based on moderation status
 * @param moderationStatus
 */
export function canEditResult(moderationStatus: string): boolean {
  return moderationStatus !== 'rejected' && moderationStatus !== 'pending'
}

/**
 * Check if user can perform actions based on moderation status
 * @param moderationStatus
 */
export function canPerformActions(moderationStatus: string): {
  canUseResource: boolean
  canTransfer: boolean
  canDelete: boolean
  canArchive: boolean
} {
  const canPerform = moderationStatus !== 'rejected' && moderationStatus !== 'pending'
  
  return {
    canUseResource: canPerform,
    canTransfer: canPerform,
    canDelete: canPerform,
    canArchive: canPerform,
  }
}

/**
 * Verify access to family menu based on user permissions and selected family type
 * @param selectedFamilyType
 */
export function accessFamilyMenu(selectedFamilyType: InquiryFamily): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser

  if (!currentUser) {
    return false
  }

  let hasAccess = false

  switch (selectedFamilyType) {
    case InquiryFamily.Official:
      hasAccess = currentUser.isOfficial || false
      break
    case InquiryFamily.Legislatif:
      hasAccess = currentUser.isLegislative || false
      break
    case InquiryFamily.Administratif:
      hasAccess = currentUser.isAdministrative || false
      break
    case InquiryFamily.Collective:
      hasAccess = currentUser.isCollective || false
      break
    default:
      hasAccess = true
  }

  if (!hasAccess) {
    return false 
  }

  return hasAccess
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
        return currentUser?.isLegislative || false
      case InquiryFamily.Administratif:
        return currentUser?.isAdministrative || false
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
        return currentUser?.isLegislative || false
      case InquiryFamily.Administratif:
        return currentUser?.isAdministrative || false
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

  // Check moderation status restrictions
  if (context.moderationStatus === 'rejected' || context.moderationStatus === 'pending') {
    return false
  }

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

  // Check moderation status restrictions
  if (context.moderationStatus === 'rejected' || context.moderationStatus === 'pending') {
    return false
  }

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
  // Check moderation status restrictions
  if (context.moderationStatus === 'rejected' || context.moderationStatus === 'pending') {
    return false
  }

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

  // Check moderation status restrictions
  if (context.moderationStatus === 'rejected' || context.moderationStatus === 'pending') {
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
export function canUseResource(context: PermissionContext): boolean {
  if (context.isArchived || context.isDeleted || context.isLocked) {
    return false
  }
  // Check moderation status restrictions
  if (context.moderationStatus === 'rejected' || context.moderationStatus === 'pending') {
    return false
  }

  if (
    context.inquiryType &&
    !canInquiryTypePerformAction(context.inquiryType, 'useResourceInquiry')
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

/**
 * @param context
 */
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

/**
 * @param context
 */
export function canCreate(context: PermissionContext): boolean {
  const appSettings = useAppSettingsStore()

  if (context.userType === UserType.Guest) {
    return appSettings.allowGuestCreation
  }
  return true
}

/**
 * @param context
 */
export function canLock(context: PermissionContext): boolean {
  return [UserType.Moderator, UserType.Admin].includes(context.userType)
}

/**
 * INQUIRY GROUP SPECIFIC PERMISSIONS
 */

/**
 * Check if user can view an inquiry group
 * @param context
 */
export function canViewInquiryGroup(context: PermissionContext): boolean {
  // Always allow admins and moderators
  if (context.userType === UserType.Admin || context.userType === UserType.Moderator) {
    return true
  }

  // Check group access level
  switch (context.groupAccessLevel) {
    case GroupAccessLevel.Private:
      return context.isGroupMember || context.isOwner
    
    case GroupAccessLevel.Restricted:
      // Members can view, non-members need to be part of allowed groups
      if (context.isGroupMember) return true
      return hasGroupAccess(context)
    
    case GroupAccessLevel.Public:
    default:
      return true
  }
}

/**
 * Check if user can create an inquiry group
 * @param context
 */
export function canCreateInquiryGroup(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  if (context.userType === UserType.Guest) {
    return false
  }

  if (context.userType === UserType.Admin) {
    return true
  }

  return groupRights?.createInquiry ?? false
}

/**
 * Check if user can modify an inquiry group
 * @param context
 */
export function canModifyInquiryGroup(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Always allow owners and admins
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Moderators can modify if they have the right
  if (context.userType === UserType.Moderator) {
    return groupRights?.modifyGroup ?? false
  }

  // Group moderators can modify
  if (context.isGroupModerator) {
    return true
  }

  return false
}

/**
 * Check if user can delete an inquiry group
 * @param context
 */
export function canDeleteInquiryGroup(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Only owners and admins can delete
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Moderators can delete if they have the right
  if (context.userType === UserType.Moderator) {
    return groupRights?.deleteGroup ?? false
  }

  return false
}

/**
 * Check if user can add inquiries to a group
 * @param context
 */
export function canAddInquiryToGroup(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Always allow owners and admins
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Check group access level
  switch (context.groupAccessLevel) {
    case GroupAccessLevel.Private:
      return context.isGroupMember || context.isGroupModerator
    
    case GroupAccessLevel.Restricted:
      // Members and moderators can add
      if (context.isGroupMember || context.isGroupModerator) return true
      // Moderators can add if they have the right
      if (context.userType === UserType.Moderator) {
        return groupRights?.addInquiry ?? false
      }
      return false
    
    case GroupAccessLevel.Public:
      // Anyone can add to public groups
      return true
    
    default:
      return false
  }
}

/**
 * Check if user can remove inquiries from a group
 * @param context
 */
export function canRemoveInquiryFromGroup(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Always allow owners and admins
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Group moderators can remove
  if (context.isGroupModerator) {
    return true
  }

  // Moderators can remove if they have the right
  if (context.userType === UserType.Moderator) {
    return groupRights?.removeInquiry ?? false
  }

  // Users can only remove their own inquiries from groups
  return context.isOwner
}

/**
 * Check if user can manage group members
 * @param context
 */
export function canManageGroupMembers(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Always allow owners and admins
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Group moderators can manage members
  if (context.isGroupModerator) {
    return true
  }

  // Moderators can manage if they have the right
  if (context.userType === UserType.Moderator) {
    return groupRights?.manageMembers ?? false
  }

  return false
}

/**
 * Check if user can manage group permissions
 * @param context
 */
export function canManageGroupPermissions(context: PermissionContext): boolean {
  const groupRights = getCurrentInquiryGroupRights()
  
  // Only owners and admins can manage permissions
  if (context.isOwner || context.userType === UserType.Admin) {
    return true
  }

  // Moderators can manage if they have the right
  if (context.userType === UserType.Moderator) {
    return groupRights?.managePermissions ?? false
  }

  return false
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

  // Check if user has required group membership for this response type
  if (!hasRequiredGroupForResponseType(responseType)) {
    return false
  }

  // For regular responses: need edit rights AND accepted moderation
  // const canCreate = canEdit(context) && context.moderationStatus === 'accepted'
  const canCreate = context.moderationStatus === 'accepted'
  return canCreate
}

/**
 * Check if user can create a specific transformation type
 * @param transformType
 * @param context
 */
export function canCreateTransformationType(transformType: string, context: PermissionContext): boolean {
  // Check if user has required group membership for this transformation type
  if (!hasRequiredGroupForTransformationType(transformType)) {
    return false
  }
  return canCreateTransformation(context)
}

/**
 * Check if user has required group membership for response type
 * @param responseType
 */
function hasRequiredGroupForResponseType(responseType: string): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  
  if (!currentUser) return false
  
  // Only check group membership for specific family-related response types
  const responseTypeRequirements: { [key: string]: boolean } = {
    'legislative': currentUser.isLegislative || false,
    'administrative': currentUser.isAdministrative || false, 
    'collective': currentUser.isCollective || false,
    'official': currentUser.isOfficial || false
  }
  
  // If this response type requires specific group membership, check it
  const requiredPermission = responseTypeRequirements[responseType]
  if (requiredPermission !== undefined) {
    return requiredPermission
  }
  
  // For all other response types, allow by default
  return true
}

/**
 * Check if user has required group membership for transformation type  
 * @param transformType
 */
function hasRequiredGroupForTransformationType(transformType: string): boolean {
  const sessionStore = useSessionStore()
  const currentUser = sessionStore.currentUser
  
  if (!currentUser) return false
  
  // Only check group membership for specific family-related transformation types
  const transformTypeRequirements: { [key: string]: boolean } = {
    'legislative': currentUser.isLegislative || false,
    'administrative': currentUser.isAdministrative || false,
    'collective': currentUser.isCollective || false, 
    'official': currentUser.isOfficial || false
  }
  
  // If this transformation type requires specific group membership, check it
  const requiredPermission = transformTypeRequirements[transformType]
  if (requiredPermission !== undefined) {
    return requiredPermission
  }
  
  // For all other transformation types, allow by default
  return true
}

/**
 * Check if response actions menu should be shown
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function shouldShowResponseActions(
  inquiryType: string, 
  inquiryTypes: InquiryType[],
  context: PermissionContext
): boolean {
  const availableTypes = getAvailableResponseTypesWithPermissions(inquiryType, inquiryTypes, context)
  return availableTypes.length > 0
}

/**
 * Check if transformation actions menu should be shown
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function shouldShowTransformationActions(
  inquiryType: string, 
  inquiryTypes: InquiryType[],
  context: PermissionContext
): boolean {
  const availableTypes = getAvailableTransformTypesWithPermissions(inquiryType, inquiryTypes, context)
  return availableTypes.length > 0
}

/**
 * Get available response types with permission filtering
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function getAvailableResponseTypesWithPermissions(
  inquiryType: string, 
  inquiryTypes: InquiryType[],
  context: PermissionContext
): InquiryType[] {
  const availableTypes = inquiryTypes.filter(type => {
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
    
    return allowedResponses.includes(type.inquiry_type) && 
           canCreateResponseType(type.inquiry_type, context)
  })
  
  return availableTypes
}

/**
 * Get available transformation types with permission filtering
 * @param inquiryType
 * @param inquiryTypes
 * @param context
 */
export function getAvailableTransformTypesWithPermissions(
  inquiryType: string, 
  inquiryTypes: InquiryType[],
  context: PermissionContext
): InquiryType[] {
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
    
    return allowedTransforms.includes(type.inquiry_type) && 
           canCreateTransformationType(type.inquiry_type, context)
  })
  
  return availableTypes
}

/**
 * Create permission context for inquiry group
 * @param groupOwnerId
 * @param isPublic
 * @param isDeleted
 * @param isArchived
 * @param hasGroupRestrictions
 * @param allowedGroups
 * @param groupAccessLevel
 * @param isGroupMember
 * @param isGroupModerator
 * @param groupType
 */
export function createPermissionContextForInquiryGroup(
  groupOwnerId: string,
  isPublic: boolean = true,
  isDeleted: boolean = false,
  isArchived: boolean = false,
  hasGroupRestrictions: boolean = false,
  allowedGroups: string[] = [],
  groupAccessLevel: GroupAccessLevel = GroupAccessLevel.Public,
  isGroupMember: boolean = false,
  isGroupModerator: boolean = false,
  groupType?: string
): PermissionContext {
  const userType = getCurrentUserType()
  const userGroups = getCurrentUserGroups()
  const isOwner = isContentOwner(groupOwnerId)

  return {
    userType,
    contentType: ContentType.InquiryGroup,
    isOwner,
    isPublic,
    isLocked: false, // Groups don't have locked state
    isExpired: false, // Groups don't have expired state
    isDeleted,
    isArchived,
    hasGroupRestrictions,
    userGroups,
    allowedGroups,
    groupAccessLevel,
    isGroupMember,
    isGroupModerator,
    groupType,
  }
}

/**
 * Create permission context for content
 * @param contentType
 * @param contentOwnerId
 * @param isPublic
 * @param isLocked
 * @param isExpired
 * @param isDeleted
 * @param isArchived
 * @param hasGroupRestrictions
 * @param allowedGroups
 * @param inquiryType
 * @param inquiryFamily
 * @param accessLevel
 * @param isFinalStatus
 * @param moderationStatus
 */
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
  GroupAccessLevel,

  // Default values
  DefaultModeratorRights,
  DefaultInquiryRights,
  DefaultOfficialRights,
  DefaultInquiryGroupRights,

  // Permission functions
  canView,
  canViewToggle,
  canComment,
  canSupport,
  canShare,
  canUseResource,
  canDelete,
  canArchive,
  canRestore,
  canTransfer,
  canModerate,
  canEdit,
  canCreate,
  canLock,
  
  // New moderation status functions
  canEditResult,
  canPerformActions,
  accessFamilyMenu,
  
  // Creation, filtering inquiries
  canCreateOfficialResponse,
  canCreateTransformation,
  canCreateByFamily,
  canCreateResponseType,
  canCreateTransformationType,
  shouldShowResponseActions,
  shouldShowTransformationActions,

  // Inquiry Group specific permissions
  canViewInquiryGroup,
  canCreateInquiryGroup,
  canModifyInquiryGroup,
  canDeleteInquiryGroup,
  canAddInquiryToGroup,
  canRemoveInquiryFromGroup,
  canManageGroupMembers,
  canManageGroupPermissions,

  // Context functions
  createPermissionContextForContent,
  createPermissionContextForInquiryGroup,

  // Helper functions
  getCurrentUserType,
  getCurrentUserGroups,
  getCurrentModeratorRights,
  getCurrentOfficialRights,
  getCurrentInquiryGroupRights,
  canInquiryTypePerformAction,
}
