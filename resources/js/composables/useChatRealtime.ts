import { ref, readonly } from 'vue'

interface ChatPreview {
  last_message?: {
    content: string
  }
  last_message_at?: string
}

const chatPreviews = ref<Map<number, ChatPreview>>(new Map())

export function useChatRealtime() {
  const updateChatPreview = (chatId: number, preview: ChatPreview) => {
    const prev = chatPreviews.value.get(chatId) || {}
    chatPreviews.value.set(chatId, {
      last_message: preview.last_message ?? prev.last_message,
      last_message_at: preview.last_message_at ?? prev.last_message_at,
    })
    chatPreviews.value = new Map(chatPreviews.value)
  }

  const getChatPreview = (chatId: number): ChatPreview | undefined => {
    return chatPreviews.value.get(chatId)
  }

  const clearChatPreview = (chatId: number) => {
    if (chatPreviews.value.has(chatId)) {
      chatPreviews.value.delete(chatId)
      chatPreviews.value = new Map(chatPreviews.value)
    }
  }

  return {
    chatPreviews: readonly(chatPreviews),
    updateChatPreview,
    getChatPreview,
    clearChatPreview,
  }
}
