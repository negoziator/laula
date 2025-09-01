<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900">Aula Dashboard</h1>
              <p class="text-sm text-gray-500">Danish School System Interface</p>
            </div>
          </div>
          
          <div class="flex items-center space-x-4">
            <button
              @click="refreshData"
              :disabled="loading"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center space-x-2"
            >
              <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span>Refresh</span>
            </button>
            
            <Link href="/chat" class="text-gray-600 hover:text-gray-900 transition-colors">
              Back to Chat
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Configuration Warning -->
    <div v-if="!aulaStatus.configured" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mx-4 mt-4 rounded-r-lg">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Aula Not Configured</h3>
          <p class="mt-1 text-sm text-yellow-700">{{ aulaStatus.message }}</p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div v-if="aulaStatus.configured" class="space-y-6">
        
        <!-- Children Selection -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Children
          </h2>
          
          <div v-if="children.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="child in children"
              :key="child.name"
              @click="selectChild(child)"
              class="p-4 border-2 rounded-lg cursor-pointer transition-all hover:shadow-md"
              :class="activeChild === child.name ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
            >
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                  {{ child.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="font-medium text-gray-900">{{ child.name }}</h3>
                  <p class="text-sm text-gray-500">{{ child.institution }}</p>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else-if="!loading" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p>No children found. Please check your Aula credentials.</p>
          </div>
        </div>

        <!-- Active Child Content -->
        <div v-if="activeChild" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Daily Overview -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Today's Overview - {{ activeChild }}
            </h3>
            
            <div v-if="dailyOverview && Object.keys(dailyOverview).length > 0" class="space-y-3">
              <div v-for="(data, childId) in dailyOverview" :key="childId">
                <div v-if="data" class="bg-gray-50 rounded-lg p-4">
                  <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ JSON.stringify(data, null, 2) }}</pre>
                </div>
                <div v-else class="text-gray-500 text-sm">No overview data available for today</div>
              </div>
            </div>
            
            <div v-else class="text-center py-8 text-gray-500">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <p>No daily overview available</p>
            </div>
          </div>

          <!-- Messages -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
              Messages
            </h3>
            
            <div v-if="messages.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
              <div v-for="(thread, index) in messages" :key="index" class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 mb-2">{{ thread.subject }}</h4>
                <div v-if="thread.messages && thread.messages.length > 0" class="space-y-2">
                  <div v-for="(message, msgIndex) in thread.messages" :key="msgIndex" class="bg-gray-50 rounded p-3">
                    <div class="flex justify-between items-start mb-2">
                      <span class="text-sm font-medium text-gray-700">{{ message.sender }}</span>
                      <span class="text-xs text-gray-500">{{ message.date }}</span>
                    </div>
                    <div class="text-sm text-gray-600" v-html="message.text"></div>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-8 text-gray-500">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
              </svg>
              <p>No messages available</p>
            </div>
          </div>
        </div>

        <!-- Calendar -->
        <div v-if="activeChild" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              Calendar Events - {{ activeChild }}
            </h3>
            
            <div class="flex space-x-2">
              <button
                @click="loadCalendar(7)"
                :class="calendarDays === 7 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm transition-colors"
              >
                7 days
              </button>
              <button
                @click="loadCalendar(14)"
                :class="calendarDays === 14 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm transition-colors"
              >
                14 days
              </button>
              <button
                @click="loadCalendar(30)"
                :class="calendarDays === 30 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-3 py-1 rounded text-sm transition-colors"
              >
                30 days
              </button>
            </div>
          </div>
          
          <div v-if="calendar && Object.keys(calendar).length > 0" class="space-y-4">
            <div v-for="(events, date) in calendar" :key="date" class="border border-gray-200 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 mb-3">{{ formatDate(date) }}</h4>
              <div class="space-y-2">
                <div v-for="(event, eventIndex) in events" :key="eventIndex" class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                  <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                  <div class="flex-1">
                    <div class="flex justify-between items-start">
                      <h5 class="font-medium text-gray-900">{{ event.title }}</h5>
                      <span class="text-sm text-gray-500">{{ event.formatted_time }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p>No calendar events found</p>
          </div>
        </div>
      </div>

      <!-- Not Configured State -->
      <div v-else class="text-center py-16">
        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Configure Aula Integration</h3>
        <p class="text-gray-500 mb-6">Add your Danish school system credentials to get started</p>
        <div class="bg-gray-50 rounded-lg p-6 max-w-2xl mx-auto text-left">
          <h4 class="font-medium text-gray-900 mb-3">Setup Instructions:</h4>
          <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
            <li>Add your Aula credentials to the <code class="bg-gray-200 px-1 rounded">.env</code> file</li>
            <li>Set <code class="bg-gray-200 px-1 rounded">AULA_USERNAME=your_username</code></li>
            <li>Set <code class="bg-gray-200 px-1 rounded">AULA_PASSWORD=your_password</code></li>
            <li>Refresh this page to connect to Aula</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// Reactive data
const loading = ref(false)
const aulaStatus = ref({ configured: false, message: '' })
const children = ref([])
const activeChild = ref(null)
const dailyOverview = ref({})
const messages = ref([])
const calendar = ref({})
const calendarDays = ref(14)

// Load initial data
onMounted(async () => {
  await loadAulaStatus()
  if (aulaStatus.value.configured) {
    await loadChildren()
  }
})

// Methods
async function loadAulaStatus() {
  try {
    const response = await axios.get('/api/aula/status')
    aulaStatus.value = response.data
  } catch (error) {
    console.error('Failed to load Aula status:', error)
  }
}

async function loadChildren() {
  if (!aulaStatus.value.configured) return
  
  loading.value = true
  try {
    const response = await axios.get('/api/aula/children')
    if (response.data.success) {
      children.value = response.data.children
    } else {
      console.error('Failed to load children:', response.data.error)
    }
  } catch (error) {
    console.error('Failed to load children:', error)
  } finally {
    loading.value = false
  }
}

async function selectChild(child) {
  if (activeChild.value === child.name) return
  
  loading.value = true
  try {
    // Set active child
    const response = await axios.post('/api/aula/active-child', {
      name: child.name
    })
    
    if (response.data.success) {
      activeChild.value = child.name
      
      // Load child-specific data
      await Promise.all([
        loadDailyOverview(),
        loadMessages(),
        loadCalendar(calendarDays.value)
      ])
    } else {
      console.error('Failed to set active child:', response.data.error)
    }
  } catch (error) {
    console.error('Failed to select child:', error)
  } finally {
    loading.value = false
  }
}

async function loadDailyOverview() {
  if (!activeChild.value) return
  
  try {
    const response = await axios.get('/api/aula/daily-overview')
    if (response.data.success) {
      dailyOverview.value = response.data.overview
    }
  } catch (error) {
    console.error('Failed to load daily overview:', error)
  }
}

async function loadMessages() {
  if (!activeChild.value) return
  
  try {
    const response = await axios.get('/api/aula/messages')
    if (response.data.success) {
      messages.value = response.data.messages
    }
  } catch (error) {
    console.error('Failed to load messages:', error)
  }
}

async function loadCalendar(days = 14) {
  if (!activeChild.value) return
  
  calendarDays.value = days
  try {
    const response = await axios.get(`/api/aula/calendar?days=${days}`)
    if (response.data.success) {
      calendar.value = response.data.calendar
    }
  } catch (error) {
    console.error('Failed to load calendar:', error)
  }
}

async function refreshData() {
  loading.value = true
  try {
    await loadAulaStatus()
    if (aulaStatus.value.configured) {
      await loadChildren()
      if (activeChild.value) {
        await Promise.all([
          loadDailyOverview(),
          loadMessages(),
          loadCalendar(calendarDays.value)
        ])
      }
    }
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return 'Tomorrow'
  } else {
    return date.toLocaleDateString('en-US', { 
      weekday: 'long', 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    })
  }
}
</script>
