/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { InquiryLinksAPI } from '../Api/index.ts'
import type { AxiosError } from '@nextcloud/axios'
import { Logger } from '../helpers/index.ts'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import  { useInquiryStore } from './inquiry.ts'

export interface InquiryLink {
    id: number
    inquiry_id: number
    target_app: string
    target_type: string
    target_id: string
    sort_order: number
    created_by: number
    created_at?: string
    updated_at?: string
}

export interface CreateLinkData {
    inquiryId: number
    targetApp: string
    targetType: string
    targetId: string
    sortOrder?: number
    createdBy?: number
}

export interface UpdateLinkData {
    targetApp: string
    targetType: string
    targetId: string
    sortOrder?: number
}

export interface CreateAppResourceData {
    title: string
    description?: string
    stackId?: number
    amount?: number
}

export type InquiryLinkType = {
    id: number
    inquiry_id: number
    target_app: string
    target_type: string
    target_id: string
    sort_order: number
    created_by: number
}

export type InquiryLinksState = {
    links: InquiryLinkType[]
}

export const useInquiryLinksStore = defineStore('inquiryLinks', {
    state: (): InquiryLinksState => ({
        links: [],
    }),

    getters: {
        getByInquiryId: (state) => (inquiryId: number) =>
        state.links.filter((link) => link.inquiry_id === inquiryId)
        .sort((a, b) => a.sort_order - b.sort_order),

            getByTarget: (state) => (targetApp: string, targetType: string, targetId: string) =>
        state.links.filter((link) => 
                           link.target_app === targetApp && 
                               link.target_type === targetType && 
                               link.target_id === targetId
                          ),

                          getByTargetApp: (state) => (targetApp: string) =>
                          state.links.filter((link) => link.target_app === targetApp),

                              getById: (state) => (id: number) =>
                          state.links.find((link) => link.id === id),

                              // Additional getters for each app
                              getFormsByInquiryId: (state) => (inquiryId: number) =>
                          state.links.filter((link) => 
                                             link.inquiry_id === inquiryId && 
                                                 link.target_app === 'forms'
                                            ).sort((a, b) => a.sort_order - b.sort_order),

                                                getCospendProjectsByInquiryId: (state) => (inquiryId: number) =>
                                            state.links.filter((link) => 
                                                               link.inquiry_id === inquiryId && 
                                                                   link.target_app === 'cospend'
                                                              ).sort((a, b) => a.sort_order - b.sort_order),

                                                                  getCollectivesByInquiryId: (state) => (inquiryId: number) =>
                                                              state.links.filter((link) => 
                                                                                 link.inquiry_id === inquiryId && 
                                                                                     link.target_app === 'collectives'
                                                                                ).sort((a, b) => a.sort_order - b.sort_order),

                                                                                    getPollsByInquiryId: (state) => (inquiryId: number) =>
                                                                                state.links.filter((link) => 
                                                                                                   link.inquiry_id === inquiryId && 
                                                                                                       link.target_app === 'polls'
                                                                                                  ).sort((a, b) => a.sort_order - b.sort_order),

                                                                                                      getDeckBoardsByInquiryId: (state) => (inquiryId: number) =>
                                                                                                  state.links.filter((link) => 
                                                                                                                     link.inquiry_id === inquiryId && 
                                                                                                                         link.target_app === 'deck'
                                                                                                                    ).sort((a, b) => a.sort_order - b.sort_order),
    },

                                                                                                                    actions: {
                                                                                                                        async loadByInquiry(inquiryId: number) {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.getLinksByInquiry(inquiryId)
                                                                                                                                // Remove existing links for this inquiry
                                                                                                                                this.links = this.links.filter((link) => link.inquiry_id !== inquiryId)
                                                                                                                                // Add new ones
                                                                                                                                this.links.push(...response.data.links)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error loading inquiry links', { error, inquiryId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async loadByTarget(targetApp: string, targetType: string, targetId: string) {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.getLinksByTarget(targetApp, targetType, targetId)
                                                                                                                                // Remove existing links for this target
                                                                                                                                this.links = this.links.filter((link) => 
                                                                                                                                                               !(link.target_app === targetApp && 
                                                                                                                                                                 link.target_type === targetType && 
                                                                                                                                                                 link.target_id === targetId)
                                                                                                                                                              )
                                                                                                                                                              // Add new ones
                                                                                                                                                              this.links.push(...response.data.links)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error loading target links', { error, targetApp, targetType, targetId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async loadByTargetApp(targetApp: string) {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.getLinksByTargetApp(targetApp)
                                                                                                                                // Remove existing links for this app
                                                                                                                                this.links = this.links.filter((link) => link.target_app !== targetApp)
                                                                                                                                // Add new ones
                                                                                                                                this.links.push(...response.data.links)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error loading app links', { error, targetApp })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async load(id: number) {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.getLink(id)
                                                                                                                                // Remove existing link with this ID
                                                                                                                                this.links = this.links.filter((link) => link.id !== id)
                                                                                                                                // Add new one
                                                                                                                                this.links.push(response.data.link)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error loading link', { error, id })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async create(data: CreateLinkData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.createLink(data)
                                                                                                                                const newLink = response.data.link
                                                                                                                                this.links.push(newLink)
                                                                                                                                return newLink
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                                                                                                                                    Logger.error('Error creating link', { error, data })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async createMultiple(inquiryId: number, linksData: Omit<CreateLinkData, 'inquiryId'>[]) {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.createMultipleLinks(inquiryId, linksData)
                                                                                                                                // Remove existing links for this inquiry
                                                                                                                                this.links = this.links.filter((link) => link.inquiry_id !== inquiryId)
                                                                                                                                // Add new ones
                                                                                                                                this.links.push(...response.data.links)
                                                                                                                                return response.data.links
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error creating multiple links', { error, inquiryId, linksData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async update(id: number, data: UpdateLinkData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                const response = await InquiryLinksAPI.updateLink(id, data)
                                                                                                                                const updatedLink = response.data.link
                                                                                                                                // Update in store
                                                                                                                                const index = this.links.findIndex((link) => link.id === id)
                                                                                                                                if (index !== -1) {
                                                                                                                                    this.links[index] = updatedLink
                                                                                                                                } else {
                                                                                                                                    this.links.push(updatedLink)
                                                                                                                                }
                                                                                                                                return updatedLink
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                                                                                                                                    Logger.error('Error updating link', { error, id, data })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async delete(id: number) {
                                                                                                                            try {
                                                                                                                                await InquiryLinksAPI.deleteLink(id)
                                                                                                                                // Remove from store
                                                                                                                                this.links = this.links.filter((link) => link.id !== id)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error deleting link', { error, id })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deleteByInquiry(inquiryId: number) {
                                                                                                                            try {
                                                                                                                                await InquiryLinksAPI.deleteLinksByInquiry(inquiryId)
                                                                                                                                // Remove from store
                                                                                                                                this.links = this.links.filter((link) => link.inquiry_id !== inquiryId)
                                                                                                                            } catch (error) {
                                                                                                                                if ((error as AxiosError)?.code === 'ERR_CANCELED') return
                                                                                                                                    Logger.error('Error deleting inquiry links', { error, inquiryId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        /// ////////////////////////////////////////////////////////////////////
                                                                                                                        // Forms API functions
                                                                                                                        async createFormViaAPI(formData: CreateAppResourceData): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.post(generateOcsUrl('/apps/forms/api/v3/forms'), {
                                                                                                                                    title: formData.title,
                                                                                                                                    description: formData.description || '',
                                                                                                                                }, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating form via API', { error, formData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deleteFormViaAPI(formId: number): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.delete(generateOcsUrl(`/apps/forms/api/v3/forms/${formId}`), {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error deleting form via API', { error, formId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async updateFormViaAPI(formId: number, formData: any): Promise<any> {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl(`/apps/forms/api/v3/forms/${formId}`)
                                                                                                                                const keyValuePairs = []

                                                                                                                                if (formData.title) {
                                                                                                                                    keyValuePairs.push({
                                                                                                                                        key: 'title',
                                                                                                                                        value: formData.title
                                                                                                                                    })
                                                                                                                                }

                                                                                                                                if (formData.description !== undefined) {
                                                                                                                                    keyValuePairs.push({
                                                                                                                                        key: 'description',
                                                                                                                                        value: formData.description
                                                                                                                                    })
                                                                                                                                }

                                                                                                                                if (formData.expires) {
                                                                                                                                    keyValuePairs.push({
                                                                                                                                        key: 'expires',
                                                                                                                                        value: formData.expires
                                                                                                                                    })
                                                                                                                                }

                                                                                                                                if (formData.showExpiration !== undefined) {
                                                                                                                                    keyValuePairs.push({
                                                                                                                                        key: 'showExpiration', 
                                                                                                                                        value: formData.showExpiration
                                                                                                                                    })
                                                                                                                                }

                                                                                                                                const requestData = {
                                                                                                                                    keyValuePairs
                                                                                                                                }


                                                                                                                                const response = await axios.patch(url, requestData, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true',
                                                                                                                                        'Content-Type': 'application/json'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                console.error(error)
                                                                                                                                Logger.error('Error updating form via API', { error, formId, formData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },
                                                                                                                        // Forms operations
                                                                                                                        async createForm(inquiryId: number, formData: CreateAppResourceData): Promise<InquiryLinkType> {
                                                                                                                            const inquiryStore=useInquiryStore()
                                                                                                                            try {

                                                                                                                                // 1. Create the form via API
                                                                                                                                const formResponse = await this.createFormViaAPI(formData)

                                                                                                                                // Extract form ID from the correct location in OCS response
                                                                                                                                const formId = formResponse.ocs?.data?.id


                                                                                                                                if (!formId) {
                                                                                                                                    console.error("❌ No form ID found in response. Full response:", formResponse)
                                                                                                                                    throw new Error('No form ID returned from API')
                                                                                                                                }

                                                                                                                                // 2. Update form with expiration date if inquiry has one
                                                                                                                                if (inquiryStore.configuration.expire) {
                                                                                                                                    try {
                                                                                                                                        await this.updateFormViaAPI(formId, {
                                                                                                                                            title: inquiryStore.title,
                                                                                                                                            expires: inquiryStore.configuration.expire,
                                                                                                                                            showExpiration: true,
                                                                                                                                        })
                                                                                                                                    } catch (updateError) {
                                                                                                                                        console.warn("⚠️ Could not set form expiration, continuing...", updateError)
                                                                                                                                        // Continue even if expiration update fails
                                                                                                                                    }
                                                                                                                                }

                                                                                                                                // 3. Create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'forms',
                                                                                                                                    targetType: 'form',
                                                                                                                                    targetId: formId.toString(),
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                const result = await this.create(linkData)
                                                                                                                                return result

                                                                                                                            } catch (error) {
                                                                                                                                console.error("❌ Error in createForm:", error)
                                                                                                                                Logger.error('Error creating form and link', { error, inquiryId, formData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Link Form
                                                                                                                        async linkForm(inquiryId: number, formId: string): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 2. Just create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'forms',
                                                                                                                                    targetType: 'form',
                                                                                                                                    targetId: formId,
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error linking form', { error, inquiryId, formId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        /// //////////////////////////////////////////////////////////////////////////
                                                                                                                        // COSPEND API functions
                                                                                                                        async createCospendProjectViaAPI(projectData: CreateAppResourceData): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.post(generateOcsUrl('/apps/cospend/api/v1/projects'), {
                                                                                                                                    name: projectData.title,
                                                                                                                                    id: projectData.title.replace(/\s+/g, '-')
                                                                                                                                }, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating cospend project via API', { error, projectData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deleteCospendProjectViaAPI(projectId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.delete(generateOcsUrl(`/apps/cospend/api/v1/projects/${projectId}`), {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error deleting cospend project via API', { error, projectId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async updateCospendProjectViaAPI(projectId: string, projectData: any): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.put(generateOcsUrl(`/apps/cospend/api/v1/projects/${projectId}`), projectData, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error updating cospend project via API', { error, projectId, projectData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Cospend operations
                                                                                                                        async createCospendProject(inquiryId: number, projectData: CreateAppResourceData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 1. Create the project via API
                                                                                                                                const projectResponse = await this.createCospendProjectViaAPI(projectData)
                                                                                                                                const projectId = projectResponse.ocs?.data?.id || projectResponse.ocs?.data?.name

                                                                                                                                if (!projectId) {
                                                                                                                                    throw new Error('No project ID returned from API')
                                                                                                                                }

                                                                                                                                // 2. Create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'cospend',
                                                                                                                                    targetType: 'project',
                                                                                                                                    targetId: projectId.toString(),
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating cospend project and link', { error, inquiryId, projectData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async linkCospendProject(inquiryId: number, projectId: string): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 2. Just create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'cospend',
                                                                                                                                    targetType: 'project',
                                                                                                                                    targetId: projectId,
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error linking cospend project', { error, inquiryId, projectId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },


                                                                                                                        /// //////////////////////////////////////////////////////////////////////////
                                                                                                                        // COLLECTIVES API functions
                                                                                                                        async createCollectiveViaAPI(collectiveData: CreateAppResourceData): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.post(generateOcsUrl('/apps/collectives/api/v1.0/collectives'), {
                                                                                                                                    name: collectiveData.title,
                                                                                                                                }, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating collective via API', { error, collectiveData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deleteCollectiveViaAPI(collectiveId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.delete(generateOcsUrl(`/apps/collectives/api/v1.0/collectives/${collectiveId}`), {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error deleting collective via API', { error, collectiveId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async updateCollectiveViaAPI(collectiveId: string, collectiveData: any): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.put(generateOcsUrl(`/apps/collectives/api/v1.0/collectives/${collectiveId}`), collectiveData, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error updating collective via API', { error, collectiveId, collectiveData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Collectives operations
                                                                                                                        async createCollective(inquiryId: number, collectiveData: CreateAppResourceData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                const collectiveResponse = await this.createCollectiveViaAPI(collectiveData)
                                                                                                                                const collectiveId = collectiveResponse.ocs?.data?.collective.id || collectiveResponse.ocs?.data?.name

                                                                                                                               if (!collectiveId) {
                                                                                                                                    throw new Error('No collective ID returned from API')
                                                                                                                                }

                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'collectives',
                                                                                                                                    targetType: 'collective',
                                                                                                                                    targetId: collectiveId.toString(),
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating collective and link', { error, inquiryId, collectiveData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async linkCollective(inquiryId: number, collectiveId: string): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 2. Just create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'collectives',
                                                                                                                                    targetType: 'collective',
                                                                                                                                    targetId: collectiveId,
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error linking collective', { error, inquiryId, collectiveId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },


                                                                                                                        /// //////////////////////////////////////////////////////////////////////////
                                                                                                                        // POLLS API functions
                                                                                                                        async createPollViaAPI(pollData: CreateAppResourceData): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.post(generateOcsUrl('/apps/polls/api/v1.0/poll'), {
                                                                                                                                    title: pollData.title,
                                                                                                                                    description: pollData.description || '',
                                                                                                                                    type: 'textPoll',
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating poll via API', { error, pollData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deletePollViaAPI(pollId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.delete(generateOcsUrl(`/apps/polls/api/v1.0/poll/${pollId}`))
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error deleting poll via API', { error, pollId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async updatePollViaAPI(pollId: string, pollData: any): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.put(generateOcsUrl(`/apps/polls/api/v1.0/poll/${pollId}`), pollData)
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error updating poll via API', { error, pollId, pollData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Polls operations
                                                                                                                        async createPoll(inquiryId: number, pollData: CreateAppResourceData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 1. Create the poll via API
                                                                                                                                const pollResponse = await this.createPollViaAPI(pollData)
                                                                                                                                const pollId = pollResponse.ocs.data.poll.id || pollResponse.ocs.data?.id

                                                                                                                                if (!pollId) {
                                                                                                                                    throw new Error('No poll ID returned from API')
                                                                                                                                }

                                                                                                                                // 2. Create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'polls',
                                                                                                                                    targetType: 'poll',
                                                                                                                                    targetId: pollId.toString(),
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating poll and link', { error, inquiryId, pollData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async linkPoll(inquiryId: number, pollId: string): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 2. Just create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'polls',
                                                                                                                                    targetType: 'poll',
                                                                                                                                    targetId: pollId,
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error linking poll', { error, inquiryId, pollId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },


                                                                                                                        /// //////////////////////////////////////////////////////////////////////////
                                                                                                                        // DECK API functions
                                                                                                                        async createDeckBoardViaAPI(boardData: CreateAppResourceData): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.post(generateUrl('/apps/deck/api/v1.0/boards'), {
                                                                                                                                    title: boardData.title,
                                                                                                                                    color: '0082c9'
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating deck board via API', { error, boardData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async deleteDeckViaAPI(boardId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.delete(generateUrl(`/apps/deck/api/v1.0/boards/${boardId}`))
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error deleting deck board via API', { error, boardId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async updateDeckCardViaAPI(cardId: string, cardData: any): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.put(generateUrl(`/apps/deck/api/v1.0/cards/${cardId}`), cardData)
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error updating deck card via API', { error, cardId, cardData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Deck operations
                                                                                                                        async createDeckBoard(inquiryId: number, boardData: CreateAppResourceData): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 1. Create the board via API
                                                                                                                                const boardResponse = await this.createDeckBoardViaAPI(boardData)
                                                                                                                                const boardId = boardResponse.id || boardResponse.data?.id

                                                                                                                                if (!boardId) {
                                                                                                                                    throw new Error('No board ID returned from API')
                                                                                                                                }

                                                                                                                                // 2. Create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'deck',
                                                                                                                                    targetType: 'board',
                                                                                                                                    targetId: boardId.toString(),
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error creating deck board and link', { error, inquiryId, boardData })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async linkDeckBoard(inquiryId: number, boardId: string): Promise<InquiryLinkType> {
                                                                                                                            try {
                                                                                                                                // 2. Just create link in InquiryLink table
                                                                                                                                const linkData: CreateLinkData = {
                                                                                                                                    inquiryId,
                                                                                                                                    targetApp: 'deck',
                                                                                                                                    targetType: 'board',
                                                                                                                                    targetId: boardId,
                                                                                                                                    sortOrder: 0
                                                                                                                                }

                                                                                                                                return await this.create(linkData)
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error linking deck board', { error, inquiryId, boardId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // GET FOR ALL RESOURCES
                                                                                                                        //
                                                                                                                        //
                                                                                                                        // Forms - Déjà existante
                                                                                                                        async getFormDetailsWithHash(formId: number): Promise<any> {
                                                                                                                            try {
                                                                                                                                const response = await axios.get(generateOcsUrl(`/apps/forms/api/v3/forms/${formId}`), {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error getting form details with hash', { error, formId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Polls
                                                                                                                        async getPollDetails(pollId: number): Promise<any> {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl(`/apps/polls/api/v1.0/poll/${pollId}`)

                                                                                                                                const response = await axios.get(url)
                                                                                                                                return response.data.ocs?.data?.poll
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error getting poll details', { error, pollId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Deck
                                                                                                                        async getDeckBoardDetails(boardId: number): Promise<any> {
                                                                                                                            try {
                                                                                                                                const url = generateUrl(`/apps/deck/api/v1.0/boards/${boardId}`)

                                                                                                                                const response = await axios.get(url)
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error getting deck board details', { error, boardId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Cospend
                                                                                                                        async getCospendProjectDetails(projectId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl(`/apps/cospend/api/v1/projects/${projectId}`)

                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error getting cospend project details', { error, projectId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Collectives
                                                                                                                        // // Get all collectives and find the one with matching ID
                                                                                                                        async getCollectiveDetails(collectiveId: string): Promise<any> {
                                                                                                                            try {
                                                                                                                                // First, get all collectives
                                                                                                                                const url = generateOcsUrl('/apps/collectives/api/v1.0/collectives')

                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true'
                                                                                                                                    }
                                                                                                                                })


                                                                                                                                // Extract the collectives array from the nested response structure
                                                                                                                                const collectivesArray = response.data.ocs.data.collectives


                                                                                                                                const collective = collectivesArray.find((c: any) => 
                                                                                                                                                                         c.id === parseInt(collectiveId) || c.id.toString() === collectiveId
                                                                                                                                                                        )

                                                                                                                                                                        if (!collective) {
                                                                                                                                                                            throw new Error(`Collective with ID ${collectiveId} not found`)
                                                                                                                                                                        }

                                                                                                                                                                        return collective
                                                                                                                                                                        // Find the specific collective by ID

                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error getting collective details', { error, collectiveId })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },
                                                                                                                        /// ///////////////////////////////////////////////////////////
                                                                                                                        // Helper methods for getting external resources
                                                                                                                        async getOwnedForms() {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl('apps/forms/api/v3/forms')
                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIREQUEST': 'true',
                                                                                                                                    },
                                                                                                                                    params: {
                                                                                                                                        type: 'owned',
                                                                                                                                        format: 'json',
                                                                                                                                    },
                                                                                                                                })
                                                                                                                                return response.data.ocs.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading owned forms', { error })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async getSharedForms() { 
                                                                                                                            try { 
                                                                                                                                const url = generateOcsUrl('apps/forms/api/v3/forms')
                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: { 
                                                                                                                                        'OCS-APIREQUEST': 'true',
                                                                                                                                    },
                                                                                                                                    params: {
                                                                                                                                        type: 'shared',
                                                                                                                                        format: 'json',
                                                                                                                                    },
                                                                                                                                })
                                                                                                                                return response.data.ocs.data
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading shared forms', { error })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Polls methods
                                                                                                                        async getOwnedPolls(): Promise<any[]> {
                                                                                                                            try {
                                                                                                                                const url = generateUrl('/apps/polls/polls')

                                                                                                                                const response = await axios.get(url)

                                                                                                                                return response.data.polls || []
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading polls', { error })
                                                                                                                                return []
                                                                                                                            }
                                                                                                                        },

                                                                                                                        async getOwnedDeckBoards(): Promise<any[]> {
                                                                                                                            try {
                                                                                                                                const url = generateUrl('/apps/deck/api/v1.0/boards')

                                                                                                                                const response = await axios.get(url)

                                                                                                                                return response.data || []
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading deck boards', { error })
                                                                                                                                return []
                                                                                                                            }
                                                                                                                        },


                                                                                                                        // Cospend methods
                                                                                                                        async getOwnedCospendProjects(): Promise<any[]> {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl('/apps/cospend/api/v1/projects')
                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true',
                                                                                                                                    }
                                                                                                                                })
                                                                                                                                return response.data.ocs.data || []
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading cospend projects', { error })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Collectives methods
                                                                                                                        async getOwnedCollectives(): Promise<any[]> {
                                                                                                                            try {
                                                                                                                                const url = generateOcsUrl('/apps/collectives/api/v1.0/collectives')

                                                                                                                                const response = await axios.get(url, {
                                                                                                                                    headers: {
                                                                                                                                        'OCS-APIRequest': 'true',
                                                                                                                                    }
                                                                                                                                })


                                                                                                                                const collectives = response.data.ocs.data?.collectives || []

                                                                                                                                if (collectives.length > 0) {
                                                                                                                                    console.log("📝 First collective structure:", collectives[0])
                                                                                                                                }

                                                                                                                                return collectives
                                                                                                                            } catch (error) {
                                                                                                                                Logger.error('Error loading collectives', { error })
                                                                                                                                throw error
                                                                                                                            }
                                                                                                                        },

                                                                                                                        // Helper method to clear all links from store
                                                                                                                        clear() {
                                                                                                                            this.links = []
                                                                                                                        },

                                                                                                                        // Helper method to remove links for a specific inquiry from store
                                                                                                                        clearByInquiry(inquiryId: number) {
                                                                                                                            this.links = this.links.filter((link) => link.inquiry_id !== inquiryId)
                                                                                                                        },
                                                                                                                    },
})
