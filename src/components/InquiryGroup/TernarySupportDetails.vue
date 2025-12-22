<!--
  - SPDX-FileCopyrightText: 2018-2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
  <NcModal
    :show="show"
    :name="title"
    @close="$emit('close')"
  >
    <div class="ternary-support-details">
      <h3>{{ title }}</h3>
      
      <div class="support-breakdown">
        <div class="breakdown-item positive">
          <div class="breakdown-header">
            <TernarySupportIcon :support-value="1" :size="20" />
            <span class="breakdown-label">{{ t('agora', 'In Favor') }}</span>
          </div>
          <div class="breakdown-counts">
            <span class="count">{{ positiveCount }}</span>
            <span class="percentage">({{ positivePercentage }}%)</span>
          </div>
          <div class="breakdown-bar">
            <div 
              class="bar-fill" 
              :style="{ width: `${positivePercentage}%` }"
            ></div>
          </div>
        </div>
        
        <div class="breakdown-item neutral">
          <div class="breakdown-header">
            <TernarySupportIcon :support-value="0" :size="20" />
            <span class="breakdown-label">{{ t('agora', 'Neutral') }}</span>
          </div>
          <div class="breakdown-counts">
            <span class="count">{{ neutralCount }}</span>
            <span class="percentage">({{ neutralPercentage }}%)</span>
          </div>
          <div class="breakdown-bar">
            <div 
              class="bar-fill" 
              :style="{ width: `${neutralPercentage}%` }"
            ></div>
          </div>
        </div>
        
        <div class="breakdown-item negative">
          <div class="breakdown-header">
            <TernarySupportIcon :support-value="-1" :size="20" />
            <span class="breakdown-label">{{ t('agora', 'Against') }}</span>
          </div>
          <div class="breakdown-counts">
            <span class="count">{{ negativeCount }}</span>
            <span class="percentage">({{ negativePercentage }}%)</span>
          </div>
          <div class="breakdown-bar">
            <div 
              class="bar-fill" 
              :style="{ width: `${negativePercentage}%` }"
            ></div>
          </div>
        </div>
      </div>
      
      <div class="support-summary">
        <div class="summary-item">
          <span class="summary-label">{{ t('agora', 'Total Participants') }}</span>
          <span class="summary-value">{{ totalParticipants }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">{{ t('agora', 'Quorum Required') }}</span>
          <span class="summary-value">{{ quorum || t('agora', 'None') }}</span>
        </div>
      </div>
    </div>
  </NcModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcModal from '@nextcloud/vue/components/NcModal'
import TernarySupportIcon from '../AppIcons/modules/TernarySupportIcon.vue'

interface Props {
  show: boolean
  inquiry: any
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: t('agora', 'Support Breakdown')
})

const emit = defineEmits(['close'])

const positiveCount = computed(() => props.inquiry.status?.countPositiveSupports || 0)
const neutralCount = computed(() => props.inquiry.status?.countNeutralSupports || 0)
const negativeCount = computed(() => props.inquiry.status?.countNegativeSupports || 0)
const totalParticipants = computed(() => props.inquiry.status?.countParticipants || 0)
const quorum = computed(() => props.inquiry.configuration?.quorum || 0)

const positivePercentage = computed(() => {
  if (totalParticipants.value === 0) return 0
  return Math.round((positiveCount.value / totalParticipants.value) * 100)
})

const neutralPercentage = computed(() => {
  if (totalParticipants.value === 0) return 0
  return Math.round((neutralCount.value / totalParticipants.value) * 100)
})

const negativePercentage = computed(() => {
  if (totalParticipants.value === 0) return 0
  return Math.round((negativeCount.value / totalParticipants.value) * 100)
})
</script>

<style lang="scss" scoped>
.ternary-support-details {
  padding: 20px;
  
  h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--color-main-text);
  }
  
  .support-breakdown {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
  }
  
  .breakdown-item {
    padding: 12px;
    border-radius: 8px;
    background: var(--color-background-dark);
    
    &.positive {
      border-left: 4px solid #10b981;
    }
    
    &.neutral {
      border-left: 4px solid #6b7280;
    }
    
    &.negative {
      border-left: 4px solid #ef4444;
    }
  }
  
  .breakdown-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    
    .breakdown-label {
      font-weight: 600;
      color: var(--color-main-text);
    }
  }
  
  .breakdown-counts {
    display: flex;
    justify-content: space-between;
    margin-bottom: 6px;
    
    .count {
      font-size: 18px;
      font-weight: 700;
    }
    
    .percentage {
      color: var(--color-text-lighter);
      font-size: 14px;
    }
  }
  
  .breakdown-bar {
    height: 6px;
    background: var(--color-border);
    border-radius: 3px;
    overflow: hidden;
    
    .bar-fill {
      height: 100%;
      transition: width 0.3s ease;
      
      .positive & {
        background: #10b981;
      }
      
      .neutral & {
        background: #6b7280;
      }
      
      .negative & {
        background: #ef4444;
      }
    }
  }
  
  .support-summary {
    display: flex;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid var(--color-border);
    
    .summary-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
      
      .summary-label {
        font-size: 12px;
        color: var(--color-text-lighter);
      }
      
      .summary-value {
        font-size: 16px;
        font-weight: 700;
        color: var(--color-main-text);
      }
    }
  }
}
</style>
