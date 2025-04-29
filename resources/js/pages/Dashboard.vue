<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import BeefreeSDK from '@beefree.io/sdk';
import axios, { type AxiosError } from 'axios';

interface BeefreeCredentials {
    clientId: string;
    clientSecret: string;
    uid: string;
}

interface BeefreeTemplate {
    [key: string]: any;
}

interface BeefreeDataResponse {
    credentials: BeefreeCredentials;
    template: BeefreeTemplate;
    message: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'EDM', href: '/dashboard' }
];

const isBuilderVisible = ref(false);
const isLoading = ref(false);
const beeEditor = ref<BeefreeSDK | null>(null);
const lastSaveTime = ref<string | null>(null);
const lastSavedJson = ref<any>(null);
const templates = ref<any[]>([]);
const loadingTemplates = ref(false);
const currentTemplateId = ref<number | null>(null);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const currentEditingTemplate = ref<any>(null);
const newTemplateName = ref('');

const { props } = usePage();
const templateData = props.templateData;

const fetchTemplates = async () => {
    try {
        loadingTemplates.value = true;
        const response = await axios.get('/email-templates');
        templates.value = response.data;
    } catch (error) {
        console.error('Failed to load templates:', error);
    } finally {
        loadingTemplates.value = false;
    }
};

onMounted(() => {
    if (templateData) {
        console.log('Template data found in session storage', templateData);
        loadTemplate(templateData.id);
    } else {
        console.warn('No template data found in session storage');
    }
    fetchTemplates();
});

const loadTemplate = async (templateId?: number) => {
    try {
        if (beeEditor.value) {
            beeEditor.value.destroy();
            beeEditor.value = null;
        }

        isLoading.value = true;
        isBuilderVisible.value = false;

        const url = templateId
            ? `/beefree/data?template_id=${templateId}`
            : '/beefree/data';

        const { data } = await axios.get<BeefreeDataResponse>(url);

        beeEditor.value = new BeefreeSDK();
        const { clientId, clientSecret, uid } = data.credentials;

        await beeEditor.value.getToken(clientId, clientSecret);

        isBuilderVisible.value = true;

        beeEditor.value.start({
            uid,
            container: 'bee-plugin-container',
            language: 'en-US',
            autosave: 30,
            onSave: handleSave,
            onAutoSave: handleAutoSave,
            onLoad: (json) => {
                handleLoad(json);
                currentTemplateId.value = templateId;
            },
            onError: (error) => {
                handleError(error);
            },
            workspace: {
                editSingleRow: false
            },
            debug: {
                inspectJson: true
            }
        }, data.template);
    } catch (error) {
        let errorMessage = 'Error loading template. Please try again.';
        if (axios.isAxiosError(error)) {
            errorMessage = error.response?.data?.error || errorMessage;
        }

        alert(errorMessage);
    } finally {
        isLoading.value = false;
    }
};

const handleAutoSave = async (jsonFile: any) => {
    const currentJsonStr = JSON.stringify(jsonFile);

    if (lastSavedJson.value && currentJsonStr === JSON.stringify(lastSavedJson.value)) {
        return;
    }

    const now = new Date().toLocaleTimeString();
    lastSaveTime.value = now;
    lastSavedJson.value = JSON.parse(JSON.stringify(jsonFile));

    try {
        const response = await axios.post('/beefree/data/save', {
            json: jsonFile,
            html: null,
            is_autosave: true
        });
    } catch (error) {
        console.error('Autosave failed:', error);
    }
};

const handleSave = async (jsonFile: any, htmlFile: string) => {
    const now = new Date().toLocaleTimeString();
    lastSavedJson.value = JSON.parse(JSON.stringify(jsonFile));

    try {
        const response = await axios.post('/beefree/data/save', {
            json: jsonFile,
            html: htmlFile,
            is_autosave: false
        });
        alert(`Template saved successfully at ${now}`);
    } catch (error) {
        console.error('Save failed:', error);
        alert('Failed to save template');
    }
};

const handleLoad = (jsonFile: any) => {};

const handleError = (errorMessage: string) => {
    alert(`Editor error: ${errorMessage}`);
};

async function showBuilder() {
    await loadTemplate();
}

const exitEditor = () => {
    if (beeEditor.value) {
        const container = document.getElementById('bee-plugin-container');
        if (container) {
            container.innerHTML = '';
        }
        beeEditor.value = null;
    }
    isBuilderVisible.value = false;
    fetchTemplates();
};

const checkTemplateStatus = () => {
    if (currentTemplateId.value === null) {

        alert('Please wait for the template to load');
        return false;
    } else if (!currentTemplateId.value) {

        alert('Save it first please');
        return false;
    }
    return true;
};

const handleFormSubmit = async (template_id?: number) => {
    try {
        const idToUse = template_id ?? currentTemplateId.value;

        if (!idToUse) {
            alert('Please save your template first or select a valid template');
            return;
        }

        const response = await axios.post('/beefree/next', {
            template_id: idToUse,
        });

        if (response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
        }

    } catch (error) {
        console.error('Error proceeding to next step:', error);
        alert('Error: Template not saved yet. Please save your template before proceeding.');
        throw new Error('Template not saved yet. Please save your template before proceeding.');
    }
};

const editTemplateName = (template: any) => {
    currentEditingTemplate.value = template;
    newTemplateName.value = template.name;
    showEditModal.value = true;
};

const updateTemplateName = async () => {
    try {
        if (!currentEditingTemplate.value) return;

        await axios.put(`/email-templates/${currentEditingTemplate.value.id}`, {
            name: newTemplateName.value
        });

        fetchTemplates();
        showEditModal.value = false;
    } catch (error) {
        console.error('Failed to update template name:', error);
        alert('Failed to update template name');
    }
};

const confirmDeleteTemplate = (template: any) => {
    currentEditingTemplate.value = template;
    showDeleteModal.value = true;
};

const deleteTemplate = async () => {
    try {
        if (!currentEditingTemplate.value) return;

        await axios.delete(`/email-templates/${currentEditingTemplate.value.id}`);

        fetchTemplates();
        showDeleteModal.value = false;
    } catch (error) {
        console.error('Failed to delete template:', error);
        alert('Failed to delete template');
    }
};
</script>
<template>
    <Head title="Continue" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 p-4 flex-col gap-4 rounded-xl">
            <div v-if="!isBuilderVisible">
                <div class="bg-black p-4 rounded-lg shadow">
                    <button
                        @click="showBuilder"
                        :disabled="isLoading"
                        class="w-full px-4 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
                    >
                        <span v-if="isLoading" class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading Editor...
                        </span>
                        <span v-else>Create New Campaign</span>
                    </button>
                </div>

                <div class="bg-black p-4 rounded-lg shadow mt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium text-white">Your Email Campaigns</h2>
                        <span class="text-sm text-gray-400">
                            {{ templates.length }} {{ templates.length === 1 ? 'campaign' : 'campaigns' }}
                        </span>
                    </div>

                    <div v-if="loadingTemplates" class="flex justify-center py-4">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-else>
                        <ul v-if="templates.length > 0" class="divide-y divide-gray-700">
                            <li
                                v-for="template in templates"
                                :key="template.id"
                                class="py-3 px-2 hover:bg-gray-800 transition-colors"
                            >
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-gray-200">{{ template.name }}</p>
                                        <p class="text-xs text-gray-400">
                                            Last updated: {{ new Date(template.updated_at).toLocaleString() }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            @click="editTemplateName(template)"
                                            class="px-3 py-1 text-sm bg-gray-700 text-gray-200 hover:bg-gray-600 rounded transition-colors"
                                        >
                                            Edit Name
                                        </button>
                                        <button
                                            @click="loadTemplate(template.id)"
                                            class="px-3 py-1 text-sm bg-blue-900 text-blue-200 hover:bg-blue-800 rounded transition-colors"
                                            :disabled="isLoading"
                                        >
                                            View
                                        </button>
                                        <button
                                            @click="handleFormSubmit(template.id)"
                                            class="px-3 py-1 text-sm bg-green-900 text-blue-200 hover:bg-green-700 rounded transition-colors"
                                            :disabled="isLoading"
                                        >
                                            Send
                                        </button>
                                        <button
                                            @click="confirmDeleteTemplate(template)"
                                            class="px-3 py-1 text-sm bg-red-900 text-red-200 hover:bg-red-800 rounded transition-colors"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="text-center py-6 text-gray-400">
                            No campaigns found. Create your first one!
                        </div>
                    </div>
                </div>
            </div>


            <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 z-50">
                <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium mb-4 text-white">Edit Template Name</h3>
                    <input
                        v-model="newTemplateName"
                        type="text"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-white"
                        placeholder="Template name"
                    />
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            @click="showEditModal = false"
                            class="px-4 py-2 bg-gray-700 text-gray-200 rounded hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="updateTemplateName"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 z-50">
                <div class="bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium mb-4 text-white">Delete Template</h3>
                    <p class="text-gray-300 mb-6">Are you sure you want to delete "{{ currentEditingTemplate?.name }}"? This action cannot be undone.</p>
                    <div class="flex justify-end gap-2">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-700 text-gray-200 rounded hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteTemplate"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="isBuilderVisible" class="bg-black p-4 rounded-lg shadow flex justify-between">
                <button
                    @click="exitEditor"
                    class="px-4 py-2 bg-gray-700 text-gray-200 rounded hover:bg-gray-600 transition-colors"
                >
                    ← Back to Campaigns
                </button>
                <div class="flex gap-2">
                    <form @submit.prevent="handleFormSubmit" class="inline">
                        <input type="hidden">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                        >
                            Next →
                        </button>
                    </form>
                </div>
            </div>

            <div
                v-if="isBuilderVisible"
                id="bee-plugin-container"
                class="w-full h-[88vh] border border-gray-700"
            ></div>

            <div v-if="isBuilderVisible && lastSaveTime" class="text-sm text-gray-400 text-center">
                Last autosave: {{ lastSaveTime }}
            </div>
        </div>
    </AppLayout>
</template>
