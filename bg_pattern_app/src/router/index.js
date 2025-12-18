// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import FullLayout from '../layouts/FullLayout.vue';
import CaseSearch from '../views/CaseSearch.vue';
import Home from '../views/Home.vue';
import OurSolutions from '../views/OurSolutions.vue';
import Settings from '../views/Settings.vue';
import SubmitIdea from '../views/SubmitIdea.vue';
import Tariffs from '../views/Tariffs.vue';
import WhatsNew from '../views/WhatsNew.vue';

const routes = [
  {
    path: '/',
    component: FullLayout,
    children: [
      { path: '/', name: 'home', component: Home },
      { path: '/case-search', name: 'case-search', component: CaseSearch },
      { path: '/settings', name: 'settings', component: Settings },
      { path: '/solutions', name: 'solutions', component: OurSolutions },
      { path: '/tariffs', name: 'tariffs', component: Tariffs },
      {
        path: '/reviews',
        name: 'reviews',
        beforeEnter(to, from, next) {
          const url = 'https://www.bitrix24.ru/partners/partner/13927904/';
          window.open(url, '_blank');
          next(false); // отменяем переход по роуту, чтобы не грузить пустую страницу
        },
      },

      {
        path: '/documentation',
        name: 'documentation',
        beforeEnter(to, from, next) {
          const url = 'https://bg59.online/Apps/docs/build/docs/cloud-solutions/kad';
          window.open(url, '_blank');
          next(false); // отменяем переход по роуту, чтобы не грузить пустую страницу
        },
      },
      { path: '/whats-new', name: 'whatsNew', component: WhatsNew },
      { path: '/submit-idea', name: 'submitIdea', component: SubmitIdea }, // Новый маршрут для "Оставить идею"
      {
        path: '/cases/:caseNumber',
        name: 'caseDetails',
        component: () => import('../views/CaseDetails.vue'),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
