/**
 * Безопасная работа с localStorage
 */
export class StorageManager {
  constructor(prefix = 'b24app_') {
    this.prefix = prefix;
    this.storageAvailable = this.checkStorage();
  }

  checkStorage() {
    try {
      const testKey = '__test__';
      localStorage.setItem(testKey, testKey);
      localStorage.removeItem(testKey);
      return true;
    } catch (error) {
      console.warn('localStorage is not available:', error);
      return false;
    }
  }

  getKey(domain) {
    return `${this.prefix}${domain}`;
  }

  saveTokens(domain, tokens) {
    if (!this.storageAvailable) return false;

    try {
      const data = {
        accessToken: tokens.accessToken,
        refreshToken: tokens.refreshToken,
        savedAt: Date.now(),
        domain
      };
      
      localStorage.setItem(this.getKey(domain), JSON.stringify(data));
      return true;
    } catch (error) {
      console.error('Failed to save tokens:', error);
      return false;
    }
  }

  loadTokens(domain) {
    if (!this.storageAvailable) return null;

    try {
      const stored = localStorage.getItem(this.getKey(domain));
      if (!stored) return null;

      const data = JSON.parse(stored);
      
      // Проверяем, что данные не устарели (больше 7 дней)
      const weekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000);
      if (data.savedAt < weekAgo) {
        this.clearTokens(domain);
        return null;
      }

      return {
        accessToken: data.accessToken,
        refreshToken: data.refreshToken
      };
    } catch (error) {
      console.error('Failed to load tokens:', error);
      return null;
    }
  }

  clearTokens(domain) {
    if (!this.storageAvailable) return;

    try {
      localStorage.removeItem(this.getKey(domain));
    } catch (error) {
      console.error('Failed to clear tokens:', error);
    }
  }

  clearAllTokens() {
    if (!this.storageAvailable) return;

    try {
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith(this.prefix)) {
          localStorage.removeItem(key);
        }
      }
    } catch (error) {
      console.error('Failed to clear all tokens:', error);
    }
  }
}