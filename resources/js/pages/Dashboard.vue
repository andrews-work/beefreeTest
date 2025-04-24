<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    { title: 'Dashboard', href: '/dashboard' }
];

const isBuilderVisible = ref(false);
const isLoading = ref(false);
const beeEditor = ref<BeefreeSDK | null>(null);
const lastSaveTime = ref<string | null>(null);
const lastSavedJson = ref<any>(null);

const handleAutoSave = async (jsonFile: any) => {
    const currentJsonStr = JSON.stringify(jsonFile);

    if (lastSavedJson.value && currentJsonStr === JSON.stringify(lastSavedJson.value)) {
        console.log('Autosave skipped - no changes detected');
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
        console.log('Autosave successful:', response.data);
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

const handleLoad = (jsonFile: any) => {
    console.log('Template loaded:', jsonFile);
};

const handleError = (errorMessage: string) => {
    console.error('Editor error:', errorMessage);
    alert(`Editor error: ${errorMessage}`);
};

async function showBuilder() {
    try {
        isLoading.value = true;
        await new Promise(resolve => setTimeout(resolve, 50));

        beeEditor.value = new BeefreeSDK();
        const { data } = await axios.get<BeefreeDataResponse>('/beefree/data');
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
            onLoad: handleLoad,
            onError: handleError,
            workspace: {
                editSingleRow: false
            },
            debug: {
                inspectJson: true
            }
        }, data.template);

    } catch (error) {
        console.error('Failed to load editor:', error);
        let errorMessage = 'Error loading email builder. Please try again.';
        if (axios.isAxiosError(error)) {
            errorMessage = error.response?.data?.error || errorMessage;
        }
        alert(errorMessage);
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl">
            <div
                v-if="isBuilderVisible"
                id="bee-plugin-container"
                class="w-full h-[88vh] border"
            ></div>
            <div v-else class="flex flex-col items-center justify-center h-full gap-4">
                <button
                    @click="showBuilder"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed transition-colors"
                >
                    <span v-if="isLoading" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading Editor...
                    </span>
                    <span v-else>Show Email Builder</span>
                </button>
            </div>

            <div v-if="isBuilderVisible && lastSaveTime" class="text-sm text-gray-00 text-center">
                Last autosave: {{ lastSaveTime }}
            </div>
        </div>
    </AppLayout>
</template>
