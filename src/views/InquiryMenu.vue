<!--
	- SPDX-FileCopyrightText: 2018 Nextcloud Contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'

import { HeaderBar } from '../components/Base/index.ts'
import { AgoraAppIcon } from '../components/AppIcons/index.ts'
import { useSessionStore } from '../stores/session.ts'
import { InquiryGeneralIcons, StatusIcons } from '../utils/icons.ts'

const route = useRoute()
const sessionStore = useSessionStore()

// State for selected family
const selectedFamily = ref<string | null>(route.params.familyId as string || null)

// Computed for available families
const inquiryFamilies = computed(() => {
  return sessionStore.appSettings.inquiryFamilyTab || []
})

// Computed for inquiry types filtered by selected family
const filteredInquiryTypes = computed(() => {
  if (!selectedFamily.value) return []
  
  const family = inquiryFamilies.value.find(f => f.id.toString() === selectedFamily.value)
  if (!family) return []
  
  return sessionStore.appSettings.inquiryTypeTab?.filter(type => 
    type.family === family.inquiry_type
  ) || []
})

// Get family by ID
const currentFamily = computed(() => {
  if (!selectedFamily.value) return null
  return inquiryFamilies.value.find(f => f.id.toString() === selectedFamily.value)
})

// Function to get icon component for family
const getFamilyIcon = (family: any) => {
  const iconName = family?.icon?.toLowerCase() || 'accountgroup'
  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
}

// Function to get icon component for inquiry type
const getInquiryTypeIcon = (inquiryType: any) => {
  const iconName = inquiryType?.icon?.toLowerCase() || 'filedocumentedit'
  return InquiryGeneralIcons[iconName] || StatusIcons[iconName] || InquiryGeneralIcons.activity
}

// Function to select a family
function selectFamily(familyId: string) {
  selectedFamily.value = familyId
}

// Function to clear family selection
function clearFamilySelection() {
  selectedFamily.value = null
}

// Empty content props
const emptyContentProps = computed(() => ({
  name: selectedFamily.value 
    ? t('agora', 'No inquiry types found for this family')
    : t('agora', 'No inquiry families configured'),
  description: selectedFamily.value
    ? t('agora', 'Please check the configuration or select another family')
    : t('agora', 'Please configure inquiry families in the settings'),
}))

</script>

<template>
  <NcAppContent class="inquiry-menu">
    <HeaderBar>
      <template #title>
        {{ selectedFamily 
          ? currentFamily?.label || t('agora', 'Inquiry Types')
          : t('agora', 'Select Inquiry Family') 
        }}
      </template>
      <span v-if="selectedFamily && currentFamily?.description">
        {{ currentFamily.description }}
      </span>
      <span v-else-if="!selectedFamily">
        {{ t('agora', 'Choose a family to see available inquiry types') }}
      </span>
    </HeaderBar>

    <!-- Family Selection Grid -->
    <div v-if="!selectedFamily" class="families-grid-container">
      <div class="families-grid">
        <div
          v-for="family in inquiryFamilies"
          :key="family.id"
          class="family-card-large"
          @click="selectFamily(family.id.toString())"
        >
          <div class="family-card-large__icon">
		<component :is="getFamilyIcon(family)" />
          </div>
          <div class="family-card-large__content">
            <h3 class="family-card-large__title">{{ family.label }}</h3>
            <p class="family-card-large__description" v-if="family.description">
              {{ family.description }}
            </p>
            <div class="family-card-large__meta">
              <span class="inquiry-count">
                {{ 
                  sessionStore.appSettings.inquiryTypeTab?.filter(t => t.family === family.inquiry_type).length || 0 
                }} inquiry types
              </span>
            </div>
          </div>
        </div>
      </div>

      <NcEmptyContent 
        v-if="inquiryFamilies.length === 0" 
        v-bind="emptyContentProps"
      >
        <template #icon>
          <AgoraAppIcon />
        </template>
      </NcEmptyContent>
    </div>

    <!-- Inquiry Types Grid for Selected Family -->
    <div v-else class="inquiry-types-container">
      <div class="inquiry-types-header">
        <button class="back-button" @click="clearFamilySelection">
          <span class="back-button__icon">←</span>
          {{ t('agora', 'Back to families') }}
        </button>
        
        <div class="selected-family-info">
          <h2>{{ currentFamily?.label }}</h2>
          <p v-if="currentFamily?.description">{{ currentFamily.description }}</p>
        </div>
      </div>

      <div class="inquiry-types-grid">
        <div
          v-for="inquiryType in filteredInquiryTypes"
          :key="inquiryType.id"
          class="inquiry-type-card"
          @click="$emit('create-inquiry', inquiryType)"
        >
          <div class="inquiry-type-card__icon">
		  <component :is="getInquiryTypeIcon(inquiryType)" />
          </div>
          <div class="inquiry-type-card__content">
            <h4 class="inquiry-type-card__title">{{ inquiryType.label }}</h4>
            <p class="inquiry-type-card__description" v-if="inquiryType.description">
              {{ inquiryType.description }}
            </p>
            <div class="inquiry-type-card__meta" v-if="inquiryType.allowed_response">
              <span class="response-types">
                Responses: {{ inquiryType.allowed_response.join(', ') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <NcEmptyContent 
        v-if="filteredInquiryTypes.length === 0" 
        v-bind="emptyContentProps"
      >
        <template #icon>
          <AgoraAppIcon />
        </template>
      </NcEmptyContent>
    </div>
  </NcAppContent>
</template>

<style lang="scss" scoped>
.inquiry-menu {
  .area__main {
    width: 100%;
  }
}

/* Large Family Cards Grid */
.families-grid-container {
  padding: 20px;
}

.families-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.family-card-large {
  background: var(--color-main-background);
  border: 2px solid var(--color-border);
  border-radius: 20px;
  padding: 32px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  min-height: 240px;
  justify-content: center;
  position: relative;

  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
  }

  &__icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--color-primary-element), var(--color-primary-element-hover));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;

    .family-icon {
      font-size: 36px;
      color: white;
    }
  }

  &__title {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 12px;
    color: var(--color-main-text);
    line-height: 1.3;
  }

  &__description {
    color: var(--color-text-lighter);
    font-size: 16px;
    line-height: 1.5;
    margin-bottom: 16px;
  }

  &__meta {
    margin-top: auto;
    
    .inquiry-count {
      background: var(--color-background-dark);
      color: var(--color-text-lighter);
      border-radius: 12px;
      padding: 6px 12px;
      font-size: 13px;
      font-weight: 600;
    }
  }
}

/* Inquiry Types Container */
.inquiry-types-container {
  padding: 20px;
}

.inquiry-types-header {
  display: flex;
  align-items: flex-start;
  gap: 24px;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);

  .back-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    padding: 10px 18px;
    cursor: pointer;
    color: var(--color-text-lighter);
    transition: all 0.2s ease;
    flex-shrink: 0;

    &:hover {
      background: var(--color-background-dark);
      color: var(--color-main-text);
      border-color: var(--color-primary-element);
    }

    &__icon {
      font-size: 18px;
      font-weight: 600;
    }
  }

  .selected-family-info {
    flex: 1;
    
    h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--color-main-text);
    }

    p {
      color: var(--color-text-lighter);
      font-size: 16px;
      line-height: 1.5;
      margin: 0;
    }
  }
}

/* Standard Size Inquiry Types Grid */
.inquiry-types-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.inquiry-type-card {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  padding: 24px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  min-height: 140px;

  &:hover {
    border-color: var(--color-primary-element);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
  }

  &__icon {
    width: 48px;
    height: 48px;
    background: var(--color-background-dark);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    .inquiry-type-icon {
      font-size: 24px;
      color: var(--color-primary-element);
    }
  }

  &__content {
    flex: 1;
  }

  &__title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--color-main-text);
    line-height: 1.3;
  }

  &__description {
    color: var(--color-text-lighter);
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 12px;
  }

  &__meta {
    .response-types {
      background: var(--color-background-darker);
      color: var(--color-text-lighter);
      border-radius: 8px;
      padding: 4px 8px;
      font-size: 11px;
      font-weight: 500;
    }
  }
}

/* Responsive Design */
@media (max-width: 1400px) {
  .families-grid {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
  }
}

@media (max-width: 1024px) {
  .families-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
  }
  
  .inquiry-types-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

@media (max-width: 768px) {
  .families-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .inquiry-types-grid {
    grid-template-columns: 1fr;
  }
  
  .family-card-large {
    padding: 24px;
    min-height: 200px;
    
    &__icon {
      width: 64px;
      height: 64px;
      margin-bottom: 16px;
      
      .family-icon {
        font-size: 28px;
      }
    }
    
    &__title {
      font-size: 20px;
    }
  }
  
  .inquiry-types-header {
    flex-direction: column;
    gap: 16px;
    
    .selected-family-info h2 {
      font-size: 24px;
    }
  }
}

/* Dark theme adjustments */
.theme--dark {
  .family-card-large {
    background: var(--color-background-dark);
    
    &:hover {
      background: var(--color-background-darker);
    }
  }
  
  .inquiry-type-card {
    background: var(--color-background-dark);
    
    &:hover {
      background: var(--color-background-darker);
    }
  }
}
</style>
