<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import axios from 'axios';
import BeefreeSDK from '@beefree.io/sdk';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const loading = ref(true);
const token = ref<string | null>(null);

// Basic template structure - you can get more from Beefree's GitHub
const getBasicTemplate = () => ({
    "version": "1.0",
    "content": {
        "type": "container",
        "children": [
            {
                "type": "header",
                "children": [
                    {
                        "type": "text",
                        "data": {
                            "text": "Welcome to my email!"
                        }
                    }
                ]
            },
            {
                "type": "section",
                "children": [
                    {
                        "type": "column",
                        "children": [
                            {
                                "type": "image",
                                "data": {
                                    "src": "https://via.placeholder.com/600x300",
                                    "alt": "Placeholder image"
                                }
                            },
                            {
                                "type": "text",
                                "data": {
                                    "text": "This is a sample email template."
                                }
                            },
                            {
                                "type": "button",
                                "data": {
                                    "text": "Click me!",
                                    "url": "https://example.com"
                                }
                            }
                        ]
                    }
                ]
            }
        ]
    }
});

onMounted(async () => {
    try {
        const response = await axios.post('/beefree/token');
        token.value = response.data.token;
        console.log('Token received:', token.value);

        if (token.value) {
            const beeConfig = {
                uid: 'test1-clientside',
                container: 'bee-plugin-container',
                language: 'en-US',
                onSave: (jsonFile, htmlFile) => {
                    console.log('onSave', jsonFile, htmlFile);
                },
                onSaveAsTemplate: (jsonFile) => {
                    console.log('onSaveAsTemplate', jsonFile);
                },
                onSend: (htmlFile) => {
                    console.log('onSend', htmlFile);
                },
                onError: (errorMessage) => {
                    console.log('onError', errorMessage);
                }
            };

            const beeTest = new BeefreeSDK(token.value);
            const template = getBasicTemplate();
            console.log('Using template:', template);

            await beeTest.start(beeConfig, template);

            console.log('Beefree SDK initialized successfully', beeTest);
            loading.value = false;
        }
    } catch (error) {
        console.error('API failed:', {
            status: error.response?.status,
            data: error.response?.data,
            fullError: error
        });
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div v-if="loading">Loading editor...</div>
            <div v-else id="bee-plugin-container" class="w-full h-[800px] border border-gray-300"></div>
        </div>
    </AppLayout>
</template>
