<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 px-4 py-3">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-900">Aula AI Assistant</h1>
        <div class="flex items-center space-x-4">
          <Link href="/aula" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm">
            Aula Dashboard
          </Link>
          <!-- Model Selection -->
          <select
            v-model="selectedModel"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
          >
            <option value="">Select Model</option>
            <option
              v-for="model in availableModels"
              :key="model.model"
              :value="model.model"
            >
              {{ model.display_name }}
            </option>
          </select>
          
          <!-- Agent Selection -->
          <select
            v-model="selectedAgent"
            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
          >
            <option value="">Select Agent</option>
            <option
              v-for="agent in availableAgents"
              :key="agent.type"
              :value="agent.type"
            >
              {{ agent.name }}
            </option>
          </select>
        </div>
      </div>
    </header>

    <!-- Main Chat Area -->
    <div class="flex-1 flex max-w-7xl mx-auto w-full">
      <!-- Sidebar with Conversations -->
      <aside class="w-80 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
          <button
            @click="startNewConversation"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
          >
            New Conversation
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto">
          <div
            v-for="conversation in conversations"
            :key="conversation.id"
            @click="loadConversation(conversation.id)"
            class="p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors"
            :class="{ 'bg-blue-50 border-blue-200': currentConversationId === conversation.id }"
          >
            <h3 class="font-medium text-gray-900 truncate">{{ conversation.title }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ conversation.agent_type.replace('_', ' ') }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ formatDate(conversation.updated_at) }}</p>
          </div>
        </div>
      </aside>

      <!-- Chat Interface -->
      <main class="flex-1 flex flex-col">
        <!-- Messages Area -->
        <div
          ref="messagesContainer"
          class="flex-1 overflow-y-auto p-4 space-y-4"
        >
          <div
            v-for="message in messages"
            :key="message.id"
            class="flex"
            :class="message.is_user ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-3xl px-4 py-3 rounded-lg shadow-sm"
              :class="message.is_user 
                ? 'bg-blue-600 text-white' 
                : 'bg-white text-gray-900 border border-gray-200'"
            >
              <div class="prose prose-sm max-w-none" v-html="formatMessage(message.content)"></div>
              <div
                class="text-xs mt-2 opacity-75"
                :class="message.is_user ? 'text-blue-100' : 'text-gray-500'"
              >
                {{ message.timestamp }}
              </div>
            </div>
          </div>

          <!-- Typing Indicator -->
          <div v-if="isTyping" class="flex justify-start">
            <div class="bg-white text-gray-900 border border-gray-200 px-4 py-3 rounded-lg shadow-sm">
              <div class="flex items-center space-x-2">
                <div class="flex space-x-1">
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
                <span class="text-sm text-gray-500">AI is thinking...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input Area -->
        <div class="border-t border-gray-200 bg-white p-4">
          <form @submit.prevent="sendMessage" class="flex space-x-4">
            <input
              v-model="newMessage"
              type="text"
              placeholder="Type your message..."
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :disabled="isTyping || !selectedModel || !selectedAgent"
            />
            <button
              type="submit"
              :disabled="!newMessage.trim() || isTyping || !selectedModel || !selectedAgent"
              class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Send
            </button>
          </form>
          
          <div v-if="!selectedModel || !selectedAgent" class="mt-2 text-sm text-gray-500">
            Please select both a model and an agent to start chatting.
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// Reactive data
const messages = ref([])
const conversations = ref([])
const availableModels = ref([])
const availableAgents = ref([])
const newMessage = ref('')
const isTyping = ref(false)
const selectedModel = ref('')
const selectedAgent = ref('')
const currentConversationId = ref(null)
const messagesContainer = ref(null)

// Load initial data
onMounted(async () => {
  await Promise.all([
    loadModels(),
    loadAgents(),
    loadConversations()
  ])
  
  // Set default selections
  if (availableModels.value.length > 0) {
    selectedModel.value = availableModels.value[0].model
  }
  if (availableAgents.value.length > 0) {
    selectedAgent.value = availableAgents.value[1]?.type || availableAgents.value[0].type
  }
})

// Methods
async function loadModels() {
  try {
    const response = await axios.get('/api/models')
    if (response.data.success) {
      availableModels.value = response.data.models
    }
  } catch (error) {
    console.error('Failed to load models:', error)
  }
}

async function loadAgents() {
  try {
    const response = await axios.get('/api/agents')
    if (response.data.success) {
      availableAgents.value = response.data.agents
    }
  } catch (error) {
    console.error('Failed to load agents:', error)
  }
}

async function loadConversations() {
  try {
    const response = await axios.get('/api/conversations')
    if (response.data.success) {
      conversations.value = response.data.conversations
    }
  } catch (error) {
    console.error('Failed to load conversations:', error)
  }
}

async function loadConversation(conversationId) {
  try {
    const response = await axios.get(`/api/conversations/${conversationId}`)
    if (response.data.success) {
      const conversation = response.data.conversation
      messages.value = conversation.messages
      currentConversationId.value = conversation.id
      selectedModel.value = conversation.model
      selectedAgent.value = conversation.agent_type
      
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Failed to load conversation:', error)
  }
}

function startNewConversation() {
  messages.value = []
  currentConversationId.value = null
}

async function sendMessage() {
  if (!newMessage.value.trim() || isTyping.value) return

  const userMessage = {
    id: Date.now(),
    content: newMessage.value,
    is_user: true,
    timestamp: new Date().toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  }

  messages.value.push(userMessage)
  const query = newMessage.value
  newMessage.value = ''
  isTyping.value = true

  await nextTick()
  scrollToBottom()

  try {
    const response = await axios.post('/api/chat', {
      query,
      model: selectedModel.value,
      agent: selectedAgent.value,
      conversation_id: currentConversationId.value
    })

    if (response.data.success) {
      const aiMessage = {
        id: Date.now() + 1,
        content: response.data.response,
        is_user: false,
        timestamp: new Date().toLocaleTimeString('en-US', { 
          hour: '2-digit', 
          minute: '2-digit' 
        })
      }

      messages.value.push(aiMessage)
      currentConversationId.value = response.data.conversation_id

      // Reload conversations to update the sidebar
      await loadConversations()

      await nextTick()
      scrollToBottom()
    } else {
      throw new Error(response.data.error || 'Unknown error')
    }
  } catch (error) {
    console.error('Chat error:', error)
    
    const errorMessage = {
      id: Date.now() + 1,
      content: `Error: ${error.response?.data?.error || error.message || 'Failed to send message'}`,
      is_user: false,
      timestamp: new Date().toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    }
    
    messages.value.push(errorMessage)
    await nextTick()
    scrollToBottom()
  } finally {
    isTyping.value = false
  }
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function formatMessage(content) {
  // Simple markdown-like formatting
  return content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/\n/g, '<br>')
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 1) {
    return 'Today'
  } else if (diffDays === 2) {
    return 'Yesterday'
  } else if (diffDays <= 7) {
    return `${diffDays - 1} days ago`
  } else {
    return date.toLocaleDateString()
  }
}
</script>
