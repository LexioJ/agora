<!--
	- SPDX-FileCopyrightText: 2018 Nextcloud contributors
	- SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { showError } from '@nextcloud/dialogs'
import { useRouter } from 'vue-router'
import { Inquiry, useInquiryStore } from '../../stores/inquiry.ts'
import InquiryItem from './InquiryItem.vue'
import { useSessionStore } from '../../stores/session.ts'
import NcButton from '@nextcloud/vue/components/NcButton'
import HomeIcon from 'vue-material-design-icons/Home.vue'
import { useCommentsStore } from '../../stores/comments'
import { usePreferencesStore } from '../../stores/preferences.ts'

const props = defineProps({
  isLoadedParent: {
    type: Boolean,
    required: true,
  },
})

const inquiryParent = ref({
  id: null,
  title: '',
  type: '',
  parentId: 0,
  created: null,
  lastInteraction: null,
  owner: '',
  inquiryGroups: [],
  participatedCount: 0,
  commentCount: 0,
  supportCount: 0,
})

const router = useRouter()
const inquiryStore = useInquiryStore()
const commentsStore = useCommentsStore()
const preferencesStore = usePreferencesStore()
const sessionStore = useSessionStore()

const isLoadedLocal = ref(false)
const isMobile = ref(window.innerWidth < 768)

// Computed for inquiry types from app settings
const inquiryTypes = computed(() => {
  return sessionStore.appSettings.inquiryTypeTab || []
})

// Group children by their inquiry type
const childrenByType = computed(() => {
  const grouped: Record<string, any[]> = {}
  
  inquiryStore.childs.forEach(child => {
    const typeInfo = inquiryTypes.value.find(t => t.inquiry_type === child.type)
    const typeKey = typeInfo?.label || child.type
    
    if (!grouped[typeKey]) {
      grouped[typeKey] = []
    }
    grouped[typeKey].push(child)
  })
  
  return grouped
})

// Get unique child types for display
const childTypes = computed(() => {
  return Object.keys(childrenByType.value)
})

const isGridView = computed(() => preferencesStore.user.defaultViewInquiry === 'table-view')

const emit = defineEmits(['editParent', 'routeChild'])

const editParent = () => {
  emit('editParent')
}

const routeChild = (inquiryId: number) => {
  emit('routeChild', inquiryId)
}

onMounted(async () => {
  if (props.isLoadedParent) {
    try {
      isLoadedLocal.value = false
      await loadInquiryData()
    } catch (error) {
      showError('Failed to load inquiry:', error)
    } finally {
      inquiryParent.value.id = inquiryStore.id
      inquiryParent.value.parentId = inquiryStore.parentId
      inquiryParent.value.title = inquiryStore.title
      inquiryParent.value.type = inquiryStore.type
      inquiryParent.value.owner = inquiryStore.owner
      inquiryParent.value.moderationStatus = inquiryStore.moderationStatus
      inquiryParent.value.status = inquiryStore.status
      inquiryParent.value.configuration = inquiryStore.configuration
      inquiryParent.value.currentUserStatus = inquiryStore.currentUserStatus
      inquiryParent.value.commentCount = commentsStore.comments.length
      inquiryParent.value.supportCount = inquiryStore.status.countSupports
      inquiryParent.value.inquiryGroups = inquiryStore.inquiryGroups

      isLoadedLocal.value = true
    }
  }

  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

const handleResize = () => {
  isMobile.value = window.innerWidth < 768
}

// Transform owner data for display
function transformOwner(obj) {
  if (obj.owner && typeof obj.owner === 'string') {
    obj.owner = {
      id: obj.owner,
      displayName: obj.owner,
    }
  }
  return obj
}

const loadInquiryData = async () => {
  // Transform all children owners
  inquiryStore.childs = inquiryStore.childs.map(transformOwner)
  return true
}

// Go to home page
const navigateToRoot = () => {
  router.push({ name: 'root' })
}
</script>

<template>
  <div v-if="!isLoadedLocal" class="loading-container">
    <div class="loading-spinner" />
    <p>{{ t('agora', 'Loading inquiry') }}</p>
  </div>

  <div v-else class="transition-container">
    <!-- Navigation -->
    <div class="navigation-controls">
      <NcButton type="secondary" @click="navigateToRoot">
        <HomeIcon /><span v-if="!isMobile">{{ t('agora', 'Home') }}</span>
      </NcButton>
    </div>

    <!-- Parent Inquiry -->
    <Transition name="fade">
      <div v-if="isLoadedLocal" class="parent-section">
        <div class="section-header">
          <h2 class="section-title">
            {{ t('agora', 'Main Inquiry') }}
          </h2>
        </div>
        <div class="parent-inquiry-wrapper">
          <InquiryItem
            :key="inquiryParent.id"
            :inquiry="inquiryParent"
            :no-link="false"
            :grid-view="false"
            @click="editParent"
          />
        </div>
      </div>
    </Transition>

    <!-- Children Sections -->
    <TransitionGroup name="slide-fade" tag="div" class="children-sections">
      <div 
        v-for="type in childTypes" 
        :key="type" 
        class="child-section"
      >
        <div class="section-header">
          <h3 class="section-title">
            {{ type }}
          </h3>
          <span class="section-count">
            {{ childrenByType[type].length }}
          </span>
        </div>
        
        <div class="inquiry-grid">
          <InquiryItem
            v-for="child in childrenByType[type]"
            :key="child.id"
            :inquiry="child"
            :no-link="false"
            :grid-view="true"
            @click="routeChild(child.id)"
          />
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped lang="scss">
.transition-container {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;

  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--color-background-darker);
    border-top: 4px solid var(--color-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
  }

  p {
    color: var(--color-text-lighter);
    font-size: 16px;
  }
}

/* Transition effects */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.navigation-controls {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 2rem;

  button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 500;
  }
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 12px;
  border-bottom: 2px solid var(--color-border);

  .section-title {
    margin: 0;
    color: var(--color-main-text);
    font-weight: 600;
    font-size: 1.5rem;
  }

  .section-count {
    background: var(--color-primary-element);
    color: var(--color-primary-text);
    border-radius: 12px;
    padding: 4px 12px;
    font-size: 14px;
    font-weight: 600;
  }
}

.parent-section {
  margin-bottom: 3rem;
  
  .parent-inquiry-wrapper {
    background: var(--color-main-background);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--color-border);
  }
}

.children-sections {
  display: flex;
  flex-direction: column;
  gap: 3rem;
}

.child-section {
  .inquiry-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
  }
}

/* Responsive Design */
@media (max-width: 1400px) {
  .child-section .inquiry-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 1024px) {
  .transition-container {
    padding: 16px;
  }
  
  .child-section .inquiry-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }
  
  .section-header .section-title {
    font-size: 1.3rem;
  }
}

@media (max-width: 768px) {
  .transition-container {
    padding: 12px;
  }
  
  .navigation-controls {
    margin-bottom: 1.5rem;
  }
  
  .child-section .inquiry-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .parent-section {
    margin-bottom: 2rem;
    
    .parent-inquiry-wrapper {
      padding: 16px;
    }
  }
  
  .children-sections {
    gap: 2rem;
  }
  
  .section-header {
    margin-bottom: 1rem;
    
    .section-title {
      font-size: 1.2rem;
    }
  }
}

@media (max-width: 480px) {
  .transition-container {
    padding: 8px;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    
    .section-count {
      align-self: flex-end;
    }
  }
}
</style>
