<script setup>
import { ref, defineProps, defineEmits } from 'vue';
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/24/solid';

// Props (входные данные)
const props = defineProps({
  initialCollapsed: {
    type: Boolean,
    default: false,
  },
  mainItems: {
    type: Array,
    default: () => [],
  },
  utilityItems: {
    type: Array,
    default: () => [],
  },
});

// Состояние сворачивания боковой панели
const isCollapsed = ref(props.initialCollapsed);

// Emits (события)
const emit = defineEmits(['update:collapsed', 'main-item-click', 'utility-item-click']);

// Методы
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
  emit('update:collapsed', isCollapsed.value);
};

const selectMainItem = (item) => {
  props.mainItems.forEach((i) => (i.active = false));
  props.utilityItems.forEach((i) => (i.active = false));
  item.active = true;
  emit('main-item-click', item);
};

const selectUtilityItem = (util) => {
  props.mainItems.forEach((i) => (i.active = false));
  props.utilityItems.forEach((i) => (i.active = false));
  util.active = true;
  emit('utility-item-click', util);
};
</script>

<template>
  <div
    :class="[
      'flex flex-col items-column'
    ]">

    <!-- ЦЕНТРАЛЬНЫЙ БЛОК (прокручиваемое меню) -->
    <nav class="flex-1 mt-3">
      <ul>
        <li
          v-for="(item, index) in mainItems"
          :key="index"
          class="text-base cursor-pointer item"
          @click="selectMainItem(item)">
          <p 
            :class="['item-icon-holder',
            item.active ? 'active':''
          ]">
            <component
              :is="item.icon"
              class="w-5 h-5 text-gray-500 item-icon" />
          </p>
          <div class="item-label-holder">
            <span
              class="item-label"
              >{{ item.label }}</span>
          </div>
        </li>
      </ul>
    </nav>

    <div class="flex-none border-t border-gray-200 p-2">
      <ul>
        <li
          v-for="(util, idx) in utilityItems"
          :key="idx"
          :class="[
            'text-base cursor-pointer item',
          ]"
          @click="selectUtilityItem(util)">
          <p class="item-icon-holder">
            <component
              :is="util.icon"
              :class="[
                'w-5 h-5 text-gray-500 item-icon',
                util.active ? 'active':''
              ]" />
          </p>
          <div class="item-label-holder">
            <span
              class="item-label"
              >{{ util.label }}</span>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 3px;
}
.item{
  position: relative;
  margin: 5px 0;
}
.item-label-holder {
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translate(0, -50%);
  box-shadow: 0px 0px 2px 3px rgba(0, 0, 0, 0.2);
  height: 36px;
  padding: 2px 10px;
  background-color: white;
  z-index: 100;
  border-radius: 5px;
  opacity: 0;
  visibility: hidden; 
  transition: .5s;
  
  display: flex;
  align-items: center;
  
  &::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 50%;
    transform: translateY(-50%) rotate(45deg);
    width: 10px;
    height: 10px;
    background: white;
    box-shadow: 2px -2px 2px 1px rgba(0, 0, 0, 0.2) inset;
    z-index: -1; 
  }
}
.item:hover .item-label-holder {
  left: calc(100% + 18px);
  opacity: 1;
  visibility: visible;
}

.item-icon{
  color: white;
}
.item-icon-holder{
  height: 40px;
  width: 40px;
  background-color: var(--color-blue-600);
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  transition: .45s;
}
.item-icon-holder.active{
  background-color: #2C2933;
}

.item:hover .item-icon-holder{
  background-color: #2C2933;
}
.items-column{
  justify-content: space-between;
  align-items: center;
  width: 72px;
}
.item-label{
  white-space: nowrap;
}
</style>
