// src/menuItems.js
import {
  Bars3Icon, // Для "Настройки"
  CurrencyDollarIcon, // Для "Тарифы"
  DocumentTextIcon, // Для "Документация"
  HomeIcon, // Для "Главная"
  LightBulbIcon,
  MagnifyingGlassIcon,
  PencilIcon, // Для "Поиск"
  QuestionMarkCircleIcon, // Для "Что нового?"
  StarIcon,
  Cog6ToothIcon
} from '@heroicons/vue/24/solid';

export const mainItems = [
  { label: 'Главная', icon: HomeIcon, active: true },
  { label: 'Поиск по номеру', icon: MagnifyingGlassIcon },
  { label: 'Настройки', icon: Cog6ToothIcon },
];

export const utilityItems = [
  { label: 'Тарифы', icon: CurrencyDollarIcon },
  { label: 'Документация', icon: DocumentTextIcon },
  { label: 'Что нового?', icon: QuestionMarkCircleIcon },
  { label: 'Наши решения', icon: LightBulbIcon },
  { label: 'Отзывы', icon: StarIcon },
  { label: 'Оставить идею', icon: PencilIcon },
];
