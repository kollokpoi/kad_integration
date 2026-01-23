class BitrixService {
  constructor() {
    this.isInitialized = false;
    this.appData = {
      placementOptions: null,
      auth: null,
    };
    this.init()
  }

  async init() {
    return new Promise((resolve, reject) => {
      if (window.BX24) {
        try {
          window.BX24.init(() => {
            this.isInitialized = true;
            this.appData.placementOptions = window.BX24.placement.info();
            this.appData.auth = window.BX24.getAuth();
            console.log('BX24 успешно инициализирован');
            resolve(true);
          });
        } catch (error) {
          console.error('Ошибка при инициализации BX24:', error);
          reject(error);
        }
      } else {
        console.error('BX24 не найден. Убедитесь, что приложение запущено в среде Битрикс24');
      }
    });
  }

  async checkConnection() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      await this.callMethod('app.info');
      return { success: true, message: 'Соединение установлено успешно' };
    } catch (error) {
      console.error('Ошибка при проверке подключения:', error);
      return { success: false, message: error.message || 'Не удалось установить соединение' };
    }
  }

  async isConnected() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      return this.isInitialized;
    } catch (error) {
      return false;
    }
  }

  callMethod(method, params = {}) {
    return new Promise((resolve, reject) => {
      if (!this.isInitialized) {
        reject(
          new Error(
            'BX24 не инициализирован. Вызовите метод init() перед использованием callMethod()',
          ),
        );
        return;
      }
      window.BX24.callMethod(method, params, (result) => {
        if (result.error()) {
          console.error(`Ошибка при вызове метода ${method}:`, result.error());
          reject(new Error(result.error()));
        } else {
          resolve(result.data());
        }
      });
    });
  }

  async getSettings() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      const result = await this.callMethod('app.option.get', {
        options: ['app_settings'],
      });
      const settings = result && result.app_settings ? JSON.parse(result.app_settings) : null;
      return (
        settings || {
          entities: [],
          settings: { refreshInterval: 15, displayMode: 'detailed', notifications: true },
        }
      );
    } catch (error) {
      console.error('Ошибка при получении настроек:', error);
      return null;
    }
  }

  async saveSettings(settings) {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      await this.callMethod('app.option.set', {
        options: { app_settings: JSON.stringify(settings) },
      });
      return true;
    } catch (error) {
      console.error('Ошибка при сохранении настроек:', error);
      throw error;
    }
  }

  // Пример: connectToEntities принимает массив
  async connectToEntities(entityKeys = []) {
    try {
      if (!this.isInitialized) {
        await this.init();
      }

      // Маппинг идентификаторов на коды встройки
      const placementMapping = {
        leads: 'CRM_LEAD_DETAIL_TAB',
        deals: 'CRM_DEAL_DETAIL_TAB',
        companies: 'CRM_COMPANY_DETAIL_TAB',
        contacts: 'CRM_CONTACT_DETAIL_TAB',
      };

      const defaultHandler = 'https://bg59.online/Apps/bg_kad_integration/dist/index.html';

      // Вызываем bind только для тех сущностей, которые переданы
      const results = await Promise.allSettled(
        entityKeys.map((key) => {
          const placement = placementMapping[key];
          if (!placement) {
            // Если такого ключа нет в маппинге, можно либо пропустить, либо выбросить ошибку
            return Promise.resolve();
          }
          return this.callMethod('placement.bind', {
            placement,
            handler: defaultHandler,
          });
        }),
      );

      return results.every((result) => result.status === 'fulfilled');
    } catch (error) {
      console.error('Ошибка при подключении к сущностям:', error);
      throw error;
    }
  }

  async disconnectFromEntities(entityKeys = []) {
    try {
      if (!this.isInitialized) {
        await this.init();
      }

      const placementMapping = {
        leads: 'CRM_LEAD_DETAIL_TAB',
        deals: 'CRM_DEAL_DETAIL_TAB',
        companies: 'CRM_COMPANY_DETAIL_TAB',
        contacts: 'CRM_CONTACT_DETAIL_TAB',
      };

      const defaultHandler = 'https://bg59.online/Apps/kad_test/dist/index.html';

      // Вызываем unbind только для тех сущностей, которые переданы
      const results = await Promise.allSettled(
        entityKeys.map((key) => {
          const placement = placementMapping[key];
          if (!placement) {
            return Promise.resolve();
          }
          return this.callMethod('placement.unbind', {
            placement,
            handler: defaultHandler,
          });
        }),
      );

      return results.every((result) => result.status === 'fulfilled');
    } catch (error) {
      console.error('Ошибка при отключении от сущностей:', error);
      throw error;
    }
  }
  async placementInfo() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      return await BX24.placement.info();
    } catch (error) {
      console.error('Ошибка при получении информации о размещении:', error);
      throw error;
    }
  }

  async GetUserfield(entitytype, entityid) {
    try {
      // Проверка обязательных параметров
      if (!entitytype || !entityid) {
        throw new Error('Не указаны обязательные параметры (entitytype или entityid)');
      }

      // Создаем Promise для работы с callback-based API
      const result = await new Promise((resolve, reject) => {
        BX24.callMethod(`crm.${entitytype}.get`, { id: entityid }, function (response) {
          if (response.error()) {
            console.error('BX24 API Error:', response.error());
            reject(new Error(response.error()));
          } else {
            resolve(response);
          }
        });
      });

      // Проверяем, что результат содержит данные
      if (!result) {
        throw new Error('Пустой ответ от BX24 API');
      }

      // Получаем данные из ответа
      const data = result.data();
      console.log('GetUserfield:', data);
      return data;
    } catch (error) {
      console.error('GetUserfield failed:', {
        entitytype,
        entityid,
        error: error.message,
        stack: error.stack,
      });
      throw error;
    }
  }


  async addActivities(activities) {
    try {
      const reversedActivities = [...activities].reverse();

      // Создаем batchCalls с новым порядком элементов
      const batchCalls = {};
      reversedActivities.forEach((fields, index) => {
        batchCalls[`activity_${index}`] = {
          method: 'crm.timeline.comment.add',
          params: { fields },
        };
      });
      console.log('Пакетное добавление активностей:', batchCalls);
      return await new Promise((resolve, reject) => {
        BX24.callBatch(batchCalls, (result) => {
          const errors = Object.values(result).filter((item) => item.error());
          if (errors.length > 0) {
            console.error('Ошибки в пакетном запросе:', errors);
            reject(errors);
          } else {
            resolve(result);
          }
        });
      });
    } catch (error) {
      console.error('Ошибка при пакетном добавлении активностей:', error);
      throw error;
    }
  }

  async getCurrentUser() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      return await this.callMethod('user.current');
    } catch (error) {
      console.error('Ошибка при получении информации о пользователе:', error);
      throw error;
    }
  }

  async toggleUserLike(featureId) {
    try {
      let likedFeatures = [];
      // Если Bitrix API инициализирован, используем методы для работы с пользовательскими настройками
      if (this.isInitialized) {
        // Получаем сохранённое значение через метод user.option.get
        const result = await this.callMethod('user.option.get', { optionName: 'likedFeatures' });
        if (result && result.likedFeatures) {
          try {
            likedFeatures = JSON.parse(result.likedFeatures);
          } catch (e) {
            likedFeatures = [];
          }
        }
      } else {
        // Fallback: используем localStorage
        const stored = localStorage.getItem('likedFeatures');
        likedFeatures = stored ? JSON.parse(stored) : [];
      }

      let liked = false;
      // Если функционал уже лайкнут, удаляем его из списка, иначе – добавляем
      if (likedFeatures.includes(featureId)) {
        likedFeatures = likedFeatures.filter((id) => id !== featureId);
        liked = false;
      } else {
        likedFeatures.push(featureId);
        liked = true;
      }

      // Сохраняем обновленный список лайков через user.option.set
      if (this.isInitialized) {
        await this.callMethod('user.option.set', {
          optionName: 'likedFeatures',
          optionValue: JSON.stringify(likedFeatures),
        });
      } else {
        localStorage.setItem('likedFeatures', JSON.stringify(likedFeatures));
      }

      return { liked, likedFeatures };
    } catch (error) {
      console.error('Ошибка при переключении лайка пользователя:', error);
      throw error;
    }
  }

  finishWork() {
    if (window.BX24 && this.isInitialized) {
      window.BX24.closeApplication();
    }
  }
}

export default new BitrixService();
