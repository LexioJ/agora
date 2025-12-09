<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-group-view-main">
    <!-- Main Layout Container -->
    <div class="layout-container">
      <!-- Left Sidebar (30%) -->
      <aside v-if="hasSidebarInquiries" class="layout-sidebar">
        <div class="sidebar-content">
          <div class="sidebar-header">
            <h3>{{ t('agora', 'Inquiries') }}</h3>
            <span class="sidebar-count">{{ sidebarInquiries.length }}</span>
          </div>
          
          <!-- Sidebar Inquiries -->
          <div class="sidebar-inquiries">
            <div 
              v-for="inquiry in sidebarInquiries" 
              :key="inquiry.id"
              class="sidebar-inquiry-item"
              :class="{ active: activeInquiryId === inquiry.id }"
              @click="setActiveInquiry(inquiry.id)"
            >
              <component 
                :is="getInquiryComponent(inquiry, 'sidebar')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'sidebar')"
                @click="handleInquiryClick(inquiry)"
              />
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area (70%) -->
      <main class="layout-main" :class="{ 'with-sidebar': hasSidebarInquiries }">
        <!-- Header Area Inquiries -->
        <div v-if="headerInquiries.length > 0" class="header-area">
          <div class="header-inquiries">
            <div 
              v-for="inquiry in headerInquiries" 
              :key="inquiry.id"
              class="header-inquiry-item"
            >
              <component 
                :is="getInquiryComponent(inquiry, 'header')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'header')"
              />
            </div>
          </div>
        </div>

        <!-- Main Area Inquiries -->
        <div v-if="mainInquiries.length > 0" class="main-area">
          <div class="main-inquiries">
            <div 
              v-for="inquiry in mainInquiries" 
              :key="inquiry.id"
              class="main-inquiry-item"
            >
              <component 
                :is="getInquiryComponent(inquiry, 'main')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'main')"
                :is-active="activeInquiryId === inquiry.id"
              />
            </div>
          </div>
        </div>

        <!-- Footer Area Inquiries -->
        <div v-if="footerInquiries.length > 0" class="footer-area">
          <div class="footer-inquiries">
            <div 
              v-for="inquiry in footerInquiries" 
              :key="inquiry.id"
              class="footer-inquiry-item"
            >
              <component 
                :is="getInquiryComponent(inquiry, 'footer')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'footer')"
              />
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="totalInquiries === 0" class="empty-main">
          <div class="empty-icon">📋</div>
          <h2>{{ t('agora', 'No inquiries to display') }}</h2>
          <p>{{ t('agora', 'Configure inquiry display settings or create inquiries') }}</p>
        </div>
      </main>
    </div>

    <!-- Modal/Popup Overlays -->
    <div v-if="modalInquiries.length > 0" class="modal-overlay">
      <div 
        v-for="inquiry in modalInquiries" 
        :key="inquiry.id"
        class="modal-container"
        @click.self="closeModal(inquiry.id)"
      >
        <div class="modal-content">
          <button class="modal-close" @click="closeModal(inquiry.id)">
            <svg width="24" height="24" viewBox="0 0 24 24">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
          <component 
            :is="getInquiryComponent(inquiry, 'modal')"
            :inquiry="inquiry"
            :render-mode="getRenderMode(inquiry, 'modal')"
          />
        </div>
      </div>
    </div>

    <div v-if="popupInquiries.length > 0" class="popup-overlay">
      <div 
        v-for="inquiry in popupInquiries" 
        :key="inquiry.id"
        class="popup-container"
        :style="getPopupStyle(inquiry)"
        @click.self="closePopup(inquiry.id)"
      >
        <div class="popup-content">
          <button class="popup-close" @click="closePopup(inquiry.id)">
            <svg width="20" height="20" viewBox="0 0 24 24">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
          <component 
            :is="getInquiryComponent(inquiry, 'popup')"
            :inquiry="inquiry"
            :render-mode="getRenderMode(inquiry, 'popup')"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'
import { useRouter } from 'vue-router'

import { getInquiryTypeData } from '../../helpers/modules/InquiryHelper.ts'
import type { InquiryGroup } from '../../stores/inquiryGroups.types.ts'
import type { Inquiry } from '../../Types/index.ts'

// Import stores
import { useInquiryStore } from '../../stores/inquiry.ts'
import { useInquiriesStore } from '../../stores/inquiries.ts'
import { useAppSettingsStore } from '../../stores/appSettings.ts'

// Import inquiry display components
import InquiryCard from '../components/InquiryCard.vue'
import InquiryListItem from '../components/InquiryListItem.vue'
import InquiryFull from '../components/InquiryFull.vue'
import InquiryRichHTML from '../components/InquiryRichHTML.vue'
import InquirySummary from '../components/InquirySummary.vue'

interface Props {
  group: InquiryGroup
  inquiryIds: number[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  viewInquiry: [id: number]
}>()

const router = useRouter()

// Initialize stores
const inquiryStore = useInquiryStore()
const inquiriesStore = useInquiriesStore()
const appSettingsStore = useAppSettingsStore()

const activeInquiryId = ref<number | null>(null)
const openModals = ref<number[]>([])
const openPopups = ref<number[]>([])

// Get inquiries from store using the IDs
const inquiries = computed(() => {
  const allInquiries = inquiriesStore.inquiries || []
  return allInquiries.filter(inquiry =>
    props.inquiryIds.includes(inquiry.id)
  )
})

// Get inquiry types from appSettings
const inquiryTypes = computed(() => {
  return appSettingsStore.inquiryTypeTab || []
})

// Compatibility matrix based on your requirements
const compatibilityMatrix = {
  cards: ['sidebar', 'main'],
  list_item: ['sidebar', 'main'],
  full: ['main'],
  rich_html: ['main', 'modal'],
  summary: ['header', 'sidebar']
}

// Group inquiries by layout zone
const sidebarInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    inquiry.layout_zone === 'sidebar' || 
    ['cards', 'list_item', 'summary'].includes(inquiry.render_mode)
  ).slice(0, 10) // Limit sidebar items
})

const headerInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    inquiry.layout_zone === 'header' || 
    inquiry.render_mode === 'summary'
  ).slice(0, 3) // Limit header items
})

const mainInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    inquiry.layout_zone === 'main' || 
    ['cards', 'list_item', 'full', 'rich_html'].includes(inquiry.render_mode)
  )
})

const footerInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    inquiry.layout_zone === 'footer' || 
    inquiry.render_mode === 'summary'
  ).slice(0, 5) // Limit footer items
})

const modalInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    (inquiry.layout_zone === 'modal' || inquiry.render_mode === 'rich_html') &&
    openModals.value.includes(inquiry.id)
  )
})

const popupInquiries = computed(() => {
  return inquiries.value.filter(inquiry => 
    inquiry.layout_zone === 'popup' &&
    openPopups.value.includes(inquiry.id)
  )
})

// Helper computed properties
const hasSidebarInquiries = computed(() => sidebarInquiries.value.length > 0)
const totalInquiries = computed(() => inquiries.value.length)

// Get the appropriate render mode for a given layout zone
function getRenderMode(inquiry: Inquiry, layoutZone: string): string {
  const preferredMode = inquiry.render_mode || 'cards'
  
  // Check if preferred mode is compatible with layout zone
  if (compatibilityMatrix[preferredMode]?.includes(layoutZone)) {
    return preferredMode
  }
  
  // Fallback to compatible mode
  for (const [mode, zones] of Object.entries(compatibilityMatrix)) {
    if (zones.includes(layoutZone)) {
      return mode
    }
  }
  
  return 'cards' // Default fallback
}

// Get the appropriate component based on render mode
function getInquiryComponent(inquiry: Inquiry, layoutZone: string) {
  const renderMode = getRenderMode(inquiry, layoutZone)
  
  switch (renderMode) {
    case 'cards':
      return InquiryCard
    case 'list_item':
      return InquiryListItem
    case 'full':
      return InquiryFull
    case 'rich_html':
      return InquiryRichHTML
    case 'summary':
      return InquirySummary
    default:
      return InquiryCard
  }
}

// Handle inquiry click
function handleInquiryClick(inquiry: Inquiry) {
  const layoutZone = inquiry.layout_zone || 'main'
  
  switch (layoutZone) {
    case 'modal':
      openModal(inquiry.id)
      break
    case 'popup':
      openPopup(inquiry.id)
      break
    default:
      setActiveInquiry(inquiry.id)
      emit('viewInquiry', inquiry.id)
      break
  }
}

// Active inquiry management
function setActiveInquiry(id: number) {
  activeInquiryId.value = id
}

// Modal management
function openModal(id: number) {
  if (!openModals.value.includes(id)) {
    openModals.value.push(id)
  }
}

function closeModal(id: number) {
  const index = openModals.value.indexOf(id)
  if (index > -1) {
    openModals.value.splice(index, 1)
  }
}

// Popup management
function openPopup(id: number) {
  if (!openPopups.value.includes(id)) {
    openPopups.value.push(id)
  }
}

function closePopup(id: number) {
  const index = openPopups.value.indexOf(id)
  if (index > -1) {
    openPopups.value.splice(index, 1)
  }
}

function getPopupStyle(inquiry: Inquiry) {
  // Generate random position for popup demonstration
  const left = Math.random() * 70 + 15
  const top = Math.random() * 70 + 15
  
  return {
    left: `${left}%`,
    top: `${top}%`
  }
}

// Helper function for inquiry type data
function getInquiryTypeIcon(typeKey: string) {
  const typeData = getInquiryTypeData(typeKey, inquiryTypes.value)
  return typeData?.icon || '📝'
}
</script>

<style lang="scss" scoped>
.inquiry-group-view-main {
  width: 100%;
  min-height: 100vh;
  background: var(--color-main-background);
}

/* Layout Container */
.layout-container {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 32px;
  max-width: 1600px;
  margin: 0 auto;
  padding: 32px;
  min-height: calc(100vh - 120px);
}

/* Sidebar Styles */
.layout-sidebar {
  position: sticky;
  top: 32px;
  height: fit-content;
  max-height: calc(100vh - 120px);
  overflow-y: auto;
  
  .sidebar-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    border: 1px solid var(--color-border);
    overflow: hidden;
  }
  
  .sidebar-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--color-border);
    background: var(--color-background-dark);
    display: flex;
    align-items: center;
    justify-content: space-between;
    
    h3 {
      font-size: 18px;
      font-weight: 600;
      color: var(--color-main-text);
      margin: 0;
    }
    
    .sidebar-count {
      background: var(--color-primary-element);
      color: white;
      padding: 4px 10px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 600;
      min-width: 28px;
      text-align: center;
    }
  }
  
  .sidebar-inquiries {
    padding: 16px;
  }
  
  .sidebar-inquiry-item {
    margin-bottom: 12px;
    
    &:last-child {
      margin-bottom: 0;
    }
    
    &.active {
      position: relative;
      
      &::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--color-primary-element);
        border-radius: 2px;
      }
    }
  }
}

/* Main Content Area */
.layout-main {
  &.with-sidebar {
    grid-column: 2;
  }
  
  .header-area,
  .main-area,
  .footer-area {
    margin-bottom: 32px;
    
    &:last-child {
      margin-bottom: 0;
    }
  }
}

/* Header Area */
.header-area {
  .header-inquiries {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
  }
}

/* Main Area */
.main-area {
  .main-inquiries {
    display: flex;
    flex-direction: column;
    gap: 24px;
  }
}

/* Footer Area */
.footer-area {
  .footer-inquiries {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
  }
}

/* Empty State */
.empty-main {
  text-align: center;
  padding: 80px 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--color-border);
  
  .empty-icon {
    font-size: 64px;
    margin-bottom: 24px;
    opacity: 0.3;
  }
  
  h2 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 0 0 12px 0;
  }
  
  p {
    color: var(--color-text-lighter);
    font-size: 16px;
    margin: 0;
  }
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-container {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-content {
  position: relative;
  background: white;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  animation: modalSlideIn 0.3s ease;
}

.modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--color-background-hover);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 100;
  transition: background-color 0.2s ease;
  
  &:hover {
    background: var(--color-background-darker);
  }
  
  svg {
    fill: var(--color-text-lighter);
  }
}

/* Popup Styles */
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  z-index: 9998;
}

.popup-container {
  position: absolute;
  pointer-events: auto;
  animation: popupFadeIn 0.2s ease;
}

.popup-content {
  position: relative;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
  border: 1px solid var(--color-border);
  width: 300px;
  max-height: 400px;
  overflow-y: auto;
}

.popup-close {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-background-hover);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 100;
  transition: background-color 0.2s ease;
  
  &:hover {
    background: var(--color-background-darker);
  }
  
  svg {
    fill: var(--color-text-lighter);
  }
}

/* Animations */
@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes popupFadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Responsive Design */
@media (max-width: 1200px) {
  .layout-container {
    grid-template-columns: 280px 1fr;
    gap: 24px;
    padding: 24px;
  }
}

@media (max-width: 992px) {
  .layout-container {
    grid-template-columns: 1fr;
  }
  
  .layout-sidebar {
    position: static;
    max-height: none;
  }
  
  .layout-main.with-sidebar {
    grid-column: 1;
  }
}

@media (max-width: 768px) {
  .layout-container {
    padding: 16px;
  }
  
  .header-area,
  .main-area,
  .footer-area {
    margin-bottom: 24px;
  }
  
  .modal-content {
    width: 95%;
    max-height: 95vh;
  }
}

@media (max-width: 480px) {
  .modal-content {
    border-radius: 12px;
  }
  
  .popup-content {
    width: 280px;
  }
}
</style>
