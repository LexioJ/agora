!-- SPDX-FileCopyrightText: 2024 Nextcloud contributors SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
  <div class="simple-tree-view">
    <!-- Simple header -->
    <div class="tree-header">
      <div class="header-content">
        <h2 v-if="useTitle">{{ documentTitle }}</h2>
        <div v-if="useDescription && documentDescription" class="header-description">
          {{ documentDescription }}
        </div>
        <div class="header-actions">
          <NcButton type="primary" @click="openAddOptionModal('chapter')">
            <template #icon>
              <component :is="InquiryOptionIcons.Plus" :size="20" />
            </template>
            {{ t('agora', 'Add Chapter') }}
          </NcButton>
        </div>
      </div>
    </div>

    <div class="tree-container">
      <!-- Left sidebar - Tree navigation -->
      <div class="tree-sidebar">
        <div class="sidebar-header">
          <h3>{{ t('agora', 'Chapters') }}</h3>
        </div>
        <div class="tree-list">
          <div
            v-for="chapter in chapters"
            :key="chapter.id"
            class="tree-item"
            :class="{ active: activeNodeId === chapter.id }"
            @click="setActiveNode(chapter)"
          >
            <div class="item-content">
              <component :is="InquiryOptionIcons.BookOpenVariant" :size="16" />
              <span class="item-label">{{
                chapter.title || chapter.label || t('agora', 'Untitled Chapter')
              }}</span>
            </div>

            <!-- Articles in this chapter -->
            <div class="child-items">
              <div
                v-for="article in getChildOptions(chapter.id, 'article')"
                :key="article.id"
                class="tree-item child"
                :class="{ active: activeNodeId === article.id }"
                @click.stop="setActiveNode(article)"
              >
                <div class="item-content">
                  <component :is="InquiryOptionIcons.FileDocument" :size="14" />
                  <span class="item-label">{{
                    article.title || article.label || t('agora', 'Untitled Article')
                  }}</span>
                </div>

                <!-- Amendments in this article -->
                <div class="child-items">
                  <div
                    v-for="amendment in getChildOptions(article.id, 'amendment')"
                    :key="amendment.id"
                    class="tree-item grandchild"
                    :class="{ active: activeNodeId === amendment.id }"
                    @click.stop="setActiveNode(amendment)"
                  >
                    <div class="item-content">
                      <component :is="InquiryOptionIcons.FileDocumentEdit" :size="12" />
                      <span class="item-label">{{
                        amendment.title || amendment.label || t('agora', 'Untitled Amendment')
                      }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="chapters.length === 0" class="empty-tree">
            <component :is="InquiryOptionIcons.BookOpenVariant" :size="32" />
            <p>{{ t('agora', 'No chapters yet') }}</p>
            <NcButton type="tertiary" @click="openAddOptionModal('chapter')">
              {{ t('agora', 'Create first chapter') }}
            </NcButton>
          </div>
        </div>
      </div>

      <!-- Right content area -->
      <div class="content-area">
        <div v-if="activeNode" class="content-panel">
          <!-- Content header -->
          <div class="content-header">
            <h2>{{ activeNode.title || activeNode.label || t('agora', 'Untitled') }}</h2>
            <div class="content-actions">
              <NcButton type="secondary" @click="openNodeDetail(activeNode)">
                <template #icon>
                  <component :is="InquiryOptionIcons.Pencil" :size="16" />
                </template>
                {{ t('agora', 'Edit') }}
              </NcButton>

              <!-- Add child buttons based on node type -->
              <NcButton
                v-if="activeNode.type === 'chapter'"
                type="tertiary"
                @click="openAddOptionModal('article', activeNode.id)"
              >
                <template #icon>
                  <component :is="InquiryOptionIcons.Plus" :size="16" />
                </template>
                {{ t('agora', 'Add Article') }}
              </NcButton>

              <NcButton
                v-else-if="activeNode.type === 'article'"
                type="tertiary"
                @click="openAddOptionModal('amendment', activeNode.id)"
              >
                <template #icon>
                  <component :is="InquiryOptionIcons.Plus" :size="16" />
                </template>
                {{ t('agora', 'Add Amendment') }}
              </NcButton>
            </div>
          </div>

          <!-- Content editor -->
          <div v-if="activeNode.text !== undefined" class="content-editor">
            <NcTextField
              v-model="editedContents[activeNode.id]"
              :label="t('agora', 'Content')"
              type="textarea"
              :rows="10"
              full-width
              :disabled="!canEditNode(activeNode)"
            />
            <div v-if="canEditNode(activeNode)" class="editor-actions">
              <NcButton
                type="primary"
                :disabled="!hasContentChanged(activeNode)"
                @click="saveNodeContent(activeNode)"
              >
                {{ t('agora', 'Save') }}
              </NcButton>
              <NcButton
                type="tertiary"
                :disabled="!hasContentChanged(activeNode)"
                @click="cancelEdit(activeNode.id)"
              >
                {{ t('agora', 'Cancel') }}
              </NcButton>
            </div>
          </div>

          <!-- STRUCTURE Family Layout -->
          <div class="structure-layout">
            <div class="structure-header">
              <h3>{{ t('agora', 'Structure') }}</h3>
              <p class="structure-description">
                {{ t('agora', 'Click on any item to edit its details') }}
              </p>
            </div>

            <div class="tree-view">
              <!-- Hierarchical tree view for chapters/articles -->
              <div v-for="chapter in chapters" :key="chapter.id" class="tree-node">
                <div class="node-content" @click="openNodeDetail(chapter)">
                  <component :is="InquiryOptionIcons.BookOpenVariant" :size="18" />
                  <span class="node-label">{{
                    chapter.title || chapter.label || t('agora', 'Untitled Chapter')
                  }}</span>
                  <div class="node-actions">
                    <NcButton
                      type="tertiary"
                      size="small"
                      :title="t('agora', 'Add Article to this Chapter')"
                      @click.stop="openAddOptionModal('article', chapter.id)"
                    >
                      <template #icon>
                        <component :is="InquiryOptionIcons.Plus" :size="14" />
                      </template>
                      {{ t('agora', 'Article') }}
                    </NcButton>
                    <NcButton
                      type="tertiary-no-background"
                      size="small"
                      :title="t('agora', 'Edit Chapter')"
                      @click.stop="openNodeDetail(chapter)"
                    >
                      <template #icon>
                        <component :is="InquiryOptionIcons.Pencil" :size="14" />
                      </template>
                    </NcButton>
                  </div>
                </div>

                <!-- Articles -->
                <div
                  v-for="article in getChildOptions(chapter.id, 'article')"
                  :key="article.id"
                  class="tree-node child"
                >
                  <div class="node-content" @click="openNodeDetail(article)">
                    <component :is="InquiryOptionIcons.FileDocument" :size="16" />
                    <span class="node-label">{{
                      article.title || article.label || t('agora', 'Untitled Article')
                    }}</span>
                    <div class="node-actions">
                      <NcButton
                        type="tertiary"
                        size="small"
                        :title="t('agora', 'Add Amendment to this Article')"
                        @click.stop="openAddOptionModal('amendment', article.id)"
                      >
                        <template #icon>
                          <component :is="InquiryOptionIcons.Plus" :size="14" />
                        </template>
                        {{ t('agora', 'Amendment') }}
                      </NcButton>
                      <NcButton
                        type="tertiary-no-background"
                        size="small"
                        :title="t('agora', 'Edit Article')"
                        @click.stop="openNodeDetail(article)"
                      >
                        <template #icon>
                          <component :is="InquiryOptionIcons.Pencil" :size="14" />
                        </template>
                      </NcButton>
                    </div>
                  </div>

                  <!-- Amendments -->
                  <div
                    v-for="amendment in getChildOptions(article.id, 'amendment')"
                    :key="amendment.id"
                    class="tree-node grandchild"
                  >
                    <div class="node-content" @click="openNodeDetail(amendment)">
                      <component :is="InquiryOptionIcons.FileDocumentEdit" :size="14" />
                      <span class="node-label">{{
                        amendment.title || amendment.label || t('agora', 'Untitled Amendment')
                      }}</span>
                      <div class="node-actions">
                        <NcButton
                          type="tertiary-no-background"
                          size="small"
                          :title="t('agora', 'Edit Amendment')"
                          @click.stop="openNodeDetail(amendment)"
                        >
                          <template #icon>
                            <component :is="InquiryOptionIcons.Pencil" :size="14" />
                          </template>
                        </NcButton>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty structure state -->
              <div v-if="chapters.length === 0" class="empty-structure">
                <component :is="InquiryOptionIcons.BookOpenVariant" :size="48" />
                <h4>{{ t('agora', 'No structure yet') }}</h4>
                <p>{{ t('agora', 'Start by creating your first chapter') }}</p>
                <NcButton type="primary" @click="openAddOptionModal('chapter')">
                  <template #icon>
                    <component :is="InquiryOptionIcons.Plus" :size="16" />
                  </template>
                  {{ t('agora', 'Create Chapter') }}
                </NcButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Welcome state -->
        <div v-else class="welcome-state">
          <component :is="InquiryOptionIcons.BookOpenVariant" :size="64" />
          <h2>{{ t('agora', 'Select a chapter or article') }}</h2>
          <p>{{ t('agora', 'Choose an item from the sidebar to view and edit its content') }}</p>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <OptionDetailModal
      v-if="showDetailModal && selectedNode"
      :option="selectedNode"
      :inquiry-id="inquiryId"
      @close="closeDetailModal"
      @updated="handleNodeUpdated"
      @deleted="handleNodeDeleted"
    />

    <AddOptionModal
      v-if="showAddOptionModal"
      :inquiry-id="inquiryId"
      :option-type="selectedOptionTypeKey"
      :parent-id="selectedParentId"
      @close="closeAddOptionModal"
      @created="handleOptionCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { useInquiryStore } from '../../stores/inquiry'
import { useOptionsStore } from '../../stores/options'
import { useSessionStore } from '../../stores/session'
import { InquiryOptionIcons } from '../../utils/icons.ts'

import AddOptionModal from './AddOptionModal.vue'
import OptionDetailModal from './OptionDetailModal.vue'

// Props
interface Props {
  inquiryId: number
  useTitle?: boolean
  useDescription?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  useTitle: true,
  useDescription: true,
})

// Stores
const inquiryStore = useInquiryStore()
const optionsStore = useOptionsStore()
const sessionStore = useSessionStore()

// State
const activeNodeId = ref<number | null>(null)
const selectedNode = ref<any>(null)
const editedContents = ref<Record<number, string>>({})
const showAddOptionModal = ref(false)
const showDetailModal = ref(false)
const selectedOptionTypeKey = ref<string | null>(null)
const selectedParentId = ref<number | null>(null)

// Computed properties
const documentTitle = computed(() => {
  const proposal = optionsStore.options.find((opt) => opt.type === 'proposal')
  return proposal?.title || inquiryStore.inquiry?.label || t('agora', 'Untitled Proposal')
})

const documentDescription = computed(() => {
  const proposal = optionsStore.options.find((opt) => opt.type === 'proposal')
  return proposal?.text || inquiryStore.inquiry?.description || ''
})

const chapters = computed(() =>
  optionsStore.options.filter(
    (opt) => opt.type === 'chapter' && (!opt.parentId || opt.parentId === 0)
  )
)

const activeNode = computed(() => {
  if (!activeNodeId.value) return null
  return optionsStore.options.find((opt) => opt.id === activeNodeId.value)
})

// Helper methods
const getChildOptions = (parentId: number, type?: string) => {
  const children = optionsStore.options.filter((opt) => opt.parentId === parentId)
  if (type) {
    return children.filter((opt) => opt.type === type)
  }
  return children
}

const truncateText = (text: string, maxLength: number): string => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return `${text.substring(0, maxLength)}...`
}

// Permission checks
const canEditNode = (node: any) => sessionStore.currentUser?.id === node.owner?.id

const hasContentChanged = (node: any) => editedContents.value[node.id] !== node.text

// Methods
const setActiveNode = (node: any) => {
  activeNodeId.value = node.id
  selectedNode.value = null

  // Initialize edited content
  if (!editedContents.value[node.id]) {
    editedContents.value[node.id] = node.text || ''
  }
}

const openNodeDetail = (node: any) => {
  selectedNode.value = node
  showDetailModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedNode.value = null
}

const cancelEdit = (nodeId: number) => {
  const node = optionsStore.options.find((opt) => opt.id === nodeId)
  if (node) {
    editedContents.value[nodeId] = node.text || ''
  }
}

const saveNodeContent = async (node: any) => {
  if (!editedContents.value[node.id]) return

  try {
    // Update local store
    const index = optionsStore.options.findIndex((opt) => opt.id === node.id)
    if (index >= 0) {
      optionsStore.options[index] = {
        ...optionsStore.options[index],
        text: editedContents.value[node.id],
      }
    }
  } catch (error) {
    console.error('Error saving node:', error)
  }
}

const openAddOptionModal = (optionTypeKey: string, parentId?: number) => {
  selectedOptionTypeKey.value = optionTypeKey
  selectedParentId.value = parentId || null
  showAddOptionModal.value = true
}

const closeAddOptionModal = () => {
  showAddOptionModal.value = false
  selectedOptionTypeKey.value = null
  selectedParentId.value = null
}

const handleNodeUpdated = (updatedNode: any) => {
  const index = optionsStore.options.findIndex((opt) => opt.id === updatedNode.id)
  if (index >= 0) {
    optionsStore.options[index] = updatedNode
  }

  if (activeNodeId.value === updatedNode.id) {
    editedContents.value[updatedNode.id] = updatedNode.text || ''
  }

  closeDetailModal()
}

const handleNodeDeleted = (deletedNodeId: number) => {
  const index = optionsStore.options.findIndex((opt) => opt.id === deletedNodeId)
  if (index >= 0) {
    optionsStore.options.splice(index, 1)
  }

  if (activeNodeId.value === deletedNodeId) {
    activeNodeId.value = null
  }

  closeDetailModal()
}

const handleOptionCreated = (newOption: any) => {
  optionsStore.options.push(newOption)
  closeAddOptionModal()

  // If it's a chapter and no active node, select it
  if (newOption.type === 'chapter' && !activeNodeId.value) {
    setActiveNode(newOption)
  }
}

// Initialize
onMounted(() => {
  if (inquiryStore.id) {
    optionsStore.load(inquiryStore.id).then(() => {
      if (chapters.value.length > 0 && !activeNodeId.value) {
        setActiveNode(chapters.value[0])
      }
    })
  }
})
</script>

<style scoped lang="scss">
.simple-tree-view {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 120px);
  background: var(--color-main-background);
  border-radius: 16px;
  overflow: hidden;
}

.tree-header {
  background: var(--color-main-background);
  border-bottom: 2px solid var(--color-border);
  padding: 20px 24px;
  flex-shrink: 0;

  .header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;

    h2 {
      margin: 0;
      font-size: 24px;
      font-weight: 700;
      color: var(--color-main-text);
      flex: 1;
    }

    .header-description {
      color: var(--color-text-lighter);
      font-size: 14px;
      max-width: 400px;
    }

    .header-actions {
      flex-shrink: 0;
    }
  }
}

.tree-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.tree-sidebar {
  width: 280px;
  background: var(--color-background-dark);
  border-right: 2px solid var(--color-border);
  display: flex;
  flex-direction: column;
  overflow: hidden;

  .sidebar-header {
    padding: 16px;
    border-bottom: 1px solid var(--color-border);

    h3 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: var(--color-main-text);
    }
  }

  .tree-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px;

    .tree-item {
      margin-bottom: 4px;

      &.active {
        .item-content {
          background: var(--color-primary-light);
          border-color: var(--color-primary-element);
          color: var(--color-primary-element);
        }
      }

      &.child {
        margin-left: 24px;
        margin-top: 4px;

        .item-content {
          font-size: 14px;
        }
      }

      &.grandchild {
        margin-left: 48px;
        margin-top: 4px;

        .item-content {
          font-size: 13px;
        }
      }

      .item-content {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: var(--color-main-background);
        border: 1px solid var(--color-border);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;

        &:hover {
          background: var(--color-background-darker);
          border-color: var(--color-primary-element);
        }

        .item-label {
          flex: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }

      .child-items {
        margin-top: 4px;
      }
    }

    .empty-tree {
      padding: 40px 20px;
      text-align: center;
      color: var(--color-text-lighter);

      svg {
        margin-bottom: 16px;
        opacity: 0.5;
      }

      p {
        margin: 0 0 16px 0;
        font-style: italic;
      }
    }
  }
}

.content-area {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  background: var(--color-main-background);

  .content-panel {
    max-width: 1000px;
    margin: 0 auto;
  }

  .content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--color-border);

    h2 {
      margin: 0;
      font-size: 28px;
      font-weight: 700;
      color: var(--color-main-text);
    }

    .content-actions {
      display: flex;
      gap: 8px;
    }
  }

  .content-editor {
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 32px;

    .editor-actions {
      display: flex;
      gap: 8px;
      margin-top: 16px;
    }
  }

  /* STRUCTURE LAYOUT STYLES */
  .structure-layout {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid var(--color-border);

    .structure-header {
      margin-bottom: 24px;

      h3 {
        margin: 0 0 8px 0;
        font-size: 20px;
        font-weight: 600;
        color: var(--color-main-text);
      }

      .structure-description {
        margin: 0;
        font-size: 14px;
        color: var(--color-text-lighter);
        font-style: italic;
      }
    }

    .tree-view {
      .tree-node {
        margin-bottom: 8px;

        &.child {
          margin-left: 32px;
          margin-top: 8px;
        }

        &.grandchild {
          margin-left: 64px;
          margin-top: 6px;
        }

        .node-content {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px 16px;
          background: var(--color-background-dark);
          border: 1px solid var(--color-border);
          border-radius: 8px;
          cursor: pointer;
          transition: all 0.2s ease;

          &:hover {
            background: var(--color-background-darker);
            border-color: var(--color-primary-element);
            transform: translateX(4px);
          }

          svg {
            flex-shrink: 0;
            color: var(--color-primary-element);
          }

          .node-label {
            flex: 1;
            font-weight: 500;
            color: var(--color-main-text);
            font-size: 15px;
          }

          .node-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;

            button {
              white-space: nowrap;
            }
          }
        }
      }

      .empty-structure {
        text-align: center;
        padding: 48px 24px;
        background: var(--color-background-dark);
        border: 2px dashed var(--color-border);
        border-radius: 16px;

        svg {
          color: var(--color-primary-element);
          margin-bottom: 20px;
          opacity: 0.6;
        }

        h4 {
          margin: 0 0 8px 0;
          font-size: 18px;
          font-weight: 600;
          color: var(--color-main-text);
        }

        p {
          margin: 0 0 20px 0;
          color: var(--color-text-lighter);
          font-style: italic;
        }
      }
    }
  }

  .welcome-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    text-align: center;
    padding: 40px;

    svg {
      color: var(--color-primary-element);
      margin-bottom: 24px;
      opacity: 0.8;
    }

    h2 {
      margin: 0 0 16px 0;
      font-size: 24px;
      font-weight: 700;
      color: var(--color-main-text);
    }

    p {
      margin: 0;
      font-size: 16px;
      color: var(--color-text-lighter);
      line-height: 1.5;
      max-width: 500px;
    }
  }
}

@media (max-width: 900px) {
  .tree-container {
    flex-direction: column;
  }

  .tree-sidebar {
    width: 100%;
    max-height: 300px;
    border-right: none;
    border-bottom: 2px solid var(--color-border);
  }

  .content-area {
    padding: 16px;
  }

  .content-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;

    .content-actions {
      justify-content: flex-start;
    }
  }

  .structure-layout {
    .tree-view {
      .tree-node {
        &.child {
          margin-left: 24px;
        }

        &.grandchild {
          margin-left: 48px;
        }

        .node-content {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;

          .node-label {
            width: 100%;
          }

          .node-actions {
            width: 100%;
            justify-content: flex-end;
          }
        }
      }
    }
  }
}

@media (max-width: 480px) {
  .tree-view {
    .tree-node {
      &.child {
        margin-left: 16px !important;
      }

      &.grandchild {
        margin-left: 32px !important;
      }
    }
  }
}
</style>
