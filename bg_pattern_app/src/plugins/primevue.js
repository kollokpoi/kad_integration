// Импортируем  PrimeVue
import PrimeVue from 'primevue/config';
// Импортируем кастмоный Пресет
import MyPreset from '../myPreset.js'; // Наш файл пресета
// Импортируем компоненты PrimeVue
import 'primeicons/primeicons.css'; // <-- добавить эту строку
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Button from 'primevue/button';
import ButtonGroup from 'primevue/buttongroup';
import Card from 'primevue/card';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';
import InputMask from 'primevue/inputmask';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import ProgressBar from 'primevue/progressbar';
import Skeleton from 'primevue/skeleton';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import TabView from 'primevue/tabview';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import Timeline from 'primevue/timeline';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';
//Импортим шапку
import PageHeader from '../components/PageHeader.vue'; // Относительный путь к компоненту
// Импортируем глобальные настройки

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

    // Предоставляем глобальные настройки через provide
    app.use(ToastService);
    app.component('Toast', Toast);
    // Глобальная регистрация компонента
    app.component('PageHeader', PageHeader);
    app.component('FileUpload', FileUpload);
    app.component('InputMask', InputMask);
    app.component('Checkbox', Checkbox);
    app.component('Dialog', Dialog);
    app.component('Dropdown', Dropdown);
    app.component('Message', Message);
    app.component('TabView', TabView);
    app.component('Textarea', Textarea);
    app.component('Timeline', Timeline);
    app.component('ButtonGroup', ButtonGroup);
    app.component('Divider', Divider);
    app.component('Tag', Tag);
    app.component('Card', Card);
    app.component('Column', Column);
    app.component('DataTable', DataTable);
    app.component('ProgressBar', ProgressBar);
    app.component('Skeleton', Skeleton);
    app.component('Accordion', Accordion);
    app.component('AccordionTab', AccordionTab);
    app.component('Button', Button);
    app.component('InputText', InputText);
    app.component('Tab', Tab);
    app.component('TabList', TabList);
    app.component('TabPanel', TabPanel);
    app.component('TabPanels', TabPanels);
    app.component('Tabs', Tabs);
  },
};
