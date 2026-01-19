<template>
  <div class="flex w-full h-screen overflow-hidden">
    <ProSidebar
      :mainItems="mainItems"
      :utilityItems="utilityItems"
      showNotifications
      @main-item-click="handleMainItemClick"
      @utility-item-click="handleUtilityItemClick"
    />
    <main class="flex-1 bg-gray-100 p-8 overflow-auto">
      <router-view />
    </main>
  </div>
</template>

<script>
import ProSidebar from "../components/ProSidebar.vue";
import { mainItems, utilityItems } from "../menuItems.js";
export default {
  name: "FullLayout",
  components: { ProSidebar },
  data() {
    return {
      mainItems,
      utilityItems,
    };
  },
  methods: {
    handleMainItemClick(item) {
      console.log("Основной пункт:", item.label);
      if (item.label === "Главная") {
        this.$router.push("/");
      } else if (item.label === "Поиск") {
        this.$router.push("/search");
      } else if (item.label === "СУД РФ") {
        this.$router.push("/sudrf");
      } else if (item.label === "Настройки") {
        this.$router.push("/settings");
      } else if (item.label === "Поиск по номеру") {
        this.$router.push("/case-search");
      }
    },
    handleUtilityItemClick(item) {
      console.log("Второстепенный пункт:", item.label);
      if (item.label === "Наши решения") {
        this.$router.push("/solutions");
      } else if (item.label === "Тарифы") {
        this.$router.push("/tariffs");
      } else if (item.label === "Отзывы") {
        this.$router.push("/reviews");
      } else if (item.label === "Документация") {
        this.$router.push("/documentation");
      } else if (item.label === "Что нового?") {
        this.$router.push("/whats-new");
      } else if (item.label === "Часто задаваемые вопросы") {
        this.$router.push("/faq");
      } else if (item.label === "Оставить идею") {
        this.$router.push("/submit-idea");
      }
    },
  },
};
</script>
