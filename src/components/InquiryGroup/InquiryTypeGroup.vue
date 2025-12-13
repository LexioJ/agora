<!--
- SPDX-FileCopyrightText: 2025 Nextcloud contributors
- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <div class="inquiry-type-group">
    <!-- Grouped by Type -->
    <div v-if="groupByType" class="type-groups">
      <div 
        v-for="(typeGroup, typeKey) in groupedInquiries" 
        :key="typeKey" 
        class="type-group"
      >
        <div class="type-header" @click="toggleType(typeKey)">
          <div class="type-info">
            <div class="type-icon">
              <component :is="getInquiryTypeIcon(typeKey)" />
            </div>
            <h3 class="type-name">{{ getInquiryTypeLabel(typeKey) }}</h3>
            <span class="type-badge">{{ typeGroup.length }}</span>
          </div>
          <div class="type-toggle" :class="{ rotated: expandedTypes.includes(typeKey) }">
            <svg width="16" height="16" viewBox="0 0 24 24">
              <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z" />
            </svg>
          </div>
        </div>
        
        <div v-show="expandedTypes.includes(typeKey)" class="type-content">
          <div class="inquiry-items" :class="`layout-${layoutZone}`">
            <InquiryItem
              v-for="inquiry in typeGroup"
              :key="inquiry.id"
              :inquiry="inquiry"
              :render-mode="getRenderMode(inquiry)"
              @click="handleClick(inquiry.id)"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Not Grouped -->
    <div v-else class="ungrouped-inquiries">
      <div class="inquiry-items" :class="`layout-${layoutZone}`">
        <InquiryItem
          v-for="inquiry in inquiries"
          :key="inquiry.id"
          :inquiry="inquiry"
          :render-mode="getRenderMode(inquiry)"
          @click="handleClick(inquiry.id)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import InquiryItem from '../Inquiry/InquiryItem.vue'
import type { Inquiry } from '../../Types/index.ts'

interface Props {
  inquiries: Inquiry[]
  inquiryTypes: any[]
  groupByType: boolean
  defaultExpanded?: boolean
  layoutZone: string
}

const props = withDefaults(defineProps<Props>(), {
  defaultExpanded: false
})

const emit = defineEmits<{
  viewInquiry: [id: number]
}>()

const expandedTypes = ref<string[]>([])

// Group inquiries by type
const groupedInquiries = computed(() => {
  const grouped: Record<string, Inquiry[]> = {}
  
  props.inquiries.forEach(inquiry => {
    const type = inquiry.type || 'default'
    if (!grouped[type]) grouped[type] = []
    grouped[type].push(inquiry)
  })
  
  return grouped
})

// Get render mode for inquiry in this layout zone
function getRenderMode(inquiry: Inquiry): string {
  // This would come from your helper function
  const renderMode = inquiry.miscFields?.render_mode || 'cards'
  return renderMode
}

// Get inquiry type icon/label
function getInquiryTypeIcon(typeKey: string) {
  const typeData = props.inquiryTypes.find(t => t.inquiry_type === typeKey)
  return typeData?.icon || '📝'
}

function getInquiryTypeLabel(typeKey: string) {
  const typeData = props.inquiryTypes.find(t => t.inquiry_type === typeKey)
  return typeData?.label || typeKey
}

// Handle type toggle
function toggleType(typeKey: string) {
  const index = expandedTypes.value.indexOf(typeKey)
  if (index > -1) {
    expandedTypes.value.splice(index, 1)
  } else {
    expandedTypes.value.push(typeKey)
  }
}

// Handle inquiry click
function handleClick(inquiryId: number) {
  emit('viewInquiry', inquiryId)
}

// Auto-expand if defaultExpanded is true
watch(() => props.groupedInquiries, (newGroups) => {
  if (props.defaultExpanded && newGroups) {
    expandedTypes.value = Object.keys(newGroups)
  }
}, { immediate: true })
</script>
