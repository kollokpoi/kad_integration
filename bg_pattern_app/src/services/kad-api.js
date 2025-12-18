// services/kad-api.js

const API_BASE_URL = 'https://bgdev.site/api/kad';

/**
 * Функция для получения данных из Картотеки арбитражных дел
 * @param {Object} params - Параметры запроса
 * @param {string} params.type - Тип запроса ('byInn' или 'byId')
 * @param {string} [params.inn] - ИНН организации (для type='byInn')
 * @param {string} [params.caseNumber] - Номер дела (для type='byId')
 * @param {boolean} [params.includeTimeline=false] - Включить хронологию событий по делу (для type='byId')
 * @returns {Promise<Array>} - Массив с данными о делах
 */
export const fetchArbitrationCases = async (params = {}) => {
  const { type = 'byInn', inn = '', caseNumber = '', includeTimeline = false } = params;

  try {
    let endpoint, requestBody;

    if (type === 'byInn' && inn) {
      endpoint = '/getlistbyinn';
      requestBody = { inn };
    } else if (type === 'byId' && caseNumber) {
      endpoint = '/getbyid';
      requestBody = {
        case_number: caseNumber,
        include_timeline: includeTimeline,
      };
    } else {
      throw new Error('Недостаточно параметров для запроса');
    }

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error || `Ошибка запроса: ${response.status}`);
    }
    
    const data = await response.json();
    console.log('Данные из Картотеки арбитражных дел:', data);
    return data.results || [];
  } catch (error) {
    console.error('Ошибка API КАД:', error);
    throw error;
  }
};
