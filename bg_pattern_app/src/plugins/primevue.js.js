// Импортируем  PrimeVue
import PrimeVue from 'primevue/config';
// Импортируем кастмоный Пресет
import MyPreset from '../myPreset.js'; // Наш файл пресета
// Импортируем компоненты PrimeVue
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Button from 'primevue/button';
import ButtonGroup from 'primevue/buttongroup';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Dropdown from 'primevue/dropdown';
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
