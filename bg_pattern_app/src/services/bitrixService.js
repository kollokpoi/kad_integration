class BitrixService {
  constructor() {
    this.isInitialized = false;
    this.appData = {
      placementOptions: null,
      auth: null,
    };
    this.DAILY_CLICK_LIMIT = 99999; // Значение по умолчанию
    this.mapping = {
      standard: 15,
      professional: 50,
      corporate: 99999,
    };
    // Инициализируем лимит кликов
    this.initClickLimit();
  }

  async initClickLimit() {
    try {
      const subscriptionInfo = await this.checkSubscription();

      if (subscriptionInfo.subscribed) {
        // Приводим тарифный ключ к нижнему регистру
        const tariffKey =
          subscriptionInfo.tariffKey && typeof subscriptionInfo.tariffKey === 'string'
            ? subscriptionInfo.tariffKey.toLowerCase()
            : null;

        if (tariffKey && this.mapping[tariffKey] !== undefined) {
          this.DAILY_CLICK_LIMIT = this.mapping[tariffKey];
          console.log(`Установлен лимит кликов: ${this.DAILY_CLICK_LIMIT} для тарифа ${tariffKey}`);
        } else {
          // Если тарифный ключ неожиданно отсутствует или не соответствует ожидаемому,
          // можно выбросить ошибку или выполнить специальную обработку
          console.error('Неизвестный тарифный ключ:', subscriptionInfo.tariffKey);
          // Например, можно назначить лимит по умолчанию, либо выбросить ошибку
          // this.DAILY_CLICK_LIMIT = 10;
        }
      } else {
        console.log('Подписка не активна, используется лимит по умолчанию');
      }
    } catch (error) {
      console.error('Ошибка при инициализации лимита кликов:', error);
    }
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
        reject(new Error('BX24 не найден'));
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

      const defaultHandler = 'https://bg59.online/Apps/kad_test/dist/index.html';

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
  async Checkrequests() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      return await BX24.callMethod('app.option.get', { options: ['app_settings'] });
    } catch (error) {
      console.error('Ошибка при получении информации о размещении:', error);
      throw error;
    }
  }
  async checkSubscription() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }
      const placement = await BX24.getAuth();
      const domain = placement.domain;
      console.log('Domain:', domain);
      console.log(placement);

      const response = await fetch(
        `https://bg59.online/Apps/bg_pattern_app/api/check_subscription.php?portal=${encodeURIComponent(
          domain,
        )}`,
      );
      if (!response.ok) {
        throw new Error(`Ошибка сервера: ${response.status}`);
      }

      const data = await response.json();

      if (data.status === 'ok' && data.has_subscription) {
        console.log(`Подписка активна, ключ тарифа: ${data.tariff_key}`);
        return {
          subscribed: true,
          tariffKey: data.tariff_key,
        };
      } else {
        console.log('Подписка не активна.');

        //вечная подписка
        return { subscribed: true };
      }
    } catch (error) {
      console.error('Ошибка при проверке подписки:', error);
      throw error;
    }
  }

  // Пример вызова функции в вашем приложении Битрикс24
  async initSubscriptionCheck() {
    try {
      const subscriptionInfo = await this.checkSubscription();
      console.log(subscriptionInfo);

      if (subscriptionInfo.subscribed) {
        console.log(`У вас активная подписка, тариф: ${subscriptionInfo.tariffKey}`);
        return subscriptionInfo.tariffKey;
        // Выполняйте необходимую логику для активной подписки
      } else {
        console.log('У вас нет активной подписки.');
        // Логика для отсутствующей подписки
      }
    } catch (error) {
      console.error('Ошибка при проверке подписки:', error);
    }
  }

  // Можно менять это значение

  async getClickData() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }

      // Получаем все сохраненные опции
      const result = await this.callMethod('app.option.get', {});

      // Если данных нет, возвращаем структуру по умолчанию
      if (!result || !result.click_data) {
        return { count: 0, date: null };
      }

      // Парсим сохраненные данные
      const clickData = JSON.parse(result.click_data);
      return {
        count: parseInt(clickData.count || 0),
        date: clickData.date || null,
      };
    } catch (error) {
      console.error('Ошибка при получении данных о кликах:', error);
      return { count: 0, date: null };
    }
  }

  async addClick() {
    try {
      if (!this.isInitialized) {
        await this.init();
      }

      const today = new Date().toISOString().split('T')[0];
      let clickData = await this.getClickData();

      // Если новый день - сбрасываем счетчик
      if (clickData.date !== today) {
        clickData = { count: 1, date: today };
      } else {
        // Проверяем лимит
        if (clickData.count >= this.DAILY_CLICK_LIMIT) {
          return {
            success: false,
            message: `Достигнут дневной лимит ${this.DAILY_CLICK_LIMIT} кликов`,
            count: clickData.count,
            limit: this.DAILY_CLICK_LIMIT,
            isLimitReached: true,
          };
        }

        // Увеличиваем счетчик
        clickData.count++;
      }

      // Сохраняем новые данные согласно документации
      await this.callMethod('app.option.set', {
        options: {
          click_data: JSON.stringify(clickData), // Сохраняем как строку
        },
      });

      return {
        success: true,
        message: `Кликов сегодня: ${clickData.count}/${this.DAILY_CLICK_LIMIT}`,
        count: clickData.count,
        limit: this.DAILY_CLICK_LIMIT,
        isLimitReached: false,
        stats: {
          dailyLimit: this.DAILY_CLICK_LIMIT,
          used: clickData.count,
          remaining: this.DAILY_CLICK_LIMIT - clickData.count,
          lastDate: clickData.date,
        },
      };
    } catch (error) {
      console.error('Ошибка при добавлении клика:', error);
      return {
        success: false,
        message: 'Ошибка сервера',
        count: 0,
        limit: this.DAILY_CLICK_LIMIT,
        isLimitReached: false,
      };
    }
  }

  async getClickStats() {
    try {
      const data = await this.getClickData();
      const today = new Date().toISOString().split('T')[0];
      const isToday = data.lastDate === today;

      return {
        dailyLimit: this.DAILY_CLICK_LIMIT,
        used: isToday ? data.clicks : 0,
        remaining: isToday ? this.DAILY_CLICK_LIMIT - data.clicks : this.DAILY_CLICK_LIMIT,
        lastDate: data.lastDate,
        isLimitReached: isToday && data.clicks >= this.DAILY_CLICK_LIMIT,
      };
    } catch (error) {
      console.error('Ошибка при получении статистики кликов:', error);
      return {
        dailyLimit: this.DAILY_CLICK_LIMIT,
        used: 0,
        remaining: this.DAILY_CLICK_LIMIT,
        lastDate: null,
        isLimitReached: false,
      };
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

  async getInnFromEntity(entityType, entityId) {
    if (!entityType || !entityId) {
      throw new Error('Не указаны обязательные параметры: entityType или entityId');
    }

    try {
      const methodMap = {
        'deal': 'crm.deal.get',
        'lead': 'crm.lead.get',
        'contact': 'crm.contact.get',
        'company': 'crm.company.get'
      };

      const method = methodMap[entityType.toLowerCase()];
      if (!method) {
        throw new Error(`Неподдерживаемый тип сущности: ${entityType}`);
      }

      // Преобразуем ID в число
      const numericId = parseInt(entityId);
      if (isNaN(numericId) || numericId <= 0) {
        throw new Error(`Некорректный ID сущности: ${entityId}`);
      }

      const entity = await this.callMethod(method, {
        id: numericId,
        select: ['ID', 'UF_CRM_INNNUMBER', 'TITLE', 'NAME']
      });

      const innValue = entity?.UF_CRM_INNNUMBER;
      
      console.log(`Получен ИНН для ${entityType} ${entityId}:`, {
        value: innValue,
        entityName: entity?.TITLE || entity?.NAME || 'Без названия'
      });

      return innValue || null;

    } catch (error) {
      console.error(`Ошибка получения ИНН из ${entityType} ${entityId}:`, error);
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
