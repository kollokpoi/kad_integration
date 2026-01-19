import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router/index.js";
import "./style.css";
import PrimeVuePlugin from "./plugins/primevue.js";

const app = createApp(App);

app.use(router);

const pinia = createPinia();
app.use(pinia);

app.use(PrimeVuePlugin);



router.isReady().then(() => {
  router.push("/");
});

app.mount("#app");
