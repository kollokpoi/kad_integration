import { createRouter, createWebHistory } from "vue-router";
import FullLayout from "../layouts/FullLayout.vue";
import { useAuthStore } from "../stores/auth.store";
import Home from "../views/Home.vue";
import Tariffs from "../views/Tariffs.vue";
import WhatsNew from "../views/WhatsNew.vue";
import SubmitIdea from "../views/SubmitIdea.vue";

const CaseSearch = () => import("../views/CaseSearch.vue");
const Settings = () => import("../views/Settings.vue");
const OurSolutions = () => import("../views/OurSolutions.vue");
const CaseDetails = () => import("../views/CaseDetails.vue");
const NotFound = () => import("../views/NotFound.vue");
const Registration = () => import("../views/Registration.vue");

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
          requiresAuth: true,
        },
      },
      {
        path: "/case-search",
        name: "case-search",
        component: CaseSearch,
        meta: {
          title: "Поиск дел",
          requiresAuth: true,
        },
      },
      {
        path: "/settings",
        name: "settings",
        component: Settings,
        meta: {
          title: "Настройки",
          requiresAuth: true,
        },
      },
      {
        path: "/solutions",
        name: "solutions",
        component: OurSolutions,
        meta: {
          title: "Наши решения",
          requiresAuth: true,
        },
      },
      {
        path: "/tariffs",
        name: "tariffs",
        component: Tariffs,
        meta: {
          title: "Тарифы",
          requiresAuth: false,
        },
      },
      {
        path: "/whats-new",
        name: "whatsNew",
        component: WhatsNew,
        meta: {
          title: "Что нового",
          requiresAuth: false,
        },
      },
      {
        path: "/submit-idea",
        name: "submitIdea",
        component: SubmitIdea,
        meta: {
          title: "Предложить идею",
          requiresAuth: false,
        },
      },
      {
        path: "/cases/:caseNumber",
        name: "caseDetails",
        component: CaseDetails,
        meta: {
          title: "Детали дела",
          requiresAuth: true,
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
  {
    path: "/register",
    name: "register",
    component: Registration,
    meta: {
      title: "Регистрация",
      requiresAuth: false,
    },
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

router.beforeEach(async (to, from, next) => {
  if (to.meta.title) {
    document.title = `${to.meta.title} | KAD`;
  }

  const authStore = useAuthStore();

  if (!authStore.isInitialized) {
    await authStore.initialize();
  }

  if (to.meta.requiresAuth) {
    if (authStore.isAuthenticated) {
      if(authStore.isSubscriptionActive)
        next();
      else
        next({name:"tariffs"})
      return;
    }

    const loginResult = await authStore.login();

    if (loginResult.success) {
      next();
    } else {
      next({ name: "register" });
    }
    return;
  }
  next();
});

export default router;
