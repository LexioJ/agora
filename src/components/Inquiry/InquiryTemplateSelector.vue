<template>
  <NcActions 
    :menu-name="t('agora', 'Change view')" 
    class="template-selector"
    :aria-label="t('agora', 'Change view template')"
  >
    <template #icon>
      <component :is="InquiryGeneralIcons.layout" :size="20" />
    </template>
    
    <div class="template-header">
      <span class="template-title">{{ t('agora', 'Select template') }}</span>
    </div>
    
    <NcActionRadio
      v-for="template in availableTemplates"
      :key="template.id"
      :value="template.id"
      :model-value="currentTemplate.id"
      :name="`template-selector-${category}`"
      @update:model-value="setTemplate(template.id)"
    >
      <template #icon>
        <div class="template-preview" :style="getTemplateStyle(template)"></div>
      </template>
      
      <div class="template-info">
        <div class="template-name">{{ template.name }}</div>
        <div class="template-description">{{ template.description }}</div>
      </div>
    </NcActionRadio>
  </NcActions>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionRadio from '@nextcloud/vue/components/NcActionRadio'
import { InquiryGeneralIcons } from '../../utils/icons.ts'
import { useInquiryTemplates, type TemplateInfo } from '../../helpers/modules/InquiryTemplatesHelper.ts'

interface Props {
  category: 'edit' | 'view'
}

const props = defineProps<Props>()

const { getTemplates, getCurrentTemplate, setTemplate } = useInquiryTemplates()

const availableTemplates = computed(() => getTemplates(props.category))
const currentTemplate = computed(() => getCurrentTemplate(props.category))

const getTemplateStyle = (template: TemplateInfo) => ({
  'border-radius': '4px',
  'background': 'linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-element) 100%)',
  'border': `2px solid ${template.id === currentTemplate.value.id ? 'var(--color-primary)' : 'var(--color-border)'}`,
  'opacity': template.id === currentTemplate.value.id ? '1' : '0.7'
})
</script>

<style scoped lang="scss">
.template-selector {
  margin-left: auto;
}

.template-header {
  padding: 8px 12px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 4px;
}

.template-title {
  font-weight: 600;
  color: var(--color-text);
  font-size: 0.9rem;
}

.template-preview {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  margin-right: 8px;
  transition: all 0.2s ease;
}

.template-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.template-name {
  font-weight: 600;
  color: var(--color-text);
  font-size: 0.9rem;
}

.template-description {
  font-size: 0.75rem;
  color: var(--color-text-lighter);
  margin-top: 2px;
  line-height: 1.2;
}

:deep(.action-radio--checked) {
  .template-preview {
    border-color: var(--color-primary) !important;
    opacity: 1 !important;
  }
  
  .template-name {
    color: var(--color-primary);
  }
}

// Amélioration du hover
:deep(.action-radio:hover) {
  .template-preview {
    opacity: 0.9;
    transform: scale(1.05);
  }
}
</style>
