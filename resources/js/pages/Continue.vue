<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { AxiosError } from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'EDM', href: '/dashboard' },
    { title: 'Send', href: '#' }
];

const props = defineProps({
    templateId: {
        type: [Number, String],
        required: true,
        validator: (value: number | string) => !isNaN(Number(value))
    },
    templateData: {
        type: Object,
        required: false
    }
});

const templateId = ref(typeof props.templateId === 'string'
    ? parseInt(props.templateId, 10)
    : props.templateId);

const template = ref(props.templateData || null);
const jsonContent = ref<string>('');
const isLoading = ref(false);
const showJsonPreview = ref(false);
const showHtmlPreview = ref(false);
const recipients = ref<string>('');
const isDropdownOpen = ref(false);
const scheduledDate = ref<string>('');
const scheduledTime = ref<string>('');
const isEditingSubject = ref(false);
const emailSubject = ref('');
const isSavingSubject = ref(false);

const availableRecipients = ref([
    { id: 1, email: 'kaniaverbal@chefalicious.com', name: 'temporary' },
    { id: 2, email: 'test3@test.com', name: 'Test User 3' },
]);

onMounted(() => {
    if (template.value) {
        jsonContent.value = JSON.stringify(template.value.content?.content_json || {}, null, 2);
        emailSubject.value = template.value.subject || template.value.name || '';
    }

    const now = new Date();
    const nextHour = new Date(now.setHours(now.getHours() + 1, 0, 0, 0));
    scheduledDate.value = nextHour.toISOString().split('T')[0];
    scheduledTime.value = `${String(nextHour.getHours()).padStart(2, '0')}:${String(nextHour.getMinutes()).padStart(2, '0')}`;
});

const updateEmailSubject = async () => {
    if (!emailSubject.value.trim()) {
        emailSubject.value = template.value?.name || '';
        isEditingSubject.value = false;
        return;
    }

    isSavingSubject.value = true;
    try {
        await axios.put(`/email-templates/${templateId.value}/subject`, {
            subject: emailSubject.value
        });

        if (template.value) {
            template.value.subject = emailSubject.value;
        }
        isEditingSubject.value = false;
    } catch (error: unknown) {
        console.error('Failed to update subject:', error);
        let errorMessage = 'Failed to update subject';
        if (axios.isAxiosError(error)) {
            errorMessage = error.response?.data?.message || error.message;
        } else if (error instanceof Error) {
            errorMessage = error.message;
        }
        alert(errorMessage);
        if (template.value) {
            emailSubject.value = template.value.subject || template.value.name || '';
        }
    }
    isSavingSubject.value = false;
};

const toggleDropdown = () => {
    isDropdownOpen.value = !isDropdownOpen.value;
};

const addRecipient = (email: string) => {
    const currentRecipients = recipients.value ? recipients.value.split(',').map(r => r.trim()) : [];
    if (!currentRecipients.includes(email)) {
        currentRecipients.push(email);
        recipients.value = currentRecipients.join(', ');
    }
    isDropdownOpen.value = false;
};

const removeRecipient = (email: string) => {
    const currentRecipients = recipients.value ? recipients.value.split(',').map(r => r.trim()) : [];
    recipients.value = currentRecipients.filter(r => r !== email).join(', ');
};

const scheduleSend = async () => {
    isLoading.value = true;
    try {
        const scheduledDateTime = new Date(`${scheduledDate.value}T${scheduledTime.value}`);

        const recipientList = recipients.value
            .split(',')
            .map(r => r.trim())
            .filter(r => r);

        if (recipientList.length === 0) {
            throw new Error('At least one recipient is required');
        }

        const payload = {
            template_id: templateId.value,
            recipients: recipientList,
            scheduled_at: scheduledDateTime.toISOString(),
            html_content: template.value?.content?.content_html || '',
            subject: emailSubject.value
        };

        console.log('Sending payload:', payload);

        const response = await axios.post('/beefree/sendEmail', payload);

        if (response.data.success) {
            alert(`Emails scheduled successfully for ${new Date(response.data.scheduled_at).toLocaleString()}`);
            recipients.value = '';
            const now = new Date();
            const nextHour = new Date(now.setHours(now.getHours() + 1, 0, 0, 0));
            scheduledDate.value = nextHour.toISOString().split('T')[0];
            scheduledTime.value = `${String(nextHour.getHours()).padStart(2, '0')}:${String(nextHour.getMinutes()).padStart(2, '0')}`;
        } else {
            throw new Error(response.data.message || 'Failed to schedule emails');
        }
    } catch (error: unknown) {
        console.error('Error during email scheduling:', error);
        let errorMessage = 'Failed to schedule emails';

        if (axios.isAxiosError(error)) {
            const axiosError = error as AxiosError<{
                message?: string;
                errors?: Record<string, string[]>;
            }>;

            if (axiosError.response?.data?.errors) {
                errorMessage = Object.values(axiosError.response.data.errors).flat().join('\n');
            } else if (axiosError.response?.data?.message) {
                errorMessage = axiosError.response.data.message;
            }

            if (axiosError.response?.status === 422) {
                errorMessage += '\nValidation errors:';
                if (axiosError.response.data.errors) {
                    for (const [field, errors] of Object.entries(axiosError.response.data.errors)) {
                        errorMessage += `\n- ${field}: ${errors.join(', ')}`;
                    }
                }
            }
        } else if (error instanceof Error) {
            errorMessage = error.message;
        }

        alert(errorMessage);
    }
    isLoading.value = false;
};

</script>

<template>
    <Head :title="template?.name || 'Continue Campaign'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="rounded-lg shadow p-6">
                <h1 class="text-2xl font-bold mb-4">
                    Continue with {{ template?.name || `Template ${templateId}` }}
                </h1>

                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-2">Template Details</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Template ID</p>
                            <p class="font-medium">{{ templateId }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Created</p>
                            <p class="font-medium">{{ template?.created_at }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Subject</label>
                            <div v-if="!isEditingSubject" class="group flex items-center">
                                <div class="min-h-[2.5rem] flex items-center px-3 py-2 rounded-md border border-gray-500 group-hover:border-gray-300 transition-colors w-full">
                                    <p class="font-medium text-gray-100 flex-1 truncate">{{ emailSubject }}</p>
                                    <button
                                        @click="isEditingSubject = true"
                                        class="ml-2 p-1 text-gray-100 hover:text-blue-600 rounded-full hover:bg-gray-100 transition-colors"
                                        title="Edit subject"
                                    >
                                        <h1>edit</h1>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <input
                                        v-model="emailSubject"
                                        type="text"
                                        ref="subjectInput"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        @keyup.enter="updateEmailSubject"
                                        placeholder="Enter email subject"
                                    />
                                    <div class="flex gap-2">
                                        <button
                                            @click="updateEmailSubject"
                                            :disabled="isSavingSubject"
                                            class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center gap-1"
                                        >
                                            <svg v-if="isSavingSubject" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>{{ isSavingSubject ? 'Saving...' : 'Save' }}</span>
                                        </button>
                                        <button
                                            @click="isEditingSubject = false; emailSubject = template?.subject || template?.name || ''"
                                            class="px-3 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                <p v-if="!emailSubject.trim()" class="text-sm text-red-500">Subject cannot be empty</p>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-6">
                    <button
                        @click="showJsonPreview = !showJsonPreview"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    >
                        {{ showJsonPreview ? 'Hide JSON' : 'Show JSON' }}
                    </button>

                    <button
                        @click="showHtmlPreview = !showHtmlPreview"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                    >
                        {{ showHtmlPreview ? 'Hide HTML' : 'Show HTML' }}
                    </button>
                </div>

                <div v-if="showJsonPreview" class="mb-6 transition-all duration-300">
                    <h2 class="text-lg font-semibold mb-2">JSON Content</h2>
                    <div class="relative overflow-hidden">
                        <div class="bg-black p-4 rounded-md border border-gray-200 overflow-auto max-h-96">
                            <pre class="text-sm whitespace-pre-wrap text-gray-300">{{ jsonContent }}</pre>
                        </div>
                    </div>
                </div>

                <div v-if="showHtmlPreview" class="mb-6 transition-all duration-300">
                    <h2 class="text-lg font-semibold mb-2">HTML Preview</h2>
                    <div class="relative overflow-hidden">
                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 overflow-auto max-h-96">
                            <div v-html="template?.content?.content_html"></div>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recipients</label>
                    <div class="relative">
                        <div
                            @click="toggleDropdown"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 cursor-pointer flex justify-between items-center"
                        >
                            <span class="text-gray-400">Select recipients...</span>
                            <svg
                                class="h-5 w-5 text-gray-400 transition-transform duration-200"
                                :class="{ 'transform rotate-180': isDropdownOpen }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>

                        <div
                            v-show="isDropdownOpen"
                            class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 border border-gray-200 max-h-60 overflow-auto"
                        >
                            <div
                                v-for="recipient in availableRecipients"
                                :key="recipient.id"
                                @click="addRecipient(recipient.email)"
                                class="px-4 py-2 hover:bg-blue-50 cursor-pointer flex items-center"
                            >
                                <span class="mr-2">{{ recipient.name }}</span>
                                <span class="text-gray-500 text-sm">{{ recipient.email }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 p-2 border border-gray-300 rounded-md min-h-16">
                        <div v-if="recipients" class="flex flex-wrap gap-2">
                            <div
                                v-for="email in recipients.split(',').filter(e => e.trim())"
                                :key="email"
                                class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full flex items-center"
                            >
                                {{ email.trim() }}
                                <button
                                    @click="removeRecipient(email.trim())"
                                    class="ml-2 text-blue-500 hover:text-blue-700"
                                >
                                    ×
                                </button>
                            </div>
                        </div>
                        <p v-else class="text-gray-400">No recipients selected</p>
                    </div>

                    <textarea
                        v-model="recipients"
                        class="hidden"
                        name="recipients"
                    ></textarea>
                </div>

                <div class="mb-6 p-4 border border-gray-200 rounded-md">
                    <h2 class="text-lg font-semibold mb-4">Schedule Options</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="scheduledDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input
                                id="scheduledDate"
                                v-model="scheduledDate"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label for="scheduledTime" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                            <input
                                id="scheduledTime"
                                v-model="scheduledTime"
                                type="time"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-6">
                    <button
                        @click="scheduleSend"
                        :disabled="isLoading"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors disabled:bg-green-300"
                    >
                        <span v-if="isLoading">Scheduling...</span>
                        <span v-else>Schedule Send</span>
                    </button>

                    <button
                        :disabled="isLoading"
                        class="px-4 py-2 bg-red-900 text-white rounded hover:bg-gray-600 transition-colors disabled:bg-gray-300"
                    >
                    <a href="/dashboard" class="btn-cancel">Cancel</a>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
