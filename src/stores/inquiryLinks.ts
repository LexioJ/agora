/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { InquiryLinksAPI } from '../Api/index.ts'
import type { AxiosError } from '@nextcloud/axios'
import { Logger } from '../helpers/index.ts'
import { generateUrl , generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

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
    created_at?: string
    updated_at?: string
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
    },

    actions: {
        async loadByInquiry(inquiryId: number) {
            try {
                // const response = await InquiryLinksAPI.getLinksByInquiry(inquiryId)
                // Remove existing links for this inquiry
                // this.links = this.links.filter((link) => link.inquiry_id !== inquiryId)
                // Add new ones
                // this.links.push(...response.data.links)
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

        async createPoll(data: CreateAppResourceData): Promise<{ app: string; id: number }> {
            try {
                const response = await InquiryLinksAPI.createPoll(data)
                return response.data
            } catch (error) {
                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                Logger.error('Error creating poll', { error, data })
                throw error
            }
        },

        async createForm(data: CreateAppResourceData): Promise<{ app: string; id: number }> {
            try {
                const response = await InquiryLinksAPI.createForm(data)
                return response.data
            } catch (error) {
                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                Logger.error('Error creating form', { error, data })
                throw error
            }
        },

        async createDeckCard(data: CreateAppResourceData): Promise<{ app: string; id: number }> {
            try {
                const response = await InquiryLinksAPI.createDeckCard(data)
                return response.data
            } catch (error) {
                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                Logger.error('Error creating deck card', { error, data })
                throw error
            }
        },

        async createCospendExpense(data: CreateAppResourceData): Promise<{ app: string; id: number }> {
            try {
                const response = await InquiryLinksAPI.createCospendExpense(data)
                return response.data
            } catch (error) {
                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                Logger.error('Error creating cospend expense', { error, data })
                throw error
            }
        },

        // Forms API actions
        async createFormResource(data: CreateAppResourceData): Promise<{ app: string; id: number }> {
            try {
                // Create form via Forms API
                const formResponse = await this.createFormViaAPI(data)
                return { app: 'forms', id: formResponse.id }
            } catch (error) {
                if ((error as AxiosError)?.code === 'ERR_CANCELED') return Promise.reject(error)
                Logger.error('Error creating form resource', { error, data })
                throw error
            }
        },

        async createFormViaAPI(formData: CreateAppResourceData): Promise<any> {
            try {
                const response = await axios.post(generateUrl('/apps/forms/api/v3/forms'), {
                    title: formData.title,
                    description: formData.description || '',
                })
                return response.data
            } catch (error) {
                Logger.error('Error creating form via API', { error, formData })
                throw error
            }
        },

        async getFormDetails(formId: number): Promise<any> {
            try {
                const response = await axios.get(generateUrl(`/apps/forms/api/v3/forms/${formId}`))
                return response.data
            } catch (error) {
                Logger.error('Error getting form details', { error, formId })
                throw error
            }
        },

        async updateFormViaAPI(formId: number, formData: any): Promise<any> {
            try {
                const response = await axios.put(generateUrl(`/apps/forms/api/v3/forms/${formId}`), formData)
                return response.data
            } catch (error) {
                Logger.error('Error updating form via API', { error, formId, formData })
                throw error
            }
        },
/*
        async getOwnedForms(): Promise<any[]> {
            try {
                const response = await axios.get(generateUrl('/apps/forms/api/v3/forms?type=owned'))
                return response.data
            } catch (error) {
                Logger.error('Error loading owned forms', { error })
                throw error
                }
                }, */

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

        /*
        async getSharedForms(): Promise<any[]> {
            try {
                const response = await axios.get(generateUrl('/apps/forms/api/v3/forms?type=shared'))
                return response.data
            } catch (error) {
                Logger.error('Error loading shared forms', { error })
                throw error
            }
        }, */
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
