<template>
  <div>
    <!-- Карточка с таймлайном комментариев -->
    <Card class="mb-4">
      <template #title> Комментарии пользователей Bitrix24 </template>
      <template #content>
        <div
          v-if="!actualCaseNumber"
          class="p-3 text-center text-gray-500">
          Для отображения комментариев необходимо сохранить дело
        </div>
        <Timeline
          v-else-if="comments.length > 0"
          :value="comments"
          class="w-full">
          <template #marker="{ item }">
            <i class="pi pi-user-circle text-blue-600"></i>
          </template>
          <template #content="{ item }">
            <div class="p-3 border border-gray-200 rounded-md shadow-sm mb-3 bg-white">
              <div class="flex justify-between mb-2">
                <span class="font-semibold">{{ item.author }}</span>
                <span class="text-sm text-gray-500">{{ formatDate(item.date) }}</span>
              </div>
              <div>{{ item.text }}</div>
            </div>
          </template>
        </Timeline>
        <div
          v-else
          class="p-3 text-center text-gray-500">
          Комментариев пока нет
        </div>
      </template>
    </Card>

    <!-- Карточка с формой добавления комментария -->
    <Card>
      <template #title> Оставить комментарий </template>
      <template #content>
        <div
          v-if="!actualCaseNumber"
          class="p-3 text-center text-red-500 mb-2">
          Для добавления комментариев необходимо сохранить дело
        </div>
        <Textarea
          v-model="newComment"
          autoResize
          rows="4"
          placeholder="Ваш комментарий..."
          class="w-full"
          :disabled="!actualCaseNumber" />
        <Button
          label="Отправить"
          icon="pi pi-send"
          class="p-button-primary mt-2"
          @click="addComment"
          :disabled="!newComment.trim() || !actualCaseNumber" />
      </template>
    </Card>
  </div>
</template>

<script setup>
import Button from 'primevue/button';
import Card from 'primevue/card';
import Textarea from 'primevue/textarea';
import Timeline from 'primevue/timeline';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import bitrixService from '../services/bitrixService.js';

// Принимаем номер дела как пропс
const props = defineProps({
  caseNumber: { type: String, default: '' },
});

const toast = useToast();

const currentUser = ref('Пользователь Bitrix24');
const comments = ref([]);
const newComment = ref('');

// Вычисляемое свойство для проверки наличия номера дела
const actualCaseNumber = computed(() => {
  return props.caseNumber && props.caseNumber !== 'undefined' ? props.caseNumber : '';
});

// Функция форматирования даты для вывода
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

// Функция загрузки комментариев
const loadComments = async () => {
  if (!actualCaseNumber.value) {
    comments.value = [];
    return;
  }

  try {
    const settings = await bitrixService.getSettings();
    console.log('getSettings response:', settings);

    if (settings && settings.commentsByCase && settings.commentsByCase[actualCaseNumber.value]) {
      comments.value = settings.commentsByCase[actualCaseNumber.value];
    } else {
      comments.value = [];
    }
  } catch (error) {
    console.error('Ошибка при загрузке комментариев:', error);
    comments.value = [];
  }
};

// Наблюдаем за изменениями номера дела
watch(
  () => props.caseNumber,
  async (newValue, oldValue) => {
    if (newValue !== oldValue) {
      await loadComments();
    }
  },
  { immediate: true },
);

onMounted(async () => {
  // Получаем данные о текущем пользователе через Bitrix
  try {
    const userResponse = await bitrixService.getCurrentUser();
    console.log('getCurrentUser response:', userResponse);
    if (userResponse) {
      // Формируем полное имя пользователя из имени и фамилии
      if (userResponse.NAME && userResponse.LAST_NAME) {
        currentUser.value = `${userResponse.NAME} ${userResponse.LAST_NAME}`;
      }
    }
  } catch (error) {
    console.error('Ошибка при получении данных о пользователе:', error);
  }

  // Загружаем комментарии
  await loadComments();
});

const addComment = async () => {
  if (!newComment.value.trim() || !actualCaseNumber.value) return;

  const comment = {
    author: currentUser.value,
    text: newComment.value.trim(),
    date: new Date().toISOString(),
  };

  // Добавляем комментарий в локальный массив
  comments.value.push(comment);

  try {
    // Получаем текущие настройки
    const currentSettings = await bitrixService.getSettings();
    const newSettings = { ...currentSettings };

    // Инициализируем объект для комментариев по делам, если его нет
    if (!newSettings.commentsByCase) {
      newSettings.commentsByCase = {};
    }

    // Сохраняем комментарии для конкретного дела
    newSettings.commentsByCase[actualCaseNumber.value] = comments.value;

    // Сохраняем обновленные настройки
    await bitrixService.saveSettings(newSettings);

    toast.add({
      severity: 'success',
      summary: 'Комментарий отправлен',
      detail: 'Ваш комментарий успешно сохранен',
      life: 3000,
    });

    // Очищаем поле ввода после успешного сохранения
    newComment.value = '';
  } catch (error) {
    console.error('Ошибка при сохранении комментария:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось сохранить комментарий',
      life: 3000,
    });
  }
};
</script>

<style scoped>
/* Для дополнительных стилей */
</style>
