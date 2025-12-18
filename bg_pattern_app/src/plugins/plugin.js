import PrimeVue from 'primevue/config';

export default {
  install(app) {
    app.use(PrimeVue, {
      theme: {
        preset: MyPreset,
        options: {
          prefix: 'p',
          darkModeSelector: 'false',
          cssLayer: false,
        },
      },
      ripple: true,
    });
    app.component('Button', Button);
    app.component('Dialog', Dialog);
    app.component('Checkbox', Checkbox);
    app.component('RadioButton', RadioButton);
    app.component('InputNumber', InputNumber);
    // ... добавьте все нужные компоненты
  },
};
