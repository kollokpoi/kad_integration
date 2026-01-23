import { createRouter, createWebHistory } from "vue-router";
import FullLayout from "../layouts/FullLayout.vue";
import Home from "../views/Home.vue";
import Tariffs from "../views/Tariffs.vue";
import WhatsNew from "../views/WhatsNew.vue";
import SubmitIdea from "../views/SubmitIdea.vue";
import {useAuthStore} from "@payment-app/authSdk"

const CaseSearch = () => import("../views/CaseSearch.vue");
const Settings = () => import("../views/Settings.vue");
const OurSolutions = () => import("../views/OurSolutions.vue");
const CaseDetails = () => import("../views/CaseDetails.vue");
const NotFound = () => import("../views/NotFound.vue");

const routes = [
  {
    path: "/",
    component: FullLayout,
    children: [
      {
        path: "/",
        name: "home",
        component: Home,
        meta: {
          title: "Главная",
        },
      },
      {
        path: "/case-search",
        name: "case-search",
        component: CaseSearch,
        meta: {
          title: "Поиск дел",
        },
      },
      {
        path: "/settings",
        name: "settings",
        component: Settings,
        meta: {
          title: "Настройки",
        },
      },
      {
        path: "/solutions",
        name: "solutions",
        component: OurSolutions,
        meta: {
          title: "Наши решения",
        },
      },
      {
        path: "/tariffs",
        name: "tariffs",
        component: Tariffs,
        meta: {
          title: "Тарифы",
        },
      },
      {
        path: "/whats-new",
        name: "whatsNew",
        component: WhatsNew,
        meta: {
          title: "Что нового",
        },
      },
      {
        path: "/submit-idea",
        name: "submitIdea",
        component: SubmitIdea,
        meta: {
          title: "Предложить идею",
        },
      },
      {
        path: "/cases/:caseNumber",
        name: "caseDetails",
        component: CaseDetails,
        meta: {
          title: "Детали дела",
        },
      },
      {
        path: "/reviews",
        name: "reviews",
        beforeEnter(to, from, next) {
          const url = "https://www.bitrix24.ru/partners/partner/13927904/";
          window.open(url, "_blank");
          next(false);
        },
      },
      {
        path: "/documentation",
        name: "documentation",
        beforeEnter(to, from, next) {
          const url =
            "https://bg59.online/Apps/docs/build/docs/cloud-solutions/kad";
          window.open(url, "_blank");
          next(false);
        },
      },
      {
        path: "/:pathMatch(.*)*",
        name: "not-found",
        component: NotFound,
        meta: {
          title: "Страница не найдена",
        },
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  },
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  if (to.meta.requiresAuth) {
    if (!authStore.isAuthenticated) {
      next('/auth-error')
      return
    }

    if (!authStore.canAccessPage(to.name)) {
      next('/auth-error?reason=no_access')
      return
    }
  }

  next()
})

router.onError((error, to) => {
  console.error('Ошибка навигации:', error)

  if (error.message.includes('авторизации') || error.message.includes('инициализации')) {
    router.push({
      name: 'auth-error',
      query: {
        error: error.message,
        from: to.fullPath
      }
    })
    return false
  }

  router.push('/not-found')
  return false
})

export default router;
