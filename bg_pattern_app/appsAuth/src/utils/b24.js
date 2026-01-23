/**
 * Утилиты для работы с Битрикс24
 */

/**
 * Проверка доступности BX24 API
 */
export function isBX24Available() {
  return typeof BX24 !== 'undefined' && BX24 !== null;
}

/**
 * Инициализация интеграции с BX24
 */
export async function initializeBX24Integration() {
  if (!isBX24Available()) {
    return { success: false, error: 'BX24 is not available' };
  }

  try {
    await BX24.init();
    return { success: true };
  } catch (error) {
    console.error('Failed to initialize BX24:', error);
    return { success: false, error: error.message };
  }
}

/**
 * Получение домена из BX24
 */
export async function extractDomainFromBX24() {
  if (!isBX24Available()) {
    return null;
  }

  try {
    return BX24.getDomain();
  } catch (error) {
    console.warn('Failed to get domain from BX24:', error);
    return null;
  }
}

/**
 * Получение ID пользователя BX24
 */
export async function getBX24UserId() {
  if (!isBX24Available()) {
    return null;
  }

  try {
    const auth = await BX24.getAuth();
    return auth.user_id;
  } catch (error) {
    console.warn('Failed to get BX24 user ID:', error);
    return null;
  }
}

/**
 * Получение роли пользователя в приложении
 */
export async function getBX24UserRole() {
  if (!isBX24Available()) {
    return null;
  }

  try {
    const auth = await BX24.getAuth();
    return auth.member_id;
  } catch (error) {
    console.warn('Failed to get BX24 user role:', error);
    return null;
  }
}

/**
 * Сохранение токенов в настройках BX24 приложения
 */
export async function saveTokensToBX24(accessToken, refreshToken) {
  if (!isBX24Available()) {
    return false;
  }

  try {
    const options = BX24.getOptions() || {};
    options.accessToken = accessToken;
    options.refreshToken = refreshToken;
    options.tokenExpires = Date.now() + 3600000; // 1 час
    
    BX24.setOptions(options);
    return true;
  } catch (error) {
    console.warn('Failed to save tokens to BX24:', error);
    return false;
  }
}

/**
 * Загрузка токенов из настроек BX24 приложения
 */
export async function loadTokensFromBX24() {
  if (!isBX24Available()) {
    return null;
  }

  try {
    const options = BX24.getOptions() || {};
    
    if (options.accessToken && options.refreshToken) {
      return {
        accessToken: options.accessToken,
        refreshToken: options.refreshToken
      };
    }
    
    return null;
  } catch (error) {
    console.warn('Failed to load tokens from BX24:', error);
    return null;
  }
}

/**
 * Очистка токенов из настроек BX24
 */
export async function clearTokensFromBX24() {
  if (!isBX24Available()) {
    return;
  }

  try {
    const options = BX24.getOptions() || {};
    delete options.accessToken;
    delete options.refreshToken;
    delete options.tokenExpires;
    
    BX24.setOptions(options);
  } catch (error) {
    console.warn('Failed to clear tokens from BX24:', error);
  }
}

/**
 * Вызов метода BX24 API
 */
export async function callBX24Method(method, params = {}) {
  if (!isBX24Available()) {
    throw new Error('BX24 API is not available');
  }

  try {
    return await BX24.callMethod(method, params);
  } catch (error) {
    console.error(`BX24 API call failed (${method}):`, error);
    throw error;
  }
}

/**
 * Пакетный вызов методов BX24 API
 */
export async function callBX24Batch(methods) {
  if (!isBX24Available()) {
    throw new Error('BX24 API is not available');
  }

  try {
    const batchCalls = methods.map(({ method, params }) => ({
      method,
      params: params || {}
    }));

    return await BX24.callBatch(batchCalls);
  } catch (error) {
    console.error('BX24 batch call failed:', error);
    throw error;
  }
}

/**
 * Получение информации о портале через BX24 API
 */
export async function getPortalInfo() {
  try {
    const [currentUser, departments] = await Promise.all([
      callBX24Method('user.current'),
      callBX24Method('department.get')
    ]);

    return {
      userId: currentUser.ID,
      userName: `${currentUser.NAME} ${currentUser.LAST_NAME}`,
      userEmail: currentUser.EMAIL,
      departmentCount: departments.length,
      portalTimezone: currentUser.TIME_ZONE || 'Europe/Moscow'
    };
  } catch (error) {
    console.error('Failed to get portal info:', error);
    return null;
  }
}

/**
 * Извлечение домена из URL
 */
export function extractDomainFromUrl(url) {
  try {
    const urlObj = new URL(url);
    return urlObj.hostname.replace('www.', '');
  } catch (error) {
    console.warn('Failed to extract domain from URL:', error);
    return null;
  }
}