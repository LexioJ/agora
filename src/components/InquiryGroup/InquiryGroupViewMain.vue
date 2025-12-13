<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
->
<!-- InquiryGroupViewMain.vue -->
<template>
  <div class="inquiry-group-view-main">
    <!-- Main Layout Container -->
    <div class="layout-container">
      <!-- Main Content Area (70%) -->
      <main class="layout-main" :class="{ 'with-sidebar': hasSidebarInquiries }">
        <!-- Header Area Inquiries -->
        <section v-if="headerInquiries.length > 0" class="header-section">
          <div class="inquiry-grid header-grid">
            <template v-for="inquiry in headerInquiries" :key="inquiry.id">
              <component
                :is="getInquiryComponent(inquiry, 'header')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'header')"
                class="header-item"
              />
            </template>
          </div>
        </section>

        <!-- Main Area Inquiries - Grouped by Type with Beautiful Headers -->
        <section v-if="mainInquiries.length > 0" class="main-section">
          <!-- Group by Inquiry Type -->
          <div class="type-groups">
            <div
              v-for="(typeGroup, typeKey) in mainInquiriesByType"
              :key="typeKey"
              class="type-group"
            >
              <!-- Beautiful Type Header -->
              <div class="type-header">
                <div class="type-header-content">
                  <div class="type-icon-wrapper">
                    <component
                      :is="getInquiryTypeIcon(typeKey)"
                      class="type-icon"
                    />
                  </div>
                  <div class="type-info">
                    <h3 class="type-name">{{ getInquiryTypeLabel(typeKey) }}</h3>
                    <p class="type-description">{{ getInquiryTypeDescription(typeKey) }}</p>
                  </div>
                  <div class="type-stats">
                    <span class="type-badge">{{ typeGroup.length }} items</span>
                    <span class="type-family">{{ getInquiryTypeFamily(typeKey) }}</span>
                  </div>
                </div>
                <div class="type-actions">
                  <button class="type-action-btn" @click="viewAllOfType(typeKey)">
                    <span>View All</span>
                    <svg width="16" height="16" viewBox="0 0 24 24">
                      <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Type Inquiries Grid -->
              <div class="type-inquiries">
                <div class="inquiry-grid main-grid">
                  <template v-for="inquiry in typeGroup" :key="inquiry.id">
                    <component
                      :is="getInquiryComponent(inquiry, 'main')"
                      :inquiry="inquiry"
                      :render-mode="getRenderMode(inquiry, 'main')"
                      class="main-item"
                      @click="handleInquiryClick(inquiry)"
                    />
                  </template>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Footer Area Inquiries -->
        <section v-if="footerInquiries.length > 0" class="footer-section">
          <div class="inquiry-grid footer-grid">
            <template v-for="inquiry in footerInquiries" :key="inquiry.id">
              <component
                :is="getInquiryComponent(inquiry, 'footer')"
                :inquiry="inquiry"
                :render-mode="getRenderMode(inquiry, 'footer')"
                class="footer-item"
                @click="handleInquiryClick(inquiry)"
              />
            </template>
          </div>
        </section>
      </main>

      <!-- Right Sidebar (30%) -->
      <aside v-if="hasSidebarInquiries" class="layout-sidebar">
        <div class="sidebar-content">
          <!-- Sidebar Inquiries - Grouped by Type -->
          <div class="sidebar-type-groups">
            <div
              v-for="(typeGroup, typeKey) in sidebarInquiriesByType"
              :key="typeKey"
              class="sidebar-type-group"
            >
              <!-- Sidebar Type Header -->
              <div class="sidebar-type-header">
                <div class="sidebar-type-icon">
                  <component :is="getInquiryTypeIcon(typeKey)" />
                </div>
                <div class="sidebar-type-info">
                  <h4 class="sidebar-type-name">{{ getInquiryTypeLabel(typeKey) }}</h4>
                  <span class="sidebar-type-count">{{ typeGroup.length }}</span>
                </div>
              </div>

              <!-- Sidebar Type Inquiries -->
              <div class="sidebar-inquiries">
                <template v-for="inquiry in typeGroup" :key="inquiry.id">
                  <component
                    :is="getInquiryComponent(inquiry, 'sidebar')"
                    :inquiry="inquiry"
                    :render-mode="getRenderMode(inquiry, 'sidebar')"
                    class="sidebar-item"
                    @click="handleInquiryClick(inquiry)"
                  />
                </template>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { t } from '@nextcloud/l10n'

import type { InquiryGroup } from '../../stores/inquiryGroups.types.ts'
import type { Inquiry } from '../../Types/index.ts'

// Import stores
import { useInquiriesStore } from '../../stores/inquiries.ts'
import { useAppSettingsStore } from '../../stores/appSettings.ts'

// Import all inquiry display components
import InquiryCard from './InquiryCard.vue'
import InquiryListItem from './InquiryListItem.vue'
import InquiryFull from './InquiryFull.vue'
import InquiryRichHTML from './InquiryRichHTML.vue'
import InquirySummary from './InquirySummary.vue'

interface Props {
  group: InquiryGroup
  inquiryIds: number[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  viewInquiry: [id: number]
}>()

// Initialize stores
const inquiriesStore = useInquiriesStore()
const appSettingsStore = useAppSettingsStore()

const activeInquiryId = ref<number | null>(null)

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

// Layout compatibility matrix
const layoutCompatibility = {
  sidebar: ['cards', 'list_item', 'summary'],
  main: ['rich_html', 'full', 'summary', 'cards', 'list_item'],
  footer: ['cards', 'summary', 'list_item'],
  header: ['cards', 'summary', 'list_item']
}

// Helper function to get miscField with fallback
function getMiscField(inquiry: Inquiry, field: string, defaultValue: any = null) {
  return inquiry.miscFields?.[field] || defaultValue
}

// Get layout zone for inquiry
function getLayoutZone(inquiry: Inquiry): string {
  const layoutZone = getMiscField(inquiry, 'layout_zone')
  if (layoutZone && ['sidebar', 'main', 'footer', 'header'].includes(layoutZone)) {
    return layoutZone
  }

  // Determine by render mode
  const renderMode = getMiscField(inquiry, 'render_mode', 'cards')

  // Find compatible layout
  const compatibleLayouts = Object.entries(layoutCompatibility)
    .filter(([_, modes]) => modes.includes(renderMode))
    .map(([layout]) => layout)

  // Priority order
  const priorityOrder = ['main', 'header', 'sidebar', 'footer']
  return priorityOrder.find(layout => compatibleLayouts.includes(layout)) || 'main'
}

// Get appropriate render mode for layout zone
function getRenderMode(inquiry: Inquiry, layoutZone: string): string {
  const preferredMode = getMiscField(inquiry, 'render_mode', 'cards')

  // Check if preferred mode is compatible
  if (layoutCompatibility[layoutZone]?.includes(preferredMode)) {
    return preferredMode
  }

  // Fallback to first compatible mode
  return layoutCompatibility[layoutZone]?.[0] || 'cards'
}

// Get component based on render mode
function getInquiryComponent(inquiry: Inquiry, layoutZone: string) {
  const renderMode = getRenderMode(inquiry, layoutZone)

  switch (renderMode) {
    case 'cards': return InquiryCard
    case 'list_item': return InquiryListItem
    case 'full': return InquiryFull
    case 'rich_html': return InquiryRichHTML
    case 'summary': return InquirySummary
    default: return InquiryCard
  }
}

// Group inquiries by type
function groupInquiriesByType(inquiryList: Inquiry[]) {
  const grouped: Record<string, Inquiry[]> = {}

  inquiryList.forEach(inquiry => {
    const type = inquiry.type || 'default'
    if (!grouped[type]) grouped[type] = []
    grouped[type].push(inquiry)
  })

  return grouped
}

// Get inquiries for each layout zone
const sidebarInquiries = computed(() =>
  inquiries.value.filter(inquiry => getLayoutZone(inquiry) === 'sidebar')
)

const headerInquiries = computed(() =>
  inquiries.value.filter(inquiry => getLayoutZone(inquiry) === 'header')
)

const mainInquiries = computed(() =>
  inquiries.value.filter(inquiry => getLayoutZone(inquiry) === 'main')
)

const footerInquiries = computed(() =>
  inquiries.value.filter(inquiry => getLayoutZone(inquiry) === 'footer')
)

// Grouped inquiries
const sidebarInquiriesByType = computed(() => groupInquiriesByType(sidebarInquiries.value))
const mainInquiriesByType = computed(() => groupInquiriesByType(mainInquiries.value))

// Helper computed properties
const hasSidebarInquiries = computed(() => sidebarInquiries.value.length > 0)
const totalInquiries = computed(() => inquiries.value.length)

// Get inquiry type information
function getInquiryTypeData(typeKey: string) {
  return inquiryTypes.value.find(type => type.inquiry_type === typeKey)
}

function getInquiryTypeIcon(typeKey: string) {
  const typeData = getInquiryTypeData(typeKey)
  return typeData?.icon || 'FileDocument'
}

function getInquiryTypeLabel(typeKey: string) {
  const typeData = getInquiryTypeData(typeKey)
  return typeData?.label || typeKey
}

function getInquiryTypeDescription(typeKey: string) {
  const typeData = getInquiryTypeData(typeKey)
  return typeData?.description || ''
}

function getInquiryTypeFamily(typeKey: string) {
  const typeData = getInquiryTypeData(typeKey)
  return typeData?.family || 'default'
}

// Handle inquiry click
function handleInquiryClick(inquiry: Inquiry) {
  emit('viewInquiry', inquiry.id)
}

// View all of specific type
function viewAllOfType(typeKey: string) {
  // Implement navigation to filtered view
  console.log('View all of type:', typeKey)
}
</script>

<style lang="scss" scoped>
.inquiry-group-view-main {
  width: 100%;
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
}

/* Layout Container */
.layout-container {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 40px;
  max-width: 1600px;
  margin: 0 auto;
  padding: 40px;
  min-height: calc(100vh - 120px);

  @media (max-width: 1200px) {
    grid-template-columns: 1fr;
    gap: 32px;
    padding: 32px;
  }

  @media (max-width: 768px) {
    padding: 24px;
    gap: 24px;
  }
}

/* Main Content Area */
.layout-main {
  &.with-sidebar {
    grid-column: 1;
  }

  .header-section,
  .main-section,
  .footer-section {
    margin-bottom: 48px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;

    &:last-child {
      margin-bottom: 0;
    }
  }
}

/* Section Headers */
.section-header {
  padding: 24px 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;

  .section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 700;
    margin: 0;

    .section-icon {
      font-size: 28px;
      opacity: 0.9;
    }

    .section-count {
      background: rgba(255, 255, 255, 0.2);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 16px;
      font-weight: 600;
      margin-left: auto;
    }
  }
}

/* Inquiry Grids */
.inquiry-grid {
  padding: 32px;

  &.header-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
  }

  &.main-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
  }

  &.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
  }
}

/* Type Groups */
.type-groups {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Type Header - Beautiful Design */
.type-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  margin: 24px 32px 0;
  border: 1px solid rgba(0, 0, 0, 0.08);
  overflow: hidden;

  .type-header-content {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 20px;
    align-items: center;
    padding: 24px;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      gap: 16px;
    }
  }

  .type-icon-wrapper {
    width: 64px;
    height: 64px;
    background: white;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

    .type-icon {
      width: 32px;
      height: 32px;
      color: #667eea;
    }
  }

  .type-info {
    .type-name {
      font-size: 22px;
      font-weight: 700;
      color: #2d3748;
      margin: 0 0 8px 0;
    }

    .type-description {
      font-size: 14px;
      color: #718096;
      margin: 0;
      line-height: 1.5;
    }
  }

  .type-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;

    .type-badge {
      background: #667eea;
      color: white;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
    }

    .type-family {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 500;
    }
  }

  .type-actions {
    padding: 0 24px 24px;
    border-top: 1px solid rgba(0, 0, 0, 0.05);

    .type-action-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      background: transparent;
      border: none;
      color: #667eea;
      font-size: 14px;
      font-weight: 600;
      padding: 8px 16px;
      cursor: pointer;
      transition: all 0.2s ease;

      &:hover {
        color: #764ba2;

        svg {
          transform: translateX(4px);
        }
      }

      svg {
        fill: currentColor;
        transition: transform 0.2s ease;
      }
    }
  }
}

/* Type Inquiries */
.type-inquiries {
  padding: 32px;

  .main-grid {
    padding: 0;
  }
}

/* Right Sidebar */
.layout-sidebar {
  position: sticky;
  top: 40px;
  height: fit-content;
  max-height: calc(100vh - 120px);
  overflow-y: auto;

  @media (max-width: 1200px) {
    position: static;
    max-height: none;
  }

  .sidebar-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    color: white;
    padding: 24px;
    border-radius: 20px 20px 0 0;

    .sidebar-title {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 20px;
      font-weight: 700;
      margin: 0 0 8px 0;

      .sidebar-icon {
        font-size: 24px;
        opacity: 0.9;
      }

      .sidebar-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 16px;
        font-size: 14px;
        font-weight: 600;
        margin-left: auto;
      }
    }

    .sidebar-subtitle {
      font-size: 14px;
      opacity: 0.9;
      margin: 0;
    }
  }

  .sidebar-content {
    background: white;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-top: none;
    padding: 24px;
  }
}

/* Sidebar Type Groups */
.sidebar-type-groups {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sidebar-type-group {
  background: #f8fafc;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);

  .sidebar-type-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);

    .sidebar-type-icon {
      width: 24px;
      height: 24px;
      color: #4f46e5;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .sidebar-type-info {
      flex: 1;

      .sidebar-type-name {
        font-size: 16px;
        font-weight: 600;
        color: #2d3748;
        margin: 0 0 4px 0;
      }

      .sidebar-type-count {
        background: #e2e8f0;
        color: #4a5568;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
      }
    }
  }

  .sidebar-inquiries {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }
}

/* Responsive Design */
@media (max-width: 1200px) {
  .layout-main.with-sidebar {
    grid-column: 1;
  }

  .inquiry-grid {
    padding: 24px;

    &.main-grid {
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }
  }

  .type-header .type-header-content {
    padding: 20px;
  }
}

@media (max-width: 768px) {
  .layout-container {
    padding: 16px;
  }

  .section-header {
    padding: 20px 24px;

    .section-title {
      font-size: 20px;
    }
  }

  .inquiry-grid {
    padding: 20px;

    &.main-grid {
      grid-template-columns: 1fr;
    }

    &.header-grid,
    &.footer-grid {
      grid-template-columns: 1fr;
    }
  }

  .type-header {
    margin: 20px 24px 0;
  }
}
</style>
